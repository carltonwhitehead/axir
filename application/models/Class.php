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
 * @property string $name
 */
class AxIr_Model_Class extends AxIr_Model_Abstract
{
    protected $_data = array(
        'id'=>null,
        'name'=>null
    );
    
    public function getId()
    {
        return $this->_data['id'];
    }
    
    public function getName()
    {
        return $this->_data['name'];
    }
    
    public function hasRecurringCategoryPrefix(AxIr_Model_Event $event)
    {
        /* this is a hack to prevent RT*RT classes from appearing
         * in the class/category list
         */
        $hasRecurringCategoryPrefix = false;
        $name = $this->getName();
        $cs = new AxIr_Model_CategoryService();
        $categories = $cs->getCategoriesWithRunsAtEvent($event);
        foreach ($categories as $category) {
            $prefix = $category->getPrefix();
            if ($prefix !== '' and strpos($name, $prefix) !== false) {
                $hasRecurringCategoryPrefix = true;
                echo "<!-- Name: $name hasPrefix: $prefix -->\n";
                break;
            }
        }
        return $hasRecurringCategoryPrefix;
    }
}
