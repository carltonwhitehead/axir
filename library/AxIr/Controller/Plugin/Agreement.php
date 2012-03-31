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
    protected $_config;
    /**
     * in the preDispatch loop, determine whether to require agreement
     * @param Zend_Controller_Request_Abstract $request 
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_config = Zend_Controller_Front::getInstance()
                ->getParam('bootstrap')
                ->getOption('agreement');
        if ($this->_agreementIsRequired()
                and !$this->_hostMayBypassAgreement())
        {
            $this->_requireAgreement($request);
        }
    }
    
    protected function _agreementIsRequired()
    {
        $required = true;
        if (array_key_exists('require', $this->_config)
                and $this->_config['require'] === 'false')
        {
            $required = false;
        }
        return $required;
    }
    
    protected function _hostMayBypassAgreement()
    {
        $acl = new Zend_Acl();
        $acl->addRole($_SERVER['REMOTE_ADDR']);
        $acl->addResource('agreement');
        if (array_key_exists('hostsAllowedToBypass', $this->_config)
                and $this->_config['hostsAllowedToBypass'])
        {
            $hostsAllowedToBypass = $this->_config['hostsAllowedToBypass'];
            foreach ($hostsAllowedToBypass as $hostAllowedToBypass)
            {
                if (!$acl->hasRole($hostAllowedToBypass))
                {
                    $acl->addRole($hostAllowedToBypass);
                }
            }
            $acl->allow($hostsAllowedToBypass, 'agreement', 'bypass');
        }
        $acl->addRole('host', array($_SERVER['REMOTE_ADDR']));
        return $acl->isAllowed('host', 'agreement', 'bypass');
    }
    
    /**
     * check the session to see if the agreement has been signed, and if not,
     * alter the current request to force the user into agreement
     * @param Zend_Controller_Request_Abstract $request 
     */
    protected function _requireAgreement(Zend_Controller_Request_Abstract $request)
    {
        $session = new Zend_Session_Namespace('AxIr_Form_Agreement');
        if (!isset($session->agreed) or !$session->agreed)
        {
            $request->setControllerName('agreement');
            $request->setActionName('form');
        }
    }
}