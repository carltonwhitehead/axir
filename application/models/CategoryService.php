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
class AxIr_Model_CategoryService extends AxIr_Model_ServiceAbstract
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
    protected static $_modelRepositoryByPrefix = array();
    
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
    protected static $_dbTableClass = 'AxIr_Model_DbTable_Categories';
    protected static $_modelClass = 'AxIr_Model_Category';
    protected static $_columnMap = array(
        'id'=>'id',
        'name'=>'name',
        'label'=>'label',
        'prefix'=>'prefix',
        'isRaw'=>'is_raw',
        'isPax'=>'is_pax'
    );
    
    protected function _mapIsRaw($value)
    {
        return (bool) $value;
    }
    protected function _unmapIsRaw($value)
    {
        return (int) $value;
    }
    
    protected function _mapIsPax($value)
    {
        return (bool) $value;
    }
    
    protected function _unmapIsPax($value)
    {
        return (int) $value;
    }
    
    /**
     * retrieve a category row by its prefix
     * @param string $prefix
     * @return Zend_Db_Table_Row|false 
     */
    protected function _getCategoryRowByPrefix($prefix)
    {
        $class = $this->_getClass();
        if ($class::$_rowRepository)
        {
            foreach ($class::$_rowRepository as $row)
            {
                if ($row['prefix'] === $prefix)
                {
                    return $row;
                }
            }
        }
        $table = $class::_getDbTable();
        $select = $table->select();
        $select->where('prefix = ?',$prefix);
        $row = $table->fetchRow($where);
        if ($row instanceof Zend_Db_Table_Row)
        {
            $class::$_rowRepository[$row->id] = $row;
            return $row;
        }
        return false;
    }
    
    /**
     * retrieve all category rows with runs at a given event id
     * @param mixed $eventId
     * @return Zend_Db_Table_Rowset 
     */
    protected function _getCategoryRowsWithRunsAtEvent($eventId)
    {
        $adapter = $this->_getDbTable()->getAdapter();
        $sql = 'select distinct categories.* ' .
                'from categories ' .
                'inner join drivers on categories.id = drivers.category_id ' .
                'inner join runs on drivers.id = runs.driver_id '.
                'where drivers.event_id = ? ' .
                'order by categories.label asc';
        $rows = $adapter->fetchAll($sql, array($eventId));
        return $rows;
    }
    
    /**
     * retrieve category models with runs at a given event
     * @param AxIr_Model_Event $event
     * @return array 
     */
    public function getCategoriesWithRunsAtEvent(AxIr_Model_Event $event)
    {
        $rows = $this->_getCategoryRowsWithRunsAtEvent($event->id);
        $categories = array();
        foreach ($rows as $row)
        {
            if (($model = $this->_getCachedModel($row['id'])) === false)
            {
                $model = $this->createFromArray($row);
                $this->_cacheModel($model);
            }
            $categories[] = $model;
        }
        return $categories;
    }
    
    /**
     * retrieve a single category model by its prefix
     * @param string $prefix
     * @return AxIr_Model_Category
     */
    public function getCategoryByPrefix($prefix)
    {
        if (($model = $this->_getCachedCategoryByPrefix($prefix)) !== false)
        {
            return $model;
        }
        $row = $this->_getCategoryRowByPrefix($prefix);
        if (!$row)
        {
            return false;
        }
        $model = $this->createFromRow($row);
        $this->_cacheModel($model);
        return $model;
    }
    
    /**
     * get a single-dimensional array of category prefix strings
     * @return array
     */
    public function getCategoryPrefixes()
    {
        $rows = $this->_getAllRows();
        $prefixes = array();
        foreach ($rows as $row)
        {
            $prefixes[] = $row->prefix;
        }
        return $prefixes;
    }
    
    /**
     * override the parent::_cacheModel method with special logic
     * that is particular to serving Category models. Category models are cached
     * both by primary key (as all services do with their respective models), 
     * and also by prefix.
     * @param AxIr_Model_Category $model 
     */
    protected function _cacheModel(AxIr_Model_Category $model) 
    {
        $class = $this->_getClass();
        $class::$_modelRepository[$model->id] = $model;
        self::$_modelRepositoryByPrefix[$model->prefix] = $model;
    }
    
    /**
     * retrieve a Category model by prefix from cache if possible, otherwise
     * return false
     * @param string $prefix
     * @return AxIr_Model_Category|false 
     */
    protected function _getCachedCategoryByPrefix($prefix)
    {
        if (array_key_exists($prefix, self::$_modelRepositoryByPrefix))
        {
            return self::$_modelRepositoryByPrefix[$prefix];
        }
        return false;
    }
}
