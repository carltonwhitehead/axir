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
 * @property AxIr_Model_Event $event
 * @property AxIr_Model_Category $category
 * @property AxIr_Model_Class $class
 * @property string $number
 * @property string $name
 * @property string $car
 * @property string $carColor
 * @property string $bestTimeRaw
 * @property string $bestTimePax
 */
class AxIr_Model_Driver extends AxIr_Model_Abstract
{
    protected $_data = array(
        'id'=>null,
        'event'=>null,
        'category'=>null,
        'class'=>null,
        'number'=>null,
        'name'=>null,
        'car'=>null,
        'carColor'=>null,
        'bestTimeRaw'=>null,
        'bestTimePax'=>null,
    );
    
    public function getId()
    {
        return $this->_data['id'];
    }
    
    /**
     *
     * @return AxIr_Model_Event
     */
    public function getEvent()
    {
        return $this->_data['event'];
    }
    
    /**
     *
     * @return AxIr_Model_Category 
     */
    public function getCategory()
    {
        return $this->_data['category'];
    }
    
    /**
     * 
     * @return AxIr_Model_Class 
     */
    public function getClass()
    {
        return $this->_data['class'];
    }
    
    public function getNumber()
    {
        return $this->_data['number'];
    }
    
    public function getName()
    {
        return $this->_data['name'];
    }
    
    public function getCar()
    {
        return $this->_data['car'];
    }
    
    public function getCarColor()
    {
        return $this->_data['carColor'];
    }
    
    public function getBestTimeRaw()
    {
        return $this->_data['bestTimeRaw'];
    }
    
    public function getBestTimeRawFormatted() {
        $runService = new AxIr_Model_RunService();
        return $runService->formatTime($this->_data['bestTimeRaw']);
    }
    
    public function getBestTimePax()
    {
        return $this->_data['bestTimePax'];
    }
    
    public function getBestTimePaxFormatted() {
        $runService = new AxIr_Model_RunService();
        return $runService->formatTime($this->_data['bestTimePax']);
    }
    
    public function getUrl()
    {
        return '/events/driver/event-id/' .
                $this->getEvent()->getId() .
                '/driver-category-id/' .
                $this->getCategory()->getId() .
                '/driver-class-id/' .
                $this->getClass()->getId() .
                '/driver-number/' .
                $this->getNumber();
    }
}

