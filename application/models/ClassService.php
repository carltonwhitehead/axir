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
class AxIr_Model_ClassService extends AxIr_Model_ServiceAbstract
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
    protected static $_modelRepositoryByName = array();
    
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
    
    protected static $_dbTableClass = 'AxIr_Model_DbTable_Classes';
    protected static $_modelClass = 'AxIr_Model_Class';
    protected static $_columnMap = array(
       'id'=>'id',
       'name'=>'name'
    );
    
    /**
     * find a class row by name
     * @param string $name
     * @return Zend_Db_Table_Row 
     */
    protected function _getClassRowByName($name)
    {
        $class = $this->_getClass();
        if ($class::$_rowRepository)
        {
            foreach ($class::$_rowRepository as $row)
            {
                if ($row->name === $name)
                {
                    return $row;
                }
            }
        }
        $table = $this->_getDbTable();
        $select = $table->select();
        $select->where('name = ?',$name);
        $row = $table->fetchRow($select);
        if ($row instanceof Zend_Db_Table_Row)
        {
            $class::$_rowRepository[$row->id] = $row;
            return $row;
        }
        return false;
    }
    
    /**
     * retrieve a single Class model by name
     * @param string $name
     * @return AxIr_Model_Class
     */
    public function getClassByName($name)
    {
        if (($model = $this->_getCachedClassByName($name)) !== false)
        {
            return $model;
        }
        $row = $this->_getClassRowByName($name);
        if (!$row)
        {
            return false;
        }
        $model = $this->createFromRow($row);
        $this->_cacheModel($model);
        return $model;
    }
    
    /**
     * retrieve a rowset of Classes with runs recorded at a given event
     * @param mixed $eventId
     * @return Zend_Db_Table_Rowset
     */
    protected function _getClassRowsWithRunsAtEvent($eventId)
    {
        $table = $this->_getDbTable();
        $select = $table->select();
        $select->joinInner('drivers', 'classes.id = drivers.class_id', array());
        $select->joinInner('runs', 'drivers.id = runs.driver_id', array());
        $select->where('drivers.event_id = ?', $eventId);
        $select->from('classes');
        $select->order('name ASC');
        $select->distinct(true);
        $rows = $table->fetchAll($select);
        return $rows;
    }
    
    /**
     * retrieve an array of Class models with runs at a given event
     * @param AxIr_Model_Event $event
     * @return array
     */
    public function getClassesWithRunsAtEvent(AxIr_Model_Event $event)
    {
        $rows = $this->_getClassRowsWithRunsAtEvent($event->id);
        $classes = array();
        foreach ($rows as $row)
        {
            if (($model = $this->_getCachedModel($row->id)) === false)
            {
                $model = $this->createFromRow($row);
                $this->_cacheModel($model);
            }
            $classes[] = $model;
        }
        return $classes;
    }
    
    /**
     * override the parent::_cacheModel method with special logic
     * that is particular to serving Class models. Class models are cached
     * both by primary key (as all services do with their respective models), 
     * and also by name.
     * @param AxIr_Model_Class $model 
     */
    protected function _cacheModel(AxIr_Model_Class $model) 
    {
        $class = $this->_getClass();
        $class::$_modelRepository[$model->id] = $model;
        self::$_modelRepositoryByName[$model->name] = $model;
    }
    
    /**
     * retrieve a Class model by name from cache if possible, otherwise
     * return false
     * @param string $prefix
     * @return AxIr_Model_Class|false 
     */
    protected function _getCachedClassByName($name)
    {
        if (array_key_exists($name, self::$_modelRepositoryByName))
        {
            return self::$_modelRepositoryByName[$name];
        }
        return false;
    }
}
