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
/**
 * @property-read string $runNumber
 * @property-read string $driverCategory
 * @property-read string $driverClass
 * @property-read string $driverNumber
 * @property-read string $timeRaw
 * @property-read string $penalty
 * @property-read string $driverName
 * @property-read string $car
 * @property-read string $carColor
 * @property-read string $timePax
 * @property-read string $timestamp
 * @property-read string $diff
 * @property-read string $diffFromFirst
 */
class AxIr_Parser_StateFileLine
{
    /**
     * the string representing this line
     * @var string
     */
    protected $_line = '';
    
    /**
     * query a state file line for value by key
     * @return string
     */
    public function __construct($line)
    {
        $this->_line = trim($line);
        
    }
    
    /**
     * local static cache of category prefix strings
     * @var array
     */
    protected static $_categoryPrefixes = array();
    
    /**
     * magic getter
     * @param type $key
     * @return type 
     */
    public function __get($key)
    {
        $getter = '_get'.$key;
        if (!method_exists($this, $getter))
        {
            $message = 'Attempting to get property with no matching getter: ' .
                    $key;
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        $result = $this->$getter();
        return $result;
    }
    
    /**
     * retrieve a value from the line by its key
     * @param string $key
     * @return string 
     */
    protected function _parse($key)
    {
        $keyStartPos = strpos($this->_line, $key);
        if ($keyStartPos !== false)
        {
            $keyLength = strlen($key);
            $valueStartPos = $keyStartPos+$keyLength+1; // +1 to account for the trailing _
            $valueStopPos = strpos($this->_line, '_', $valueStartPos);
            if ($valueStopPos !== false)
            {
                $valueLength = $valueStopPos-$valueStartPos;
                $value = substr($this->_line, $valueStartPos, $valueLength);
            }
            else
            {
                $value = substr($this->line, $valueStartPos);
            }
        }
        else $value = '';
        return $value;
    }
    
    protected function _getRunNumber()
    {
        return $this->_parse('run');
    }
    
    protected function _getDriverCategory()
    {
        $categoryService = new AxIr_Model_CategoryService();
        if (!self::$_categoryPrefixes)
        {
            self::$_categoryPrefixes = $categoryService->getCategoryPrefixes();
        }
        $classString = $this->_parse('class');
        foreach (self::$_categoryPrefixes as $categoryPrefix)
        {
            if (
                    $categoryPrefix !== '' 
                    and substr($classString, 0, strlen($categoryPrefix)) == $categoryPrefix
            )
            {
                $category = $categoryService->getCategoryByPrefix($categoryPrefix);
                break;
            }
        }
        if (!isset($category))
        {
            $category = $categoryService->getCategoryByPrefix('');
        }
        return $category->prefix;
    }
    
    protected function _getDriverClass()
    {
        $classString = $this->_parse('class');
        if ($classString === '')
        {
            $message = 'Invalid state file line is missing class.';
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        $categoryPrefix = $this->driverCategory;
        if ($categoryPrefix != '')
        {
            $classString = str_replace($categoryPrefix, '', $classString);
        }
        return $classString;
    }
    
    protected function _getDriverNumber()
    {
        $number = $this->_parse('number');
        if ($number === '')
        {
            $message = 'Invalid state file line is missing driver number.';
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        return $number;
    }
    
    protected function _getTimeRaw()
    {
        $timeRaw = $this->_parse('tm');
        if ($timeRaw === '')
        {
            $message = 'Invalid state file line is missing raw time.';
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        return $timeRaw;
    }
    
    protected function _getPenalty()
    {
        return $this->_parse('penalty');
    }
    
    protected function _getDriverName()
    {
        $driverName = $this->_parse('driver');
        if ($driverName === '')
        {
            $message = 'Invalid state file line is missing driver name.';
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        return $driverName;
    }
    
    protected function _getCar()
    {
        return $this->_parse('car');
    }
    
    protected function _getCarColor()
    {
        return $this->_parse('cc');
    }
    
    protected function _getTimePax()
    {
        $timePax = $this->_parse('paxed');
        if ($timePax === '')
        {
            $message = 'Invalid state file line is missing pax time.';
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        return $timePax;
    }
    
    protected function _getTimestamp()
    {
        return (int) $this->_parse('tod');
    }
    
    protected function _getDiff()
    {
        return $this->_parse('diff');
    }
    
    protected function _getDiffFromFirst()
    {
        return $this->_parse('diff1');
    }
}
