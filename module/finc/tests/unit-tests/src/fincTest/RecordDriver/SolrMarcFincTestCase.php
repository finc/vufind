<?php
/**
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * Copyright (C) Leipzig University Library 2015.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  finc
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @author   Frank Morgner  <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace fincTest\RecordDriver;
use VuFindTest\Unit\TestCase as VuFindTestCase;
use finc\RecordDriver\SolrMarcFinc as SolrMarcFincDriver;
/**
 * SolrMarcTestCase
 *
 * @category VuFind
 * @package  finc
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @author   Frank Morgner  <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class SolrMarcFincTestCase extends VuFindTestCase
{
    /**
     * SolrMarcDriver
     *
     * @var SolrMarcFincDriver
     */
    protected $driver;

    /**
     * Initialize driver with fixture
     *
     * @param String $file File
     *
     * @return void
     */
    public function initialize($file)
    {
        if (!$this->driver) {
            $this->driver = new SolrMarcFincDriver();
            $fixture = $this->getFixtureData($file);
            $this->driver->setRawData($fixture);
        }
    }

    /**
     * Get record fixture
     *
     * @param String $file File
     *
     * @return array
     */
    protected function getFixtureData($file)
    {
        return json_decode(
            file_get_contents(realpath(FINC_TEST_FIXTURES . '/' . $file)),
            true
        );
    }
}