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
class AxIr_Model_DriverService extends AxIr_Model_ServiceAbstract
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
     * static cache of model objects. less expensive than rows
     * @var array
     */
    protected static $_modelRepositoryByEventClassCategoryNumber = array();
    
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
    protected static $_dbTableClass = 'AxIr_Model_DbTable_Drivers';
    protected static $_modelClass = 'AxIr_Model_Driver';
    protected static $_columnMap = array(
        'id'=>'id',
        'event'=>'event_id',
        'category'=>'category_id',
        'class'=>'class_id',
        'number'=>'number',
        'name'=>'name',
        'car'=>'car',
        'carColor'=>'car_color',
        'bestTimeRaw'=>'best_time_raw',
        'bestTimePax'=>'best_time_pax'
    );
    
    protected function _mapEvent($value)
    {
        $eventService = new AxIr_Model_EventService();
        return $eventService->getByPrimary($value);
    }
    
    protected function _unmapEvent(AxIr_Model_Event $value)
    {
        return $value->getId();
    }
    
    protected function _mapCategory($value)
    {
        $categoryService = new AxIr_Model_CategoryService();
        return $categoryService->getByPrimary($value);
    }
    
    protected function _unmapCategory(AxIr_Model_Category $value)
    {
        return $value->getId();
    }
    
    protected function _mapClass($value)
    {
        $classService = new AxIr_Model_ClassService();
        return $classService->getByPrimary($value);
    }
    
    protected function _unmapClass(AxIr_Model_Class $value)
    {
        return $value->getId();
    }
    
    /**
     * delete all drivers for a given event
     * @param AxIr_Model_Event $event 
     */
    public function deleteAllForEvent(AxIr_Model_Event $event)
    {
        $class = $this->_getClass();
        $table = $this->_getDbTable();
        $adapter = $table->getAdapter();
        $deleteWhere = $adapter->quoteInto('event_id = ?', $event->id);
        $table->delete($deleteWhere); // schema cascades delete to runs table
        $class::$_rowRepository = array();
    }
    
    /**
     * retrieve a driver row by their event, category, class, and number. if
     * not found, return false.
     * @param mixed $eventId
     * @param mixed $categoryId
     * @param mixed $classId
     * @param mixed $number
     * @return Zend_Db_Table_Row|false
     */
    protected function _getDriverRowByEventIdCategoryIdClassIdNumber(
            $eventId, 
            $categoryId, 
            $classId, 
            $number
    )
    {
        $class = $this->_getClass();
        if ($class::$_rowRepository)
        {
            foreach ($class::$_rowRepository as $row)
            {
                if (
                        $row->event_id == $eventId
                        and $row->category_id == $categoryId
                        and $row->class_id == $classId
                        and $row->number == $number
                )
                {
                    return $row;
                }
            }
        }
        $table = $class::_getDbTable();
        $select = $table->select();
        $select->where('event_id = ?', $eventId)
                ->where('category_id = ?', $categoryId)
                ->where('class_id = ?', $classId)
                ->where('number = ?', $number);
        $row = $table->fetchRow($select);
        if ($row instanceof Zend_Db_Table_Row)
        {
            return $row;
        }
        return false;
    }
    
    /**
     * return a driver model by event, category, class, and number. if not found,
     * return false.
     * @param AxIr_Model_Event $event
     * @param AxIr_Model_Category $category
     * @param AxIr_Model_Class $class
     * @param mixed $number
     * @return AxIr_Model_Driver|false 
     */
    public function getDriverByEventCategoryClassNumber(AxIr_Model_Event $event, AxIr_Model_Category $category, AxIr_Model_Class $class, $number)
    {
        if (
                ($model = $this->_getCachedDriverByEventCategoryClassNumber(
                        $event, 
                        $category, 
                        $class, 
                        $number
                )) !== false
        )
        {
            return $model;
        }
        $row = $this->_getDriverRowByEventIdCategoryIdClassIdNumber(
                $event->id, 
                $category->id, 
                $class->id, 
                $number
        );
        if (!$row) return false;
        $model = $this->createFromRow($row);
        $this->_cacheModel($model);
        return $model;
    }
    
    /**
     * construct a driver model for a given event and state file line
     * @param AxIr_Model_Event $event
     * @param AxIr_Parser_StateFileLine $line
     * @return AxIr_Model_Driver 
     */
    public function createFromStateFileLine(
            AxIr_Model_Event $event, 
            AxIr_Parser_StateFileLine $line
    )
    {
        $driver = new AxIr_Model_Driver();
        $driver->event = $event;
        $driver->name = $line->driverName;
        $driver->number = $line->driverNumber;
        $driver->car = $line->car;
        $driver->carColor = $line->carColor;
        $categoryService = new AxIr_Model_CategoryService();
        $driverCategory = $line->driverCategory;
        $driver->category = $categoryService->getCategoryByPrefix($driverCategory);
        $classService = new AxIr_Model_ClassService();
        $driver->class = $classService->getClassByName($line->driverClass);
        if (!($driver->class instanceof AxIr_Model_Class))
        {
            $driver->class = new AxIr_Model_Class();
            $driver->class->name = $line->driverClass;
            $classService->store($driver->class);
        }
        return $driver;
    }
    
    /**
     * override the parent::_cacheModel method with special logic
     * that is particular to serving Driver models. Driver models are cached
     * both by primary key (as all services do with their respective models), 
     * and also by a composite of their event, category, class, and number.
     * @param AxIr_Model_Class $model 
     */
    protected function _cacheModel(AxIr_Model_Driver $model) 
    {
        $class = $this->_getClass();
        $class::$_modelRepository[$model->id] = $model;
        $key = $this->_getCacheKeyDriverByEventCategoryClassNumber(
                $model->event, 
                $model->category, 
                $model->class, 
                $model->number
        );
        self::$_modelRepositoryByEventClassCategoryNumber[$key] = $model;
    }
    
    /**
     * build a composite key for the driver event/category/class/number cache
     * @param AxIr_Model_Event $event
     * @param AxIr_Model_Category $category
     * @param AxIr_Model_Class $class
     * @param mixed $number
     * @return string 
     */
    protected function _getCacheKeyDriverByEventCategoryClassNumber(
            AxIr_Model_Event $event, 
            AxIr_Model_Category $category, 
            AxIr_Model_Class $class, 
            $number
    )
    {
        return "{$event->id}-{$category->id}-{$class->id}-{$number}";
    }
    
    /**
     * retrieve a driver from cache by event, category, class, and number. if 
     * not found, return false.
     * @param AxIr_Model_Event $event
     * @param AxIr_Model_Category $category
     * @param AxIr_Model_Class $class
     * @param mixed $number
     * @return AxIr_Model_Driver|false
     */
    protected function _getCachedDriverByEventCategoryClassNumber(
            AxIr_Model_Event $event, 
            AxIr_Model_Category $category, 
            AxIr_Model_Class $class, 
            $number
    )
    {
        $key = $this->_getCacheKeyDriverByEventCategoryClassNumber(
                $event, 
                $category, 
                $class,
                $number
        );
        if (array_key_exists($key, self::$_modelRepositoryByEventClassCategoryNumber))
        {
            return self::$_modelRepositoryByEventClassCategoryNumber[$key];
        }
        return false;
    }
    
    /**
     * retrieve a rowset of drivers at a given event
     * @param mixed $eventId
     * @return Zend_Db_Table_Rowset
     */
    protected function _getDriverRowsAtEvent($eventId)
    {
        $table = $this->_getDbTable();
        $select = $table->select();
        $select->where('event_id = ?', $eventId);
        $select->order('name ASC');
        $select->order('number ASC');
        $rows = $table->fetchAll($select);
        return $rows;
    }
    
    /**
     * retrieve an array of driver models at a given event
     * @param AxIr_Model_Event $event
     * @return array 
     */
    public function getDriversAtEvent(AxIr_Model_Event $event)
    {
        $rows = $this->_getDriverRowsAtEvent($event->id);
        $drivers = array();
        foreach ($rows as $row)
        {
            if (($driver = $this->_getCachedModel($row->id)) === false)
            {
                $driver = $this->createFromRow($row);
                $this->_cacheModel($driver);
            }
            $drivers[] = $driver;
        }
        return $drivers;
    }
    
    /**
     * 
     * @param mixed $eventId
     * @param string $timeType (must be either 'raw' or 'pax')
     * @return Zend_Db_Table_Rowset
     */
    protected function _getDriverRowsAtEventByTime($eventId, $timeType)
    {
        $table = $this->_getDbTable();
        $select = $table->select();
        $select->where('event_id = ?', $eventId);
        $select->order("best_time_{$timeType} ASC");
        $rows = $table->fetchAll($select);
        return $rows;
    }
    
    /**
     * retrieve an array of driver models at a given event sorted by their best
     * time of a given type
     * @param AxIr_Model_Event $event
     * @param string $timeType (must be either 'raw' or 'pax')
     * @return array 
     */
    public function getDriversAtEventByBestTime(AxIr_Model_Event $event, $timeType)
    {
        if (!in_array($timeType, array('raw', 'pax')))
        {
            $message = 'Invalid $timeType: must be either raw or pax';
            throw new AxIr_Model_Service_Exception($message);
        }
        $rows = $this->_getDriverRowsAtEventByTime($event->id, $timeType);
        $drivers = array();
        foreach ($rows as $row)
        {
            if (($driver = $this->_getCachedModel($row->id)) === false)
            {
                $driver = $this->createFromRow($row);
                $this->_cacheModel($driver);
            }
            $drivers[] = $driver;
        }
        return $drivers;
    }
    
    /**
     * retrieve driver rows at a given event in a given category ordered by
     * their best time of a certain time type
     * @param mixed $eventId
     * @param mixed $categoryId
     * @param string $timeType (must be either 'raw' or 'pax')
     * @return Zend_Db_Table_Rowset
     */
    protected function _getDriverRowsAtEventInCategoryByBestTime($eventId, $categoryId, $timeType)
    {
        $table = $this->_getDbTable();
        $select = $table->select();
        $select->where('event_id = ?', $eventId);
        $select->where('category_id = ?', $categoryId);
        $select->order("best_time_{$timeType} ASC");
        $rows = $table->fetchAll($select);
        return $rows;
    }
    
    /**
     * retrieve an array of driver models at a given event in a given category
     * sorted by their best time
     * @param AxIr_Model_Event $event
     * @param AxIr_Model_Category $category
     * @return array
     */
    public function getDriversAtEventInCategoryByBestTime(AxIr_Model_Event $event, AxIr_Model_Category $category)
    {
        $rows = $this->_getDriverRowsAtEventInCategoryByBestTime($event->id, $category->id, $category->timeType);
        $drivers = array();
        foreach ($rows as $row)
        {
            if (($driver = $this->_getCachedModel($row->id)) === false)
            {
                $driver = $this->createFromRow($row);
                $this->_cacheModel($driver);
            }
            $drivers[] = $driver;
        }
        return $drivers;
    }
    
    /**
     * retrieve a rowset of drivers at a given event in a given class by
     * their best raw time (open class-level comparisons are ALWAYS raw time)
     * @param mixed $eventId
     * @param mixed $classId
     * @return Zend_Db_Table_Rowset
     */
    protected function _getDriverRowsAtEventInClassByBestTime($eventId, $classId)
    {
        $table = $this->_getDbTable();
        $select = $table->select();
        $select->where('event_id = ?', $eventId);
        $select->where('class_id = ?', $classId);
        $select->where('category_id = 1');
        $select->order("best_time_raw ASC");
        $rows = $table->fetchAll($select);
        return $rows;
    }
    
    /**
     * retrieve an array of driver models at a given event in a given class by
     * their best raw time (open class-level comparisons are ALWAYS raw time)
     * @param AxIr_Model_Event $event
     * @param AxIr_Model_Class $class
     * @return array
     */
    public function getDriversAtEventInClassByBestTime(AxIr_Model_Event $event, AxIr_Model_Class $class)
    {
        $rows = $this->_getDriverRowsAtEventInClassByBestTime($event->id, $class->id);
        $drivers = array();
        foreach ($rows as $row)
        {
            if (($driver = $this->_getCachedModel($row->id)) === false)
            {
                $driver = $this->createFromRow($row);
                $this->_cacheModel($driver);
            }
            $drivers[] = $driver;
        }
        return $drivers;
    }
    
    /**
     * retrieve an array of distinct first letter of driver first names
     * for a given event
     * @param AxIr_Model_Event $event
     * @return array
     */
    public function getDriverNameFirstLetters(AxIr_Model_Event $event)
    {
        $sql = 'SELECT DISTINCT upper(substr(`name`, 1, 1)) ' .
                'FROM drivers ' .
                'WHERE event_id = ? ' .
                'ORDER BY upper(name) ASC';
        $table = $this->_getDbTable();
        return $table->getAdapter()->fetchCol($sql, array($event->id));
    }
}

