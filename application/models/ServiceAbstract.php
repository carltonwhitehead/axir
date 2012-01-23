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
abstract class AxIr_Model_ServiceAbstract
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
    
    /**
     * subclasses must declare their associated dbTable class
     * @var string
     */
    protected static $_dbTableClass = null;
    
    /**
     * subclasses must declare their associated model class
     * (normally that would be the shortname of the subclass
     * minus "service")
     * @todo automatically declare this in the constructor
     * @var string
     */
    protected static $_modelClass = '';
    
    /**
     * key-value pair mapping
     * modelProperty => column_name
     * @var array
     */
    protected static $_columnMap = array();
    
    public static $abstractStoreElapsed = 0.0;
    public static $abstractStorePreSave = 0.0;
    public static $abstractStorePostSave = 0.0;
    public static $abstractCreateFromRow = 0.0;
    
    protected function _getClass()
    {
        return get_class($this);
    }
    
    public function __construct()
    {
        // the below probably belongs in unit tests, but meh
        $class = $this->_getClass();
        $missing = false;
        if ($class::$_dbTableClass === null) $missing = '_dbTableClass';
        elseif ($class::$_modelClass === null) $missing = '_modelClass';
        elseif (!$class::$_columnMap) $missing = '_columnMap';
        if ($missing)
        {
            $message = 'Missing '.$missing.' declaration in '.$class;
            throw new RuntimeException ($message);
        }
    }
    
    /**
     * get the dbTable from static cache
     * @return Zend_Db_Table
     */
    protected function _getDbTable()
    {
        $class = $this->_getClass();
        if ($class::$_dbTable === null)
        {
            $toInstantiate = $class::$_dbTableClass;
            $class::$_dbTable = new $toInstantiate();
        }
        return $class::$_dbTable;
    }
    
    public function beginTransaction()
    {
        return $this->_getDbTable()->getAdapter()->beginTransaction();
    }
    
    public function commit()
    {
        return $this->_getDbTable()->getAdapter()->commit();
    }
    
    /**
     * find a row by primary key value
     * @param integer $id
     * @return Zend_Db_Table_Row|null
     */
    protected function _getRowByPrimary($primary)
    {
        $class = $this->_getClass();
        if (isset($class::$_rowRepository[$primary]))
        {
            return $class::$_rowRepository[$primary];
        }
        $row = $this->_getDbTable()->find($primary)->current();
        if ($row instanceof Zend_Db_Table_Row)
        {
            $class::$_rowRepository[$primary] = $row;
            return $row;
        }
        return false;
    }
    
    /**
     * fetch all rows
     * @return Zend_Db_Table_Rowset
     */
    protected function _getAllRows()
    {
        $class = $this->_getClass();
        if ($class::$_fetchedAll)
        {
            return $class::$_rowRepository;
        }
        $table = $this->_getDbTable();
        $primary = $table->info('primary');
        $rows = $table->fetchAll();
        foreach ($rows as $row)
        {
            $class::$_rowRepository[$row->id] = $row;
        }
        $class::$_fetchedAll = true;
        return $rows;
    }
    
    /**
     *
     * @param Zend_Db_Table_Row $row
     * @param AxIr_Model_Abstract|null $model 
     * @todo refactor this to leverate createFromArray
     * @return AxIr_Model_Abstract
     */
    public function createFromRow(Zend_Db_Table_Row $row, $model = null)
    {
        $class = $this->_getClass();
        $model = $this->_instantiateModel($model);
        $rowData = $row->toArray();
        $modelData = array();
        foreach ($class::$_columnMap as $property => &$column)
        {
            if (array_key_exists($column, $rowData))
            {
                $mapMethod = '_map'.$property;
                $modelData[$property] = (method_exists($this, $mapMethod))
                    ? $this->$mapMethod($rowData[$column]) : $rowData[$column];
            }
        }
        $model->setFromArray($modelData);
        return $model;
    }
    
    /**
     *
     * @param array $array
     * @param AxIr_Model_Abstract|null $model
     * @return AxIr_Model_Abstract 
     */
    public function createFromArray(Array $array, $model = null)
    {
        $class = $this->_getClass();
        $model = $this->_instantiateModel($model);
        $modelData = array();
        foreach ($class::$_columnMap as $property => $column)
        {
            if (array_key_exists($property, $array))
            {
                $mapMethod = '_map'.$property;
                $modelData[$property] = (method_exists($this, $mapMethod))
                        ? $this->$mapMethod($array[$property]) : $array[$property];
            }
        }
        $model->setFromArray($modelData);
        return $model;
    }
    
    /**
     * instantiate a given model according to the specification of the sub-class
     * @param AxIr_Model_Abstract $model
     * @return AxIr_Model_Abstract 
     */
    protected function _instantiateModel($model = null)
    {
        $class = $this->_getClass();
        $modelClass = $class::$_modelClass;
        if (!($model instanceof $modelClass))
        {
            $model = new $modelClass();
        }
        return $model;
    }
    
    /**
     * take a model and persist it "magically"
     * @param AxIr_Model_Abstract $model 
     */
    public function store(AxIr_Model_Abstract $model)
    {
        $class = $this->_getClass();
        $serviceClass = get_class($model).'Service';
        if (!($this instanceof $serviceClass))
        {
            $message = 'Model of type '.get_class($model).' passed into ' .
                    ' wrong service object, '.$class;
            throw new RuntimeException($message);
        }
        $table = $this->_getDbTable();
        $data = array();
        $row = $table->find($model->getPrimary())->current();
        if ($row === null)
        {
            $row = $table->createRow();
        }
        foreach ($class::$_columnMap as $property => $column)
        {
            $unmapMethod = '_unmap'.$property;
            $row->$column = (method_exists($this, $unmapMethod))
                ? $this->$unmapMethod($model->$property) : $model->$property;
        }
        try
        {
            $row->save();
        }
        catch (Exception $e)
        {
            Zend_Debug::dump($e);
            die();
        }
        $class::$_rowRepository[$row->id] = $row;
        $model = $this->createFromRow($row, $model);
    }
    
    /**
     * delete records for the given model from the backing store
     * @param AxIr_Model_Abstract $model 
     */
    public function delete(AxIr_Model_Abstract $model)
    {
        $class = $this->_getClass();
        $serviceClass = get_class($model).'Service';
        if (!($this instanceof $serviceClass))
        {
            $message = 'Model of type '.get_class($model).' passed into ' .
                    ' wrong service object, '.$class;
            throw new RuntimeException($message);
        }
        $table = $this->_getDbTable();
        $primary = current($table->info('primary')); // apologies
        $table->delete($primary.' = '.$model->getPrimary());
    }
    
    /**
     * retrieve a single model by its primary key
     * @param int primary key of model to retrieve
     * @return AxIr_Model_Abstract|false
     */
    public function getByPrimary($primary)
    {
        if (($model = $this->_getCachedModel($primary)) !== false)
        {
            return $model;
        }
        $row = $this->_getRowByPrimary($primary);
        if (!$row) return false;
        $model = $this->createFromRow($row);
        $this->_cacheModel($model);
        return $model;
    }
    
    /**
     * retrieve an array of all models in no particular order, filter, or 
     * grouping
     * @return mixed
     */
    public function getAll()
    {
        $rows = $this->_getAllRows();
        $models = array();
        foreach ($rows as $row)
        {
            if (($model = $this->_getCachedModel($row->id)) !== false)
            {
                $models[] = $model;
            }
            else
            {
                $model = $this->createFromRow($row);
                $this->_cacheModel($model);
                $models[] = $model;
            }
        }
        return $models;
    }
    
    /**
     * cache a given model in the service subclass
     * @param AxIr_Model_Abstract $model 
     */
    protected function _cacheModel(AxIr_Model_Abstract $model)
    {
        $class = $this->_getClass();
        $class::$_modelRepository[$model->getPrimary()] = $model;
    }
    
    /**
     * retrieve a given model from the service subclass, or return false if
     * not found
     * @param mixed $primary
     * @return AxIr_Model_Abstract 
     */
    protected function _getCachedModel($primary)
    {
        $class = $this->_getClass();
        if (array_key_exists($primary, $class::$_modelRepository))
        {
            return $class::$_modelRepository[$primary];
        }
        return false;
    }
}
