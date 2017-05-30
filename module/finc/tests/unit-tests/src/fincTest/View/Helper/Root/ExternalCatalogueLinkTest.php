<?php
/**
 * External Catalogue Link Test Class
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2017.
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
 * @category Finc
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
namespace FincTest\View\Helper\Root;

use finc\View\Helper\Root\ExternalCatalogueLink,
    Zend\Config\Config;

/**
 * External Catalogue Link Test Class
 *
 * @category Finc
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class ExternalCatalogueLinkTest extends \VuFindTest\Unit\ViewHelperTestCase
{

    /**
     * Test checkSupportedRecordRules() to see if it accounts for record driver
     * class.
     *
     * @return void
     */
    public function testGetLinks()
    {
        //fwrite(STDERR, print_r($driver, true));
        $externalCatalogue
            = $this->getExternalCatalogueLink(
            [],
            $this->getFixture('testexternallink1.json', 'externallink')
        )->__invoke($this->getMockDriver());

        $links = $externalCatalogue->getLinks();
        $expectedArray = [
            [
                "desc" => "DE-D13",
                "url" => "http://webopac.skd.museum/libero/WebopacOpenURL.cls?ACTION=DISPLAY&RID=265768233"
            ],
            [
                "desc" => "DE-14",
                "url" => "http://katalogbeta.slub-dresden.de/id/0008964455/"
            ]
        ];
        $this->assertEquals(json_encode($expectedArray), json_encode($links));
        $externalCatalogue
            = $this->getExternalCatalogueLink(
            [],
            $this->getFixture('testexternallink1.json', 'externallink')
        )->__invoke(
            $this->getMockDriver(
                'VuFind\RecordDriver\SolrDefault',
                '0008964455',
                '265768233',
                '79',
                ['DE-D13', 'DE-540']
            )
        );
        $links = $externalCatalogue->getLinks();
        fwrite(STDERR, print_r($links, true));
        $expectedArray = [
            [
                "desc" => "DE-540",
                "url" => "http://194.94.197.6/libero/WebopacOpenURL.cls?ACTION=DISPLAY&LANG=DE&RID=265768233"
            ]
        ];
        $this->assertEquals(json_encode($expectedArray), json_encode($links));
    }

    /**
     * Get mock driver that returns an openURL.
     *
     * @param string $class Class to mock
     * @param string $id Finc id from getUniqueID
     * @param string $record_id Record id from getRID
     * @param string $source_id Source id from getSourceID
     * @param array $institutions Institutions array from getInstitutions
     *
     * @return \VuFind\RecordDriver\SolrDefault
     */
    protected function getMockDriver(
        $class = 'VuFind\RecordDriver\SolrDefault',
        $id = '0008964455',
        $record_id = '265768233',
        $source_id = "0",
        $institutions = ["DE-14", "DE-D13"]
    )
    {
        $driver = $this->getMockBuilder($class)
            ->disableOriginalConstructor()->getMock();
        $driver->expects($this->any())->method('getUniqueID')
            ->will($this->returnValue($id));
        $driver->expects($this->any())->method('getSourceID')
            ->will($this->returnValue($source_id));
        $driver->expects($this->any())
            ->method('tryMethod')
            ->withConsecutive(
                [$this->equalTo('getInstitutions')],
                [$this->equalTo('getRID')],
                [$this->equalTo('getSourceID')]
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnValue($institutions),
                $this->returnValue($record_id),
                $this->returnValue($source_id)
            );
        return $driver;
    }


    /**
     * Get the fixtures for testing OpenUrlRules
     *
     * @param string $fixture filename of the fixture to load
     * @param string $type type of fixture to load
     *
     * @return mixed|null
     */
    protected function getFixture($fixture, $type = 'misc')
    {
        if ($fixture) {
            $file = realpath(
                __DIR__ .
                '/../../../../../../../tests/fixtures/' . $type . '/' . $fixture
            );
            if (!is_string($file) || !file_exists($file) || !is_readable($file)) {
                throw new \InvalidArgumentException(
                    sprintf('Unable to load fixture file: %s ', $fixture)
                );
            }
            return json_decode(file_get_contents($file), true);
        }
        return null;
    }

    /**
     * Get the object to test
     *
     * @param array $config Configuration settings (optional)
     * @param array $rules JSON-decoded array containing rules (optional)
     *
     * @return ExternalCatalogueLink
     */
    protected function getExternalCatalogueLink($config = [], $rules = null)
    {
        if (null === $rules) {
            $json = __DIR__
                . '/../../../../../../../../../config/vufind/'
                . 'ExternalCatalogueLinks.json';
            $rules = json_decode(file_get_contents($json), true);
        }
        $externalCatalogueLink
            = new ExternalCatalogueLink(new Config($config), $rules);
        return $externalCatalogueLink;
    }

}