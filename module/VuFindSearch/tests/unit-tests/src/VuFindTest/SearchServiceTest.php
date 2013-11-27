<?php

/**
 * Unit tests for search service.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @category VuFind2
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */

namespace VuFindTest;

use VuFindSearch\Service;
use VuFindSearch\ParamBag;
use VuFindSearch\Backend\Exception\BackendException;
use VuFindSearch\Response\AbstractRecordCollection;

use PHPUnit_Framework_TestCase as TestCase;

/**
 * Unit tests for search service.
 *
 * @category VuFind2
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class SearchServiceTest extends TestCase
{
    /**
     * Mock backend
     *
     * @var \VuFindSearch\Backend\BackendInterface
     */
    protected $backend = false;

    /**
     * Test retrieve action.
     *
     * @return void
     */
    public function testRetrieve()
    {
        $service = $this->getService();
        $backend = $this->getBackend();
        $response = 'fake';
        $params = new ParamBag(array('x' => 'y'));
        $backend->expects($this->once())->method('retrieve')
            ->with($this->equalTo('bar'), $this->equalTo($params))
            ->will($this->returnValue($response));
        $em = $service->getEventManager();
        $em->expects($this->at(0))->method('trigger')
            ->with($this->equalTo('pre'), $this->equalTo($backend));
        $em->expects($this->at(1))->method('trigger')
            ->with($this->equalTo('post'), $this->equalTo($response));
        $service->retrieve('foo', 'bar', $params);
    }

    /**
     * Test exception-throwing retrieve action.
     *
     * @return void
     * @expectedException VuFindSearch\Backend\Exception\BackendException
     * @expectedExceptionMessage test
     */
    public function testRetrieveException()
    {
        $service = $this->getService();
        $backend = $this->getBackend();
        $response = 'fake';
        $params = new ParamBag(array('x' => 'y'));
        $exception = new BackendException('test');
        $backend->expects($this->once())->method('retrieve')
            ->with($this->equalTo('bar'), $this->equalTo($params))
            ->will($this->throwException($exception));
        $em = $service->getEventManager();
        $em->expects($this->at(0))->method('trigger')
            ->with($this->equalTo('pre'), $this->equalTo($backend));
        $em->expects($this->at(1))->method('trigger')
            ->with($this->equalTo('error'), $this->equalTo($exception));
        $service->retrieve('foo', 'bar', $params);
    }

    /**
     * Test batch retrieve.
     *
     * @return void
     */
    public function testRetrieveBatch()
    {
        $service = $this->getService();
        $backend = $this->getBackend();
        $mockRecord = $this->getMock('VuFindSearch\Response\RecordInterface');
        $response1 = $this->getRecordCollection();
        $response1->expects($this->once())->method('add')
            ->with($this->equalTo($mockRecord));
        $response2 = $this->getRecordCollection();
        $response2->expects($this->once())->method('first')
            ->will($this->returnValue($mockRecord));
        $params = new ParamBag(array('x' => 'y'));
        $backend->expects($this->at(0))->method('retrieve')
            ->with($this->equalTo('bar'), $this->equalTo($params))
            ->will($this->returnValue($response1));
        $backend->expects($this->at(1))->method('retrieve')
            ->with($this->equalTo('baz'), $this->equalTo($params))
            ->will($this->returnValue($response2));
        $em = $service->getEventManager();
        $em->expects($this->at(0))->method('trigger')
            ->with($this->equalTo('pre'), $this->equalTo($backend));
        $em->expects($this->at(1))->method('trigger')
            ->with($this->equalTo('post'), $this->equalTo($response1));
        $service->retrieveBatch('foo', array('bar', 'baz'), $params);
    }

    // Internal API

    /**
     * Get a mock backend.
     *
     * @return \VuFindSearch\Backend\BackendInterface
     */
    protected function getBackend()
    {
        if (!$this->backend) {
            $this->backend = $this->getMock('VuFindSearch\Backend\BackendInterface');
        }
        return $this->backend;
    }

    /**
     * Generate a fake service.
     *
     * @return Service
     */
    protected function getService()
    {
        $em = $this->getMock('Zend\EventManager\EventManagerInterface');
        $service = $this->getMock('VuFindSearch\Service', array('resolve'));
        $service->expects($this->any())->method('resolve')
            ->will($this->returnValue($this->getBackend()));
        $service->setEventManager($em);
        return $service;
    }

    /**
     * Generate a fake record collection.
     *
     * @param string $id ID of record to include in collection.
     *
     * @return AbstractRecordCollection
     */
    protected function getRecordCollection()
    {
        return $this->getMock('VuFindSearch\Response\AbstractRecordCollection');
    }
}