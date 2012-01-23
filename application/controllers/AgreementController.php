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
class AgreementController extends Zend_Controller_Action
{
    /**
     *
     * @var Zend_Session_Namespace
     */
    protected $_session;
    public function init()
    {
        $this->_session = new Zend_Session_Namespace('AxIr_Form_Agreement');
    }

    public function formAction()
    {
        $form = new AxIr_Form_Agreement();
        if ($this->_request->isPost() and $form->isValid($this->_getAllParams()))
        {
            $this->_session->agreed = true;
            $this->_redirect('/events');
            return;
        }
        $this->view->form = $form;
    }


}



