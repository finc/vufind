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
namespace Bsz\RecordTab;

use Bsz\Config\Libraries as LibConf;
use VuFind\RecordTab\AbstractBase;

/**
 * Description of Libraries
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Libraries extends AbstractBase
{
    /**
     *
     * @var Bsz\Config\Libraries
     */
    protected $libraries;
    /**
     * @var array
     */
    protected $f924;
    /**
     * @var bool
     */
    protected $visible;
    /**
     * @var bool
     */
    protected $swbonly;

    public function __construct(LibConf $libraries, $visible = true, $swbonly = false)
    {
        $this->accessPermission = 'access.LibrariesViewTab';
        $this->libraries = $libraries;
        $this->visible = (bool)$visible;
        $this->swbonly = $swbonly;
    }

    public function getDescription()
    {
        return 'Libraries';
    }

    /**
     * Tab is shown if there is at least one 924 in MARC.
     * @return boolean
     */
    public function isActive()
    {
        $parent = parent::isActive();
        if (null === $this->f924) {
            $this->f924 = $this->driver->tryMethod('getField924');
        }
        if ($this->swbonly) {
            foreach ($this->f924 as $k => $field) {
                if (isset($field['c']) && strtoupper($field['c']) !== 'BSZ') {
                    unset($this->f924[$k]);
                }
            }
        }
        if ($parent && $this->f924) {
            return true;
        }
        return false;
    }

    public function getContent()
    {
        if (null === $this->f924) {
            $this->f924 = $this->driver->tryMethod('getField924');
        }
        if (is_array($this->f924)) {
            $libraries = $this->libraries->getByIsils(array_keys($this->f924));
            foreach ($libraries as $library) {
                $this->f924[$library->getIsil()]['name'] = $library->getName();
                $this->f924[$library->getIsil()]['homepage'] = $library->getHomepage();
                $this->f924[$library->getIsil()]['adisurl'] = $library->getaDISURl();
            }
        }
        return $this->f924;
    }

    public function isVisible()
    {
        return $this->visible;
    }
}
