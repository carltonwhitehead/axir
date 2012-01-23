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
 * @property int|null $id primary key
 * @property AxIr_Model_Event $event
 * @property AxIr_Model_Driver $driver
 * @property string $number
 * @property string $timeRaw
 * @property string $timePax
 * @property string $penalty
 * @property string $timeRawWithPenalty
 * @property string $timePaxWithPenalty
 * @property string $diff
 * @property string $diffFromFirst
 * @property Zend_Date $timestamp
 * 
 */
class AxIr_Model_Run extends AxIr_Model_Abstract
{
    protected $_data = array(
        'id'=>null,
        'event'=>null,
        'driver'=>null,
        'number'=>null,
        'timeRaw'=>null,
        'timePax'=>null,
        'penalty'=>null,
        'timeRawWithPenalty'=>null,
        'timePaxWithPenalty'=>null,
        'diff'=>null,
        'diffFromFirst'=>null,
        'timestamp'=>null
    );
    
    /**
     *
     * @return int
     */
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
    
    public function setEvent(AxIr_Model_Event $event)
    {
        $this->_data['event'] = $event;
    }
    
    /**
     *
     * @return AxIr_Model_Driver
     */
    public function getDriver()
    {
        return $this->_data['driver'];
    }
    
    public function setDriver(AxIr_Model_Driver $driver)
    {
        $this->_data['driver'] = $driver;
    }
    
    /**
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->_data['number'];
    }
    
    /**
     *
     * @return string
     */
    public function getTimeRaw()
    {
        $runService = new AxIr_Model_RunService();
        return $runService->formatTime($this->_data['timeRaw']);
    }
    
    /**
     *
     * @return string
     */
    public function getTimePax()
    {
        $runService = new AxIr_Model_RunService();
        return $runService->formatTime($this->_data['timePax']);
    }
    
    public function getPenalty()
    {
        return $this->_data['penalty'];
    }
    
    /**
     *
     * @return string
     */
    public function getTimeRawWithPenalty()
    {
        $runService = new AxIr_Model_RunService();
        return $runService->formatTime($this->_data['timeRawWithPenalty']);
    }
    
    /**
     *
     * @return string
     */
    public function getTimePaxWithPenalty()
    {
        $runService = new AxIr_Model_RunService();
        return $runService->formatTime($this->_data['timePaxWithPenalty']);
    }
    
    /**
     *
     * @return string
     */
    public function getDiff()
    {
        return $this->_data['diff'];
    }
    
    /**
     *
     * @return string
     */
    public function getDiffFromFirst()
    {
        return $this->_data['diffFromFirst'];
    }
    
    public function setTimestamp(Zend_Date $timestamp)
    {
        $this->_data['timestamp'] = $timestamp;
    }
}
