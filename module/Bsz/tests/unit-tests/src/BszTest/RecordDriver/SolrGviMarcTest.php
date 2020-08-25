<?php
/*
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

namespace BszTest\RecordDriver;

use Bsz\RecordDriver\SolrGviMarc;
use PHPUnit\Framework\TestCase;

class SolrGviMarcTest extends TestCase
{
    protected function getDefaultRecord()
    {
        $config = new \Zend\Config\Config([]);
        $record = new \Bsz\RecordDriver\SolrGviMarc($config);
        $fixture = $this->loadRecordFixture('repetitorium.json');
        $record->setRawData($fixture['response']['docs'][0]);
        return $record;
    }

    /**
     * Load a fixture file.
     *
     * @param string $file File to load from fixture directory.
     *
     * @return array
     */
    protected function loadRecordFixture($file)
    {
        return json_decode(
            file_get_contents(
                realpath(
                    VUFIND_PHPUNIT_MODULE_PATH . '/fixtures/solr/' . $file
                )
            ),
            true
        );
    }


    public function testFormat()
    {
        $driver = $this->getDefaultRecord();
        $this->assertA($driver->getFormats(), ['Book']);

    }

}
