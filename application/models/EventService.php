<?php
/*
Copyright 2012 Carlton Whitehead

This file is part of Autocross Instant Results.

Autocross Instant Results is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Autocross Instant Results is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Autocross Instant Results.  If not, see 
<http://www.gnu.org/licenses/>.
 */
class AxIr_Model_EventService extends AxIr_Model_ServiceAbstract
{
    /**
     * static cache of row objects
     * @var array
     */
    protected static $_rowRepository = array();
    
    /**
     * static cache of model objects. less expensive than rows
     * @var array
     */
    protected static $_modelRepository = array();
    
    /**
     * indicate whether all rows are in the repository already
     * @var bool
     */
    protected static $_fetchedAll = false;
    
    /**
     * instance of our table
     * @return Zend_Db_Table
     */
    protected static $_dbTable = null;
    protected static $_dbTableClass = 'AxIr_Model_DbTable_Events';
    protected static $_modelClass = 'AxIr_Model_Event';
    protected static $_columnMap = array(
        'id'=>'id',
        'file'=>'file',
        'fileModified'=>'file_modified',
        'date'=>'date',
        'label'=>'label',
        'coneSeconds'=>'cone_seconds'
    );
    
    /**
     * 
     * @param string $value
     * @return Zend_Date 
     */
    protected function _mapFileModified($value)
    {
        return new Zend_Date($value, Zend_Date::ISO_8601);
    }
    
    /**
     *
     * @param Zend_Date $value
     * @return string
     */
    protected function _unmapFileModified(Zend_Date $value)
    {
        return $value->toString('YYYY-MM-dd HH:m:s');
    }
    
    /**
     *
     * @param string $value
     * @return Zend_Date 
     */
    protected function _mapDate($value)
    {
        return new Zend_Date($value, Zend_Date::ISO_8601);
    }
    
    /**
     *
     * @param Zend_Date $value
     * @return string
     */
    protected function _unmapDate(Zend_Date $value)
    {
        return $value->toString('YYYY-MM-dd');
    }
    
    /**
     * find an event row by date
     * @param string $date
     * @return Zend_Db_Table_Row|null
     */
    protected function _getEventRowByDate($date)
    {
        $class = $this->_getClass();
        if (isset($class::$_rowRepository))
        {
            foreach ($class::$_rowRepository as $row)
            {
                if ($row->date == $date) return $row;
            }
        }
        $table = $class::_getDbTable();
        $select = $table->select()->where('date = ?', $date);
        $row = $table->fetchRow($select);
        if ($row instanceof Zend_Db_Table_Row)
        {
            $class::$_rowRepository[$row->id] = $row;
            return $row;
        }
        return null;
    }
    
    /**
     * find all event rows sorted by date descending
     * @return Zend_Db_Table_Rowset
     */
    protected function _getAllEventRowsByDateDesc()
    {
        return $this->_getDbTable()->fetchAll(null, 'date desc');
    }
    
    /**
     * retrieve an event model by its date
     * @param string $date
     * @return AxIr_Model_Event 
     */
    public function getEventByDate($date)
    {
        $row = $this->_getEventRowByDate($date);
        if (!$row) return false;
        return $this->createFromRow($row);
    }
    
    /**
     * retrieve an array of all event models ordered by date descending
     * @return array
     */
    public function getAllEventsByDateDesc()
    {
        $rows = $this->_getAllEventRowsByDateDesc();
        if ($rows->count() == 0) return false;
        $events = array();
        foreach ($rows as $row)
        {
            if (($eventModel = $this->_getCachedModel($row->id)) !== false)
            {
                $events[] = $eventModel;
            }
            else
            {
                $eventModel = $this->createFromRow($row);
                $this->_cacheModel($eventModel);
                $events[] = $eventModel;
            }
        }
        return $events;
    }
    
    /**
     * cache the data into our db by parsing the state file's lines
     * @param AxIr_Model_Event $event 
     */
    public function cacheFromStateFile(AxIr_Model_Event $event)
    {
        $this->setFileModified($event);
        
        // instantiate all related model services
        $categoryService = new AxIr_Model_CategoryService();
        $driverService = new AxIr_Model_DriverService();
        $runService = new AxIr_Model_RunService();
        
        // retrieve permanent supporting records
        $categories = $categoryService->getAll();
        
        $driverService->deleteAllForEvent($event);
        
        $runLines = file($event->file);
        foreach ($runLines as $runLine)
        {
            
            $line = new AxIr_Parser_StateFileLine($runLine);
            try
            {
                $run = $runService->createFromStateFileLine($event, $line);
            }
            catch(AxIr_Exception_ReRun $e)
            {
                // skip over re-runs
                continue;
            }
            catch(AxIr_Parser_StateFileLine_Exception $e)
            {
                // skip over malformed lines
                continue;
            }
            $runService->store($run);
        }
    }
    
    public function setFileModified(AxIr_Model_Event $event)
    {
        $event->fileModified = $this->_mapFileModified(
                date('Y-m-d H:i:s', filemtime($event->file))
        );
    }
    
    /**
     * scan the configured stateFilePath (and sub-paths) for .st1 files
     * @return array
     */
    protected function _scanStateFilePath()
    {
        $stateFilePath = Zend_Controller_Front::getInstance()
                ->getParam('bootstrap')
                ->getOption('stateFilePath');
        $scanPath = realpath($stateFilePath);
        $files = $this->_scanStateFileSubPath($scanPath);
        
        // remove files not ending in .st1
        $stateFiles = array();
        foreach ($files as $file)
        {
            if (substr($file, strlen($file)-4) === '.st1')
            {
                $stateFiles[] = $file;
            }
        }
        return $stateFiles;
    }
    
    /**
     * recurse into the stateFilePath's sub-paths
     * @param string $path
     * @param int $currentDepth
     * @return array 
     */
    protected function _scanStateFileSubPath($path, $currentDepth = 0)
    {
        try
        {
            if ($currentDepth > 4)
            {
                $message = 'Exceeded maximum recursion depth';
                throw new Exception($message);
            }
            $contents = scandir($path);
            $files = array();
            foreach ($contents as $file)
            {
                $fullPath = realpath($path . '/' . $file);
                if ($file === '.' or $file === '..')
                {
                    continue;
                }
                elseif (is_dir($fullPath))
                {
                    $subFiles = $this->_scanStateFileSubPath($fullPath, $currentDepth + 1);
                    $files = array_merge($files, $subFiles);
                }
                else
                {
                    $files[] = $fullPath;
                }
            }
        }
        catch (Exception $e)
        {
            return array();
        }
        return $files;
    }
    
    /**
     * scans the stateFilePath and returns an array of path strings
     * representing unknown state files
     * @return array 
     */
    public function getUnknownStateFiles()
    {
        $files = $this->_scanStateFilePath();
        $events = $this->getAll();
        $knownStateFiles = array();
        foreach ($events as $event)
        {
            $knownStateFiles[] = $event->file;
        }
        $unknownStateFiles = array();
        foreach ($files as $file)
        {
            if (!in_array($file, $knownStateFiles))
            {
                $unknownStateFiles[] = $file;
            }
        }
        return $unknownStateFiles;
    }
}
