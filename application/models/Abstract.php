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
abstract class AxIr_Model_Abstract
{
    /**
     * repository of model data keyed by property name. model declarations
     * should set the initial value to a sensible default such as null
     * or perhaps an empty string
     * @var array
     */
    protected $_data = array();
    
    /**
     * magic getter. if property has a matching getter method, such as 
     * get{PropertyName}(), use it to retrieve the value, otherwise retrieve it
     * directly from this object's _data property
     * @param string $name
     * @return mixed 
     */
    public function __get($name)
    {
        $getter = 'get'.$name;
        if (method_exists($this, $getter))
        {
            return $this->$getter();
        }
        elseif (array_key_exists($name, $this->_data))
        {
            return $this->_data[$name];
        }
        throw new RuntimeException('Attempted to retrieve field with no getter');
    }
    
    /**
     * magic setter. if property has a matching setter method, such as 
     * set{PropertyName}(), use it to assign the value, otherwise assign it
     * directly into this object's _data property
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        $setter = 'set'.$name;
        if (method_exists($this, $setter))
        {
            $this->$setter($value);
            return $value;
        }
        elseif (array_key_exists($name, $this->_data))
        {
            $this->_data[$name] = $value;
            return $value;
        }
        throw new RuntimeException('Attempted to set non-existing field');
    }
    
//    reference implementation of a getter
//    public function getId()
//    {
//        return $this->_data['id'];
//    }
    
    
    protected function _getPropertyNames()
    {
        return array_keys($this->_data);
    }
    
    /**
     * set a model's values with an array of key/value pairs. since this
     * bypasses the _map and _unmap logic of the model's service, use only as
     * a last resort from trustworthy data, such as a validated/filtered form 
     * value, or a database record deemed trustworthy
     * @param array $array 
     */
    public function setFromArray(Array $array)
    {
        foreach ($this->_getPropertyNames() as $property)
        {
            if (array_key_exists($property, $array))
            {
                $this->_data[$property] = $array[$property];
            }
        }
    }
    
    /**
     * retrieve the primary key.
     * more often than not, my primary key is named id, and my getter
     * is getId(), so I'm just going to implement this here out of
     * "laziness"
     * "We will encourage you to develop the three great virtues of a 
     * programmer: laziness, impatience, and hubris." 
     * -- LarryWall, ProgrammingPerl 
     */
    public function getPrimary()
    {
        return $this->getId();
    }
    
    /**
     * retrieve all of the model's values in array format
     * @return array
     */
    public function toArray()
    {
        // avoids the php behavior of passing arrays by reference -- we wouldn't
        // want someone bypassing those setter methods, now would we ;)
        $array = array();
        foreach ($this->_data as $k => $v)
        {
            $array[$k] = $v;
        }
        return $array;
    }
}

?>
