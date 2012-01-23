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
class AxIr_Form_Agreement extends Zend_Form
{
    public function init()
    {
        $agreements = array(
            'I will not pester any event workers about anything I see here.',
            'These results have not been audited.',
            'These results are unofficial.',
            'The official results may differ substantially.',
            'I will disconnect from this wireless after I view the results.'
        );
        foreach($agreements as $key => $agreement)
        {
            $element = new Zend_Form_Element_Radio('agreement'.$key, array(
                'required' => true,
                'label' => $agreement,
                'multiOptions' => array('I agree.')
            ));
            $this->addElement($element);
        }
        $submit = new Zend_Form_Element_Submit('submit', array(
            'label' => 'Show me the coneage!'
        ));
        $this->addElement($submit);
    }
}
