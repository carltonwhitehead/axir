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
class AxIr_Parser_StateFileLine {

    /**
     * the string representing this line
     * @var string
     */
    private $_line = '';

    /**
     * query a state file line for value by key
     * @return string
     */
    public function __construct($line) {
        $this->_line = trim($line);
    }

    /**
     * local static cache of category prefix strings
     * @var array
     */
    private static $_categoryPrefixes = array();

    /**
     * retrieve a value from the line by its key
     * @param string $key
     * @return string
     */
    private function _parse($key) {
        $keyStartPos = strpos($this->_line, $key);
        if ($keyStartPos !== false) {
            $keyLength = strlen($key);
            $valueStartPos = $keyStartPos + $keyLength + 1; // +1 to account for the trailing _
            $valueStopPos = strpos($this->_line, '_', $valueStartPos);
            if ($valueStopPos !== false) {
                $valueLength = $valueStopPos - $valueStartPos;
                $value = substr($this->_line, $valueStartPos, $valueLength);
            } else {
                $value = substr($this->_line, $valueStartPos);
            }
        }
        else
            $value = '';
        $value = trim($value);
        return $value;
    }

    public function getRunNumber() {
        return $this->_parse('run');
    }

    public function getDriverCategory() {
        $categoryService = new AxIr_Model_CategoryService();
        if (!self::$_categoryPrefixes) {
            self::$_categoryPrefixes = $categoryService->getCategoryPrefixes();
        }
        $classString = strtoupper($this->_parse('class'));
        $category = null;
        foreach (self::$_categoryPrefixes as $categoryPrefix) {
            if (
                    $categoryPrefix !== ''
                    and substr($classString, 0, strlen($categoryPrefix)) == $categoryPrefix
            ) {
                $category = $categoryService->getCategoryByPrefix($categoryPrefix);
                break;
            }
        }
        if ($category === null) {
            $category = $categoryService->getCategoryByPrefix('');
        }
        return $category->prefix;
    }

    public function getDriverClass() {
        $classString = strtoupper($this->_parse('class'));
        if ($classString === '') {
            $message = 'Invalid state file line is missing class.';
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        $categoryPrefix = $this->getDriverCategory();
        if ($categoryPrefix != '') {
            $classString = str_replace($categoryPrefix, '', $classString);
        }
        return $classString;
    }

    public function getDriverNumber() {
        $number = $this->_parse('number');
        if ($number === '') {
            $message = 'Invalid state file line is missing driver number.';
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        return $number;
    }

    public function getTimeRaw() {
        $timeRaw = $this->_parse('tm');
        if ($timeRaw === '') {
            $message = 'Invalid state file line is missing raw time.';
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        return $timeRaw;
    }

    public function getPenalty() {
        return strtoupper($this->_parse('penalty'));
    }

    public function getDriverName() {
        $driverName = $this->_parse('driver');
        if ($driverName === '') {
            $message = 'Invalid state file line is missing driver name.';
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        return $driverName;
    }

    public function getCar() {
        return $this->_parse('car');
    }

    public function getCarColor() {
        return $this->_parse('cc');
    }

    public function getTimePax() {
        $timePax = strtoupper($this->_parse('paxed'));
        if ($timePax === '') {
            $message = 'Invalid state file line is missing pax time.';
            throw new AxIr_Parser_StateFileLine_Exception($message);
        }
        return $timePax;
    }

    public function getTimestamp() {
        return (int) $this->_parse('tod');
    }

    public function getDiff() {
        return $this->_parse('diff');
    }

    public function getDiffFromFirst() {
        return $this->_parse('diff1');
    }

}
