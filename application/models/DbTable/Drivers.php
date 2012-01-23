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
class AxIr_Model_DbTable_Drivers extends Zend_Db_Table_Abstract
{
    protected $_name = 'drivers';
    protected $_primary = 'id';
    
    protected $_referenceMap = array(
        'Event' => array(
            'columns' => 'event_id',
            'refTableClass' => 'AxIr_Model_DbTable_Events'
        ),
        'Category' => array(
            'columns' => 'category_id',
            'refTableClass' => 'AxIr_Model_DbTable_Categories'
        ),
        'Class' => array(
            'columns' => 'class_id',
            'refTableClass' => 'AxIr_Model_DbTable_Classes'
        ),
        'BestTimeRaw' => array(
            'columns' => 'best_time_raw_id',
            'refTableClass' => 'AxIr_Model_DbTable_Runs'
        ),
        'BestTimePax' => array(
            'columns' => 'best_time_pax_id',
            'refTableClass' => 'AxIr_Model_DbTable_Runs'
        )
    );
}

