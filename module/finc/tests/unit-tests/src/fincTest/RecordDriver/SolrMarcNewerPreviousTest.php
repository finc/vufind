<?php
/**
* PHP version 5
*
* Copyright (C) Villanova University 2010.
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
* @package  FincTest
* @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
* @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
* @link     http://www.swissbib.org
*/
namespace fincTest\RecordDriver;
/**
 * SolrMarcNewerPreviousTest
 *
 * @category VuFind
 * @package  FincTest
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class SolrMarcNewerPreviousTest extends SolrMarcFincTestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        $this->initialize('misc/0002795235.json');
    }

    /**
     * TestNewerTitles
     *
     * @return void
     */
    public function testNewerTitles()
    {
        $newer = $this->driver->getNewerTitles(false);
        $this->assertInternalType('array', $newer);
        $this->assertEquals('Tanz', $newer[0]['text']);
        $this->assertEquals('Forts.', $newer[0]['pretext']);
        $this->assertEquals('317975862', $newer[0]['record_id']);
    }

    /**
     * TestPreviousTitles
     *
     * @return void
     */
    public function testPreviousTitles()
    {
        $previous = $this->driver->getPreviousTitles(false);
        $this->assertInternalType('array', $previous);
        $this->assertEquals(
            'Ballett international, Tanz aktuell', $previous[0]['text']
        );
        $this->assertEquals('Vorg.', $previous[0]['pretext']);
        $this->assertEquals('038873095', $previous[0]['record_id']);
    }

    /**
     * TestGetUniqueId
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testGetUniqueId()
    {
        $id = $this->driver->getUniqueID();
        $this->assertEquals('0002795235', $id);
    }
}

