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

namespace BszTest;

use Bsz\Config\Client;
use PHPUnit\Framework\TestCase;
use Zend\Config\Config;

class ClientTest extends TestCase
{


    protected function getBasicConfig()
    {
        return [
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
            ]
        ];
    }

    protected function getClient($config)
    {
        return $client = new Client($config);
    }

    public function testClient()
    {
        $config = $this->getBasicConfig();
        $client = $this->getClient($config);
        $this->assertEquals(get_class($client), 'Bsz\Config\Client');
    }

    public function testIsils()
    {
        $config = $this->getBasicConfig();
        $client = $this->getClient($config);
        $this->assertIsArray($client->getIsils());
    }

    public function testWebsite()
    {
        $config = $this->getBasicConfig();
        $client = $this->getClient($config);
        $this->assertEquals($client->getWebsite(),'https://www.example.com');
        $this->assertEquals($client->getWebsite('google'), 'https://www.google.com');

    }

    public function testTag()
    {
        $config = $this->getBasicConfig();
        $client = $this->getClient($config);
        $this->assertEquals($client->getTag(),'foo');
        $config['Site']['url'] = 'https://bar.boss.bsz-bw.de';
        $client = $this->getClient($config);
        $this->assertEquals($client->getTag(),'bar');
    }








}
