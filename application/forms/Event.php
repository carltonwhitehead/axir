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
class AxIr_Form_Event extends Zend_Form
{
    const AFTERSUBMIT_GOTO_EVENT = 1;
    const AFTERSUBMIT_ADD_ANOTHER = 2;

    public function init()
    {
        $this->setAttrib('id', 'AxIr_Form_Event');
        $idElement = new Zend_Form_Element_Hidden('id', array(
            'ignore'=>true
        ));
        $idElement->removeDecorator('Label');
        $this->addElement($idElement);
        
        $stringTrimFilter = new Zend_Filter_StringTrim();
        
        $file = new Zend_Form_Element_Textarea('file', array(
            'label'=>'Path to State File',
            'required'=>true,
            'cols'=>20,
            'rows'=>5
        ));
        $file->addFilter($stringTrimFilter);
        $file->addDecorator(
                array('Wrapper' => 'HtmlTag'),
                array('tag' => 'div', 'class' => 'grid-5')
        );
        $file->setAttrib('class', 'grid-5');
        $file->setAttrib('style', 'width: 80%');
        $this->addElement($file);
        
        $label = new Zend_Form_Element_Text('label', array(
            'label'=>'Event Name',
            'required'=>true
        ));
        $label->addFilter($stringTrimFilter);
        $label->addDecorator(
                array('Wrapper' => 'HtmlTag'),
                array('tag' => 'div', 'class' => 'grid-5')
        );
        $label->setAttrib('class', 'grid-4');
        $this->addElement($label);
        
        $date = new Zend_Form_Element_Text('date', array(
            'label'=>'Date (YYYY-MM-DD)',
            'required'=>true
        ));
        $dateValidator = new Zend_Validate_Date();
        $date->addValidator($dateValidator);
        $date->addFilter($stringTrimFilter);
        $date->addDecorator(
                array('Wrapper' => 'HtmlTag'),
                array('tag' => 'div', 'class' => 'grid-4')
        );
        $date->setAttrib('class', 'grid-4');
        $this->addElement($date);
        
        $conePenalty = new Zend_Form_Element_Text('coneSeconds', array(
            'label'=>'Cone Penalty (seconds)',
            'required'=>true,
            'value'=>2
        ));
        $conePenaltyNumeric = new Zend_Validate_Float();
        $conePenalty->addValidator($conePenaltyNumeric);
        $conePenalty->addDecorator(
                array('Wrapper' => 'HtmlTag'),
                array('tag' => 'div', 'class' => 'grid-5')
        );
        $conePenalty->setAttrib('class', 'grid-4');
        $this->addElement($conePenalty);
        
        $this->addDisplayGroup($this->getElements(), 'event-details');
        $displayGroup = $this->getDisplayGroup('event-details');
        $displayGroup->setLegend('Event Details');
        $displayGroup->removeDecorator('DtDdWrapper');
        
        $displayGroup->addDecorator(
                array('Wrapper' => 'HtmlTag'),
                array('tag' => 'div', 'class' => 'grid-6')
        );
    }
    
    public function adaptForAdd()
    {
        $afterSubmit = new Zend_Form_Element_Select('afterSubmit', array(
            'label'=>'After Submit'
        ));
        $afterSubmit->setAttrib('options', array(
                self::AFTERSUBMIT_GOTO_EVENT => 'View Added Event',
                self::AFTERSUBMIT_ADD_ANOTHER => 'Add Another Event'
        ));
        $afterSubmit->addDecorator(
                array('Wrapper' => 'HtmlTag'),
                array('tag' => 'div', 'class' => 'grid-4')
        );
        $displayGroup = $this->getDisplayGroup('event-details');
        $displayGroup->addElement($afterSubmit);
        
        $this->_addSubmitButton('Add');
    }
    
    public function adaptForUpdate()
    {
        $this->_addSubmitButton('Update');
    }
    
    protected function _addSubmitButton($label)
    {
        $displayGroup = $this->getDisplayGroup('event-details');
        $submit = new Zend_Form_Element_Submit('submit', array(
            'ignore' => true,
            'label' => $label
        ));
        $submit->addDecorator(
                array('Wrapper' => 'HtmlTag'),
                array('tag' => 'div', 'class' => 'grid-4')
        );
        $displayGroup->addElement($submit);
    }
}

