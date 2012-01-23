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
class ConfigController extends Zend_Controller_Action
{

    /**
     * @var Zend_Controller_Action_Helper_FlashMessenger
     *
     *
     *
     */
    protected $_flashMessenger = null;

    /**
     * @var Zend_Acl
     *
     *
     *
     */
    protected $_acl = null;

    public function init()
    {
        $this->_acl = $this->getFrontController()
                ->getParam('bootstrap')
                ->getResource('Acl');
        $this->_prohibitRemoteConfig();
        $this->_flashMessenger = new Zend_Controller_Action_Helper_FlashMessenger();
    }

    protected function _prohibitRemoteConfig()
    {
        if (!$this->_acl->isAllowed('user','configuration'))
                die('Access denied');
    }

    public function indexAction()
    {
        $eventService = new AxIr_Model_EventService();
        $this->view->events = $eventService->getAllEventsByDateDesc();
        
        $this->view->messages = $this->_flashMessenger->getMessages();
    }

    public function addEventAction()
    {
        $form = new AxIr_Form_Event();
        $form->adaptForAdd();
        $eventService = new AxIr_Model_EventService();
        if ($this->_request->isPost() and $form->isValid($this->_getAllParams()))
        {
            $dateFormElement = $form->getElement('date');
            if ($eventService->getEventByDate($dateFormElement->getValue()))
            {
                $message = 'An event with this date already exists';
                $dateFormElement->addError($message);
            }
            $eventService->beginTransaction();
            $eventData = array(
                'file' => $form->getValue('file'),
                'date' => $form->getValue('date'),
                'label' => $form->getValue('label'),
                'coneSeconds' => $form->getValue('coneSeconds')
            );
            $event = $eventService->createFromArray($eventData);
            $eventService->setFileModified($event);
            $eventService->store($event);
            $eventService->commit();
            $eventService->beginTransaction();
            $eventService->cacheFromStateFile($event);
            $eventService->commit();
            $this->_flashMessenger->addMessage('Added '.$event->label);
            $this->_redirect('/config/event/event-id/'.$event->id);
            return;
        }
        $this->view->unknownStateFiles = $eventService->getUnknownStateFiles();
        $this->view->form = $form;
        if (!isset($this->view->messages))
        {
            $this->view->messages = $this->_flashMessenger->getMessages();
        }
    }

    public function eventAction()
    {
        if (($eventId = $this->_getParam('event-id')) !== null)
        {
            $eventService = new AxIr_Model_EventService();
            if (($event = $eventService->getByPrimary($eventId)) !== false)
            {
                $form = new AxIr_Form_Event();
                $form->adaptForUpdate();
                $form->populate(array(
                    'file' => $event->file,
                    'label' => $event->label,
                    'date' => $event->date->toString('YYYY-MM-dd'),
                    'coneSeconds' => $event->coneSeconds
                ));
                if 
                (
                        $this->_hasParam('file') 
                        and $this->_hasParam('label')
                        and $this->_hasParam('date')
                        and $this->_hasParam('coneSeconds')
                        and $form->isValid($this->_getAllParams())
                )
                {
                    $needsResync = false;
                    if (($event->file !== $form->getValue('file'))
                            or ($event->coneSeconds !== $form->getValue('coneSeconds'))
                    )
                    {
                        $needsResync = true;
                    }
                    $event->setFromArray(array(
                        'file' => $form->getValue('file'),
                        'label' => $form->getValue('label'),
                        'date' => new Zend_Date($form->getValue('date')),
                        'coneSeconds' => $form->getValue('coneSeconds')
                    ));
                    $eventService->store($event);
                    if ($needsResync)
                    {
                        $eventService->beginTransaction();
                        $eventService->cacheFromStateFile($event);
                        $eventService->commit();
                        $this->_flashMessenger->addMessage('Resynchronized with the state file');
                    }
                    $this->_flashMessenger->addMessage('Updated event');
                    $this->_redirect('/config/event/event-id/'.$event->id);
                }
                $this->view->messages = $this->_flashMessenger->getMessages();
                $this->view->form = $form;
                $this->view->event = $event;
            }
            else
            {
                $message = 'Unable to retrieve requested event ID';
                throw new Zend_Controller_Exception($message);
            }
        }
        else
        {
            $message = 'This action requires an event ID but none was requested';
            throw new Zend_Controller_Exception($message);
        }
    }

    public function resyncEventAction()
    {
        if (($eventId = $this->_getParam('event-id')) !== null)
        {
            $eventService = new AxIr_Model_EventService();
            if (($event = $eventService->getByPrimary($eventId)) !== false)
            {
                $eventService->beginTransaction();
                $eventService->cacheFromStateFile($event);
                $eventService->commit();
                
                $this->_flashMessenger->addMessage('Resynchronized with the state file');
                
                $this->_redirect('/config/event/event-id/'.$event->id);
                return;
            }
        }
    }

    public function deleteEventAction()
    {
        if (($eventId = $this->_getParam('event-id')) !== null)
        {
            $eventService = new AxIr_Model_EventService();
            if (($event = $eventService->getByPrimary($eventId)) !== false)
            {
                $eventService->delete($event);
                
                $this->_flashMessenger->addMessage('Deleted event');
                
                $this->_redirect('/config');
                return;
            }
        }
    }


}






