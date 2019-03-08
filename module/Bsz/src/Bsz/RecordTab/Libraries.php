<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Bsz\RecordTab    ;


/**
 * Description of Libraries
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Libraries extends \VuFind\RecordTab\AbstractBase
{
    /**
     *
     * @var \Bsz\Config\Libraries
     */
    protected $libraries;
    protected $f924;
    protected $visible;
    
    public function __construct(\Bsz\Config\Libraries $libraries, $visible = true) 
    {       
        $this->libraries = $libraries;    
        $this->visible = (bool)$visible;
    }
    
    public function getDescription()
    {
        return 'Libraries';
    }
    
    /**
     * This tab is active if record's network is SWB or K10plus and there exists entries from fiedls 924
     * @return boolean
     */
    public function isActive()
    {
        if ($this->driver->getNetwork() == 'SWB' || $this->driver->getNetwork() == 'K10plus' ) {
            $this->f924 = $this->driver->getField924();
            if (count($this->f924) > 0) {
                return true;                
            }            
        } 
        return false;        
    }
    
    public function getContent()
    {
        $libraries = $this->libraries->getByIsils(array_keys($this->f924));
        foreach ($libraries as $library) {
            $this->f924[$library->getIsil()]['name'] = $library->getName();    
        
        }
        return $this->f924;        
    }
    
    public function isVisible()
    {
        return $this->visible;
    }


}
