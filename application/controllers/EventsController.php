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
class EventsController extends Zend_Controller_Action
{

    /**
     * @var AxIr_Model_Event
     *
     */
    protected $_event = null;

    public function init()
    {
        if (($eventId = $this->_getParam('event-id')) !== null)
        {
            $eventService = new AxIr_Model_EventService();
            if (($this->_event = $eventService->getByPrimary($eventId)) !== false)
            {
                if ($this->_event->needsResync())
                {
                    $eventService->beginTransaction();
                    $eventService->cacheFromStateFile($this->_event);
                    $eventService->store($this->_event);
                    $eventService->commit();
                }
            }
            else
            {
                $message = 'Unable to retrieve requested event ID';
                throw new Zend_Controller_Exception($message);
            }
        }
        elseif (!in_array($this->getRequest()->getActionName(), array(
            'index'
        )))
        {
            $message = 'This action requires an event ID but none was requested';
            throw new Zend_Controller_Exception($message);
        }
    }

    public function indexAction()
    {
        $eventService = new AxIr_Model_EventService();
        $this->view->events = $eventService->getAllEventsByDateDesc();
    }

    public function detailAction()
    {
        
        $categoryService = new AxIr_Model_CategoryService();
        $categories = $categoryService->getCategoriesWithRunsAtEvent($this->_event);
        $classService = new AxIr_Model_ClassService();
        $classes = $classService->getClassesWithRunsAtEvent($this->_event);
        $driverService = new AxIr_Model_DriverService();
        $drivers = $driverService->getDriversAtEvent($this->_event);
        $driversTotal = count($drivers);
        $driverFirsts = $driverService->getDriverNameFirstLetters($this->_event);
        $runService = new AxIr_Model_RunService();
        $runsTotal = $runService->getTotalRunsFromEvent($this->_event);
        $conesTotal = $runService->getTotalConesFromEvent($this->_event);
        $this->view->assign(array(
            'event'=>$this->_event,
            'categories'=>$categories,
            'classes'=>$classes,
            'drivers'=>$drivers,
            'driverFirsts'=>$driverFirsts,
            'driversTotal'=>$driversTotal,
            'runsTotal'=>$runsTotal,
            'conesTotal'=>$conesTotal
        ));
    }

    protected function overallResultsByType($type)
    {
        $driverService = new AxIr_Model_DriverService();
        $classService = new AxIr_Model_ClassService();
        $categoryService = new AxIr_Model_CategoryService();

        $categories = $categoryService->getCategoriesWithRunsAtEvent($this->_event);
        $classes = $classService->getClassesWithRunsAtEvent($this->_event);
        $drivers = $driverService->getDriversAtEventByBestTime($this->_event, $type);
        
        $this->view->event = $this->_event;
        $this->view->drivers = $drivers;
    }

    public function rawResultsAction()
    {
        $this->overallResultsByType('raw');
    }

    public function paxResultsAction()
    {
        $this->overallResultsByType('pax');
    }

    public function categoryResultsAction()
    {
        if (($categoryId = $this->_getParam('category-id')) !== null)
        {
            $categoryService = new AxIr_Model_CategoryService();
            if (($category = $categoryService->getByPrimary($categoryId)) !== false)
            {
                $driverService = new AxIr_Model_DriverService();
                $drivers = $driverService->getDriversAtEventInCategoryByBestTime($this->_event, $category);

                $this->view->event = $this->_event;
                $this->view->category = $category;
                $this->view->drivers = $drivers;
            }
            else
            {
                $message = 'Unable to retrieve requested category ID';
                throw new Zend_Controller_Exception($message);
            }
        }
        else
        {
            $message = 'No category ID in request';
            throw new Zend_Controller_Exception($message);
        }
    }

    public function classResultsAction()
    {
        if (($classId = $this->_getParam('class-id')) !== null)
        {
            $classService = new AxIr_Model_ClassService();
            if (($class = $classService->getByPrimary($classId)) !== false)
            {
                $driverService = new AxIr_Model_DriverService();
                $drivers = $driverService->getDriversAtEventInClassByBestTime($this->_event, $class);

                $this->view->event = $this->_event;
                $this->view->class = $class;
                $this->view->drivers = $drivers;
            }
            else
            {
                $message = 'Unable to retrieve requested class ID';
                throw new Zend_Controller_Exception($message);
            }
        }
        else
        {
            $message = 'No class ID in request';
            throw new Zend_Controller_Exception($message);
        }
    }

    public function driverAction()
    {
        if (($driverClassId = $this->_getParam('driver-class-id')) !== null
            and ($driverCategoryId = $this->_getParam('driver-category-id')) !== null
            and ($driverNumber = $this->_getParam('driver-number')) !== null
        )
        {
            $categoryService = new AxIr_Model_CategoryService();
            if (($category = $categoryService->getByPrimary($driverCategoryId)) === false)
            {
                throw new Zend_Controller_Action_Exception('Invalid driver category id');
            }
            $classService = new AxIr_Model_ClassService();
            if (($class = $classService->getByPrimary($driverClassId)) === false)
            {
                throw new Zend_Controller_Action_Exception('Invalid driver class id');
            }
            
            $driverService = new AxIr_Model_DriverService();
            if (($driver = $driverService->getDriverByEventCategoryClassNumber(
                    $this->_event, 
                    $category, 
                    $class, 
                    $driverNumber)) !== false
            )
            {
                $runService = new AxIr_Model_RunService();
                $runs = $runService->getRunsAtEventByDriver($this->_event, $driver);

                $this->view->event = $this->_event;
                $this->view->driver = $driver;
                $this->view->runs = $runs;
            }
            else
            {
                $message = 'Unable to retrieve requested driver ID';
                throw new Zend_Controller_Exception($message);
            }
        }
        else
        {
            $message = 'No driver ID in request';
            throw new Zend_Controller_Exception($message);
        }
    }

    public function newestRunsAction()
    {
        $runService = new AxIr_Model_RunService();
        $runs = $runService->getNewestXRunsAtEvent($this->_event, 25);
        
        $this->view->event = $this->_event;
        $this->view->runs = $runs;
    }


}


