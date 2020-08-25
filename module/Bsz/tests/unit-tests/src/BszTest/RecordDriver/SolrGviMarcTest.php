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

use Bsz\Config\Client;
use Bsz\RecordDriver\SolrGviMarc;
use PHPUnit\Framework\TestCase;

class SolrGviMarcTest extends TestCase
{
    protected function getDefaultRecord()
    {
        $config = $this->getClient();
        $record = new \Bsz\RecordDriver\SolrGviMarc($config);
        $fixture = $this->loadRecordFixture('repetitorium.json');
        $record->setRawData($fixture['response']['docs'][0]);
        return $record;
    }

    protected function getClient()
    {
       $config = [
            'Site' => [
                'isil' => 'DE-666,DE-667',
                'website' => 'https://www.example.com',
                'website_google' => 'https://www.google.com',
                'url' => 'foo.bar.com'
            ],
            'System' => [],
            'OpenUrl' => [],
            'Footer' => [],
            'Switches' => [
                'isil_session' => false
            ],
            'FooterLinks' => []
       ];
       return $client = new Client($config);
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
        $this->assertEquals($driver->getFormats(), ['Book']);

        $this->assertFalse($driver->isJournal());
        $this->assertFalse($driver->isArticle());
        $this->assertFalse($driver->isMonographicSerial());
        $this->assertFalse($driver->isElectronic());
        $this->assertFalse($driver->isFree());
        $this->assertFalse($driver->isNewspaper());

        $this->assertTrue($driver->isBook());
    }

    public function testConsortium()
    {
        $driver = $this->getDefaultRecord();
        $this->assertIsString($driver->getConsortium());
        $this->assertEquals($driver->getConsortium(), 'GBV, SWB');
    }

    public function testField924NumericKeys()
    {
        $driver = $this->getDefaultRecord();
        $f924 = $driver->getField924();
        $keys = array_keys($f924);
        foreach($keys as $key) {
            $this->assertTrue(is_numeric($key));
        }
    }

    public function testField924CheckArrayContent()
    {
        $driver = $this->getDefaultRecord();
        $f924 = $driver->getField924();

        foreach ($f924 as $field) {
            $this->assertTrue(array_key_exists('isil', $field));
            $this->assertTrue(strlen($field['ill_indicator']) == 1);
        }

    }

    public function testPublicationDetails()
    {
        $driver = $this->getDefaultRecord();
        $publications = $driver->getPublicationDetails();
        foreach ($publications as $publication) {
            $place = $publication->getPlace();
            $this->assertFalse(strpos($place, '['));
        }
    }

}
