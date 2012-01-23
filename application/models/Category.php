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
 * @property int|null $id
 * @property string $name
 * @property string $label
 * @property string $prefix
 * @property bool $isRaw
 * @property bool $isPax
 * @property bool $timeType
 */
class AxIr_Model_Category extends AxIr_Model_Abstract
{
    protected $_data = array(
        'id'=>null,
        'name'=>null,
        'label'=>null,
        'prefix'=>null,
        'isRaw'=>null,
        'isPax'=>null,
        'timeTime'=>null
    );
    
    public function getId()
    {
        return $this->_data['id'];
    }
    
    public function getName()
    {
        return $this->_data['name'];
    }
    
    public function getLabel()
    {
        return $this->_data['label'];
    }
    
    public function getPrefix()
    {
        return $this->_data['prefix'];
    }
    
    public function getIsRaw()
    {
        return $this->_data['isRaw'];
    }
    
    public function getIsPax()
    {
        return $this->_data['isPax'];
    }
    
    public function getTimeType()
    {
        return ($this->_data['isRaw']) ? 'raw' : 'pax';
    }
}

