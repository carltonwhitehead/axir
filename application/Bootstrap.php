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
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initAxIrNamespace()
    {
        $loader = Zend_Loader_Autoloader::getInstance();
        $loader->registerNamespace('AxIr_');
    }
    
    /**
     *
     * @return Zend_Navigation 
     */
    protected function _initNavigation()
    {
        $this->bootstrap('frontController');
        $this->bootstrap('View');
        $nav = new Zend_Navigation(array(
            array(
                'module'=>'default',
                'controller'=>'index',
                'action'=>'index',
                'label'=>'Instant Results',
                'pages'=>array(
                    array(
                        'module'=>'default',
                        'controller'=>'events',
                        'action'=>'index',
                        'label'=>'Pick an event',
                        'pages'=>array(
                            array(
                                'module'=>'default',
                                'controller'=>'events',
                                'action'=>'detail',
                                'label'=>'Event Details',
                                'reset_params'=>false,
                                'pages'=>array(
                                    array(
                                        'module'=>'default',
                                        'controller'=>'events',
                                        'action'=>'raw-results',
                                        'label'=>'Raw Results'
                                    ),
                                    array(
                                        'module'=>'default',
                                        'controller'=>'events',
                                        'action'=>'pax-results',
                                        'label'=>'PAX Results'
                                    ),
                                    array(
                                        'module'=>'default',
                                        'controller'=>'events',
                                        'action'=>'category-results',
                                        'label'=>'Category Results'
                                    ),
                                    array(
                                        'module'=>'default',
                                        'controller'=>'events',
                                        'action'=>'class-results',
                                        'label'=>'Class Results'
                                    ),
                                    array(
                                        'module'=>'default',
                                        'controller'=>'events',
                                        'action'=>'driver',
                                        'label'=>'Driver Results'
                                    ),
                                    array(
                                        'module'=>'default',
                                        'controller'=>'events',
                                        'action'=>'newest-runs',
                                        'label'=>'Newest Runs'
                                    )
                                )
                            )
                        )
                    
                    ),
                    array(
                        'module'=>'default',
                        'controller'=>'config',
                        'action'=>'index',
                        'label'=>'Configuration',
                        'resource'=>'configuration',
                        'pages'=>array(
                            array(
                                'module'=>'default',
                                'controller'=>'config',
                                'action'=>'add-event',
                                'label'=>'Add an Event'
                            ),
                            array(
                                'module' => 'default',
                                'controller' => 'config',
                                'action' => 'event',
                                'label' => 'Event'
                            )
                        )
                    ),
                    array(
                        'module'=>'default',
                        'controller'=>'agreement',
                        'action'=>'form',
                        'label'=>'Agreement',
                        'visible'=>false
                    )
                )
            )
        ));
        Zend_Registry::set('Zend_Navigation',$nav);
        return $nav;
    }
    
    /**
     *
     * @return Zend_Acl 
     */
    protected function _initAcl()
    {
        $acl = new Zend_Acl();
        $acl->addRole('user-local');
        $acl->addRole('user-remote');
        if (substr($_SERVER['REMOTE_ADDR'],0,4) === '127.')
        {
            $acl->addRole('user', array('user-local'));
        }
        else
        {
            $acl->addRole('user', array('user-remote'));
        }
        $acl->addResource('configuration');
        $acl->allow('user-local','configuration');
        $configControllerOptions = $this->getOption('configController');
        if ($configControllerOptions['allowFromAny'] === 'true')
        {
            $acl->allow('user-remote','configuration');
        }
        $nav = $this->getResource('Navigation');
        Zend_View_Helper_Navigation::setDefaultAcl($acl);
        Zend_View_Helper_Navigation::setDefaultRole('user');
        return $acl;
    }
    
    /**
     *
     * @return Zend_Db
     */
    protected function _initDb()
    {
        $resourceOptions = $this->getOption('resources');
        $dbOptions = $resourceOptions['db'];
        $db = Zend_Db::factory($dbOptions['adapter'], $dbOptions['params']);
        Zend_Db_Table::setDefaultAdapter($db);
        $frontendOptions = array(
            'automatic_serialization' => true
        );
        $backendOptions  = array(
            'cache_dir' => APPLICATION_PATH . '/../data/cache/'
        );

        $cache = Zend_Cache::factory(
                
                'Core',
                'File',
                $frontendOptions,
                $backendOptions
        );
        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        return $db;
    }
    
    protected function _initControllerPlugins()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new AxIr_Controller_Plugin_Agreement());
    }
    
    protected function _initSession()
    {
        Zend_Session::start();
    }
}

