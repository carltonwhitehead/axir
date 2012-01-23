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
class AxIr_Controller_Plugin_Agreement extends Zend_Controller_Plugin_Abstract
{
    /**
     * in the preDispatch loop, determine whether to require agreement
     * @param Zend_Controller_Request_Abstract $request 
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $config = Zend_Controller_Front::getInstance()
                ->getParam('bootstrap')
                ->getOption('agreement');
        if ($config['require']) $this->requireAgreement($request);
        
    }
    
    /**
     * check the session to see if the agreement has been signed, and if not,
     * alter the current request to force the user into agreement
     * @param Zend_Controller_Request_Abstract $request 
     */
    protected function requireAgreement(Zend_Controller_Request_Abstract $request)
    {
        $session = new Zend_Session_Namespace('AxIr_Form_Agreement');
        if (!isset($session->agreed) or !$session->agreed)
        {
            $request->setControllerName('agreement');
            $request->setActionName('form');
        }
    }
}