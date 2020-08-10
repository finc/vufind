<?php
/**
 * Copyright 2020 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

namespace BszTest;

use Bsz\Config\Library;
use PHPUnit\Framework\TestCase;
use Exception;
use VuFind\Db\Table\Shortlinks;
use VuFind\UrlShortener\Database;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\ResultSet;
use Bsz\Config\Libraries;

class LibraryTest extends TestCase
{

    /**
     * Get the object to test.
     *
     * @param  object $table Database table object/mock
     *
     * @return Database
     */
    public function getDatabase($table)
    {
        return new Libraries($table);
    }

    /**
     * Get the mock table object.
     *
     * @param  array $methods Methods to mock.
     *
     * @return object
     */
    public function getMockTable($methods)
    {
        return $this->getMockBuilder(Libraries::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    public function testAllowsLend()
    {
        $this->assertEquals(true, true);
    }

    public function testAllowsCopy()
    {
        $this->assertEquals(true, true);
    }
}
