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
 * @property int|nulll $id
 * @property string $file
 * @property Zend_Date $fileModified
 * @property Zend_Date $date
 * @property string $label
 * @property string $coneSeconds
 * @property integer $totalConesHit
 */
class AxIr_Model_Event extends AxIr_Model_Abstract
{
    protected $_data = array(
        'id'=>null,
        'file'=>null,
        'fileModified'=>null,
        'date'=>null,
        'label'=>null,
        'coneSeconds'=>null
    );
    
    /**
     *
     * @return int
     */
    public function getId()
    {
        return $this->_data['id'];
    }
    
    /**
     *
     * @return string
     */
    public function getFile()
    {
        return $this->_data['file'];
    }
    
    /**
     *
     * @return Zend_Date
     */
    public function getFileModified()
    {
        return $this->_data['fileModified'];
    }
    
    /**
     *
     * @return Zend_Date
     */
    public function getDate()
    {
        return $this->_data['date'];
    }
    
    /**
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->_data['label'];
    }
    
    /**
     *
     * @return string
     */
    public function getConeSeconds()
    {
        return $this->_data['coneSeconds'];
    }
    
    /**
     * if the cached file modified date exceeds the minimum age for resync,
     * compare it to the actual file modified date.
     * 
     * ===== CONDITION => RETURN VALUE =====
     * Cached file modified date under minimum age for resync => false
     * Cached file modified date equal to file modified date => false
     * Cached file modified date not equal to file modified date => true
     * @return bool
     */
    public function needsResync()
    {
        $needsResync = false;
        $eventOptions = Zend_Controller_Front::getInstance()
                ->getParam('bootstrap')
                ->getOption('event');
        $resyncMinimumAgeSeconds = $eventOptions['resync']['minimumAgeSeconds'];
        $now = new Zend_Date();
        $cacheAgeVsMinimum = $this->fileModified->compare(
                $now->subSecond($resyncMinimumAgeSeconds)
        );
        if ($cacheAgeVsMinimum <= 0)
        {
            $realFileModifiedDate = new Zend_Date(filemtime($this->file));
            $cachedFileModifiedVsActual = $this->fileModified->compare($realFileModifiedDate);
            if ($cachedFileModifiedVsActual === -1)
            {
                $needsResync = true;
            }
        }
        return $needsResync;
    }
}
