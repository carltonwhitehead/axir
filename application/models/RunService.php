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
class AxIr_Model_RunService extends AxIr_Model_ServiceAbstract
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
    protected static $_dbTableClass = 'AxIr_Model_DbTable_Runs';
    protected static $_modelClass = 'AxIr_Model_Run';
    protected static $_columnMap = array(
        'id'=>'id',
        'event'=>'event_id',
        'driver'=>'driver_id',
        'number'=>'number',
        'timeRaw'=>'time_raw',
        'timePax'=>'time_pax',
        'penalty'=>'penalty',
        'timeRawWithPenalty'=>'time_raw_with_penalty',
        'timePaxWithPenalty'=>'time_pax_with_penalty',
        'diff'=>'diff',
        'diff'=>'diff_from_first',
        'timestamp'=>'timestamp'
    );

    public static $elapsedTime = 0.0;

    protected function _mapEvent($value)
    {
        if ($value instanceof AxIr_Model_Event)
        {
            $event = $value;
        }
        elseif (is_numeric($value))
        {
            $eventService = new AxIr_Model_EventService();
            $event = $eventService->getByPrimary($value);
        }
        else
        {
            $message = 'Unable to map Event to Run';
            throw new AxIr_Model_Service_Exception($message);
        }
        return $event;
    }

    protected function _unMapEvent(AxIr_Model_Event $value)
    {
        return $value->id;
    }

    protected function _mapDriver($value)
    {
        if ($value instanceof AxIr_Model_Driver)
        {
            $driver = $value;
        }
        elseif (is_numeric($value))
        {
            $driverService = new AxIr_Model_DriverService();
            $driver = $driverService->getByPrimary($value);
        }
        else
        {
            $message = 'Unable to map Driver to Run';
            throw new AxIr_Model_Service_Exception($message);
        }
        return $driver;
    }

    protected function _unMapDriver(AxIr_Model_Driver $value)
    {
        return $value->getId();
    }

    protected function _mapTimeRaw($value)
    {
        return (float) $value;
    }

    protected function _mapTimePax($value)
    {
        return (float) $value;
    }

    protected function _mapTimestamp($value)
    {
        if ($value instanceof Zend_Date)
        {
            $timestamp = $value;
        }
        else
        {
            try
            {
                $timestamp = new Zend_Date($value);
            }
            catch (Exception $e)
            {
                $message = 'Unable to map Timestamp to Run: (' .
                        $e->getMessage() . ')';
                throw new AxIr_Model_Service_Exception($message);
            }
        }
        return $timestamp;
    }

    protected function _unmapTimestamp(Zend_Date $value)
    {
        if ($value instanceof Zend_Date)
        {
            $timestamp = $value->toString('YYYY-MM-dd HH:mm:ss');
        }
        else
        {
            $message = 'Unable to unmap Timestamp';
            throw new AxIr_Model_Service_Exception($message);
        }
        return $timestamp;
    }

    /**
     * construct a run model for an event by parsing values for the run from the
     * given state file line
     * @param AxIr_Model_Event $event
     * @param AxIr_Parser_StateFileLine $line
     * @return AxIr_Model_Run
     */
    public function createFromStateFileLine(AxIr_Model_Event $event, AxIr_Parser_StateFileLine $line)
    {
        $driverService = new AxIr_Model_DriverService();
        $classService = new AxIr_Model_ClassService();
        $categoryService = new AxIr_Model_CategoryService();
        $driverCategory = $categoryService->getCategoryByPrefix($line->getDriverCategory());
        $driverClass = $classService->getClassByName($line->getDriverClass());
        $driverNumber = $line->getDriverNumber();
        if (
                $driverCategory instanceof AxIr_Model_Category
                and $driverClass instanceof AxIr_Model_Class
        )
        {
            $driver = $driverService->getDriverByEventCategoryClassNumber(
                    $event,
                    $driverCategory,
                    $driverClass,
                    $driverNumber
            );
        }
        if (!isset($driver) or !($driver instanceof AxIr_Model_Driver))
        {
            $driver = $driverService->createFromStateFileLine($event, $line);
            $driverService->store($driver);
        }
        $runTimeRaw = $line->getTimeRaw();
        $runTimePax = $line->getTimePax();
        $runPenalty = $line->getPenalty();
        $runTimestamp = $line->getTimestamp();
        $run = $this->createFromArray(array(
            'event' => $event,
            'driver' => $driver,
            'number' => $line->getRunNumber(),
            'timeRaw' => $runTimeRaw,
            'timePax' => ($runPenalty !== 'DNF')
                ? $runTimePax : $runPenalty,
            'penalty' => $runPenalty,
            'timeRawWithPenalty' => $this->_getTimeWithPenalty
            (
                $event,
                $runTimeRaw,
                $runPenalty
            ),
            'timePaxWithPenalty' => ($runPenalty !== 'DNF')
                ? $runTimePax : AxIr_Model_Run::PENALTY_TIME_DNF,
            'diff' => $line->getDiff(),
            'diffFromFirst' => $line->getDiffFromFirst(),
            'timestamp' => $runTimestamp
        ));
        return $run;
    }

    /**
     * process a raw or pax time with any applicable penalty and return the
     * adjusted time
     * @param AxIr_Model_Event $event
     * @param type $time
     * @param type $penalty
     * @return float
     */
    protected function _getTimeWithPenalty(AxIr_Model_Event $event, $time, $penalty)
    {
        switch ($penalty)
        {
            case '':
                $timeWithPenalty = $time;
                break;
            case 'DNF':
                $timeWithPenalty = AxIr_Model_Run::PENALTY_TIME_DNF;
                break;
            case 'RRN':
                throw new AxIr_Exception_ReRun('Reruns dont count');
            default:
                $timeWithPenalty = $time + ($event->coneSeconds * $penalty);
        }
        return $timeWithPenalty;
    }

    /**
     * retrieve runs rows for a given event, optionally sorted by a given order
     * (sqlite will sort by primary key by default)
     * @param AxIr_Model_Event $event
     * @param string $order
     * @return Zend_Db_Table_Rowset
     */
    protected function _getRunsRowsByEvent(AxIr_Model_Event $event, $order = null)
    {
        $table = $this->_getDbTable();
        $select = $table->select();
        $select->where('event_id = ?', $event->id);
        if (in_array($order, array(
            'time_raw','time_pax','time_raw_with_penalty','time_pax_with_penalty'
        )))
        {
            $select->order($order.' ASC');
        }
        return $table->fetchAll($select);
    }

    /**
     * retrieve an array of all run models on file for the given event
     * @param AxIr_Model_Event $event
     * @return array
     */
    public function getAllRunsFromEvent(AxIr_Model_Event $event)
    {
        $rows = $this->_getRunsRowsByEvent($event);
        if ($rows->count() == 0) return false;
        $runs = array();
        foreach ($rows as $row)
        {
            if (($runModel = $this->_getCachedModel($row->id)) !== false)
            {
                $runs[] = $runModel;
            }
            else
            {
                $runModel = $this->createFromRow($row);
                $this->_cacheModel($runModel);
                $runs[] = $runModel;
            }
        }
        return $runs;
    }

    /**
     * retrieve the x newest rows of runs at a given event
     * @param mixed $eventId
     * @param mixed $x
     * @return Zend_Db_Table_Rowset
     */
    protected function _getNewestXRunsRowsAtEvent($eventId, $x)
    {
        $table = $this->_getDbTable();
        $select = $table->select();
        $select->where('event_id = ?', $eventId);
        $select->order('timestamp DESC');
        $select->limit($x);
        return $table->fetchAll($select);
    }

    /**
     * retrieve an array of the newest x run models at a given event
     * @param AxIr_Model_Event $event
     * @param int $x
     * @return array
     */
    public function getNewestXRunsAtEvent(AxIr_Model_Event $event, $x)
    {
        $rows = $this->_getNewestXRunsRowsAtEvent($event->id, $x);
        if ($rows->count() == 0) return false;
        $runs = array();
        foreach ($rows as $row)
        {
            if (($runModel = $this->_getCachedModel($row->id)) !== false)
            {
                $runs[] = $runModel;
            }
            else
            {
                $runModel = $this->createFromRow($row);
                $this->_cacheModel($runModel);
                $runs[] = $runModel;
            }
        }
        return $runs;
    }

    /**
     * retrieve all runs rows for a given event by a given driver
     * @param mixed $eventId
     * @param mixed $driverId
     * @return Zend_Db_Table_Rowset
     */
    protected function _getRunsRowsAtEventByDriver($eventId, $driverId)
    {
        $table = $this->_getDbTable();
        $select = $table->select();
        $select->where('event_id = ?', $eventId);
        $select->where('driver_id = ?', $driverId);
        $select->order("number ASC");
        $rows = $table->fetchAll($select);
        return $rows;
    }

    /**
     * retrieve all run models for a given event by a given driver
     * @param AxIr_Model_Event $event
     * @param AxIr_Model_Driver $driver
     * @return array
     */
    public function getRunsAtEventByDriver(AxIr_Model_Event $event, AxIr_Model_Driver $driver)
    {
        $rows = $this->_getRunsRowsAtEventByDriver($event->id, $driver->id);
        $runs = array();
        foreach ($rows as $row)
        {
            if (($run = $this->_getCachedModel($row->id)) === false)
            {
                $run = $this->createFromRow($row);
                $this->_cacheModel($run);
            }
            $runs[] = $run;
        }
        return $runs;
    }

    /**
     * find the total runs on file for a given event
     * @param AxIr_Model_Event $event
     * @return mixed
     */
    public function getTotalRunsFromEvent(AxIr_Model_Event $event)
    {
        $table = $this->_getDbTable();
        $sql = 'SELECT COUNT(id) FROM runs WHERE event_id = ?';
        return $table->getAdapter()->fetchOne($sql, array($event->id));
    }

    /**
     * find the sum of cone penalties on file for a given event
     * @param AxIr_Model_Event $event
     * @return mixed
     */
    public function getTotalConesFromEvent(AxIr_Model_Event $event)
    {
        $table = $this->_getDbTable();
        $sql = 'SELECT SUM(penalty) FROM runs WHERE event_id = ? ' .
                'AND penalty != "DNF"';
        return $table->getAdapter()->fetchOne($sql, array($event->id));
    }

    /**
     * utility to convert a run time to a standard format
     * @param int|string $time
     * @return string
     */
    public function formatTime($time)
    {
        if (is_numeric($time))
        {
            $time = number_format($time, 3);
        }
        return $time;
    }
}

