<?php
/**
 * ExtendedIni translation loader Test Class
 *
 * PHP version 7
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
namespace VuFindTest\I18n\Translator\Loader;

use VuFind\I18n\Translator\Loader\ExtendedIni;

/**
 * ExtendedIni translation loader Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class ExtendedIniTest extends \VuFindTest\Unit\TestCase
{
    /**
     * @var string
     */
    protected $path = __DIR__ . '/../../../../../../fixtures/language';

    public function setUp()
    {
        $this->path = realpath($this->path);
    }

    /**
     * Test translations.
     *
     * @return void
     */
    public function testTranslations()
    {
        $pathStack = ["$this->path/base", "$this->path/overrides"];
        $loader = new ExtendedIni($pathStack);
        $result = $loader->load('en', null);
        $result->offsetUnset(ExtendedIni::TRACE);
        $this->assertEquals(
            [
                'blank_line' =>
                    html_entity_decode('&#x200C;', ENT_NOQUOTES, 'UTF-8'),
                'test1' => 'test one',
                'test2' => 'test two - override',
            ],
            (array)$result
        );
    }

    /**
     * Test fallback to a different language.
     *
     * @return void
     */
    public function testFallback()
    {
        $pathStack = [
            realpath(__DIR__ . '/../../../../../../fixtures/language/base'),
        ];
        $loader = new ExtendedIni($pathStack, 'en');
        $result = $loader->load('fake', null);
        $result->offsetUnset(ExtendedIni::TRACE);
        $this->assertEquals(
            [
                'blank_line' =>
                    html_entity_decode('&#x200C;', ENT_NOQUOTES, 'UTF-8'),
                'test1' => 'test one',
                'test2' => 'test two',
                'test3' => 'test three',
            ],
            (array)$result
        );
    }

    /**
     * Test fallback to the same language.
     *
     * @return void
     */
    public function testFallbackToSelf()
    {
        $pathStack = ["$this->path/base"];
        $loader = new ExtendedIni($pathStack, 'fake');
        $result = $loader->load('fake', null);
        $this->assertEquals(
            [
                'test3' => 'test three',
                ExtendedIni::TRACE => "$this->path/base/fake.ini"
            ],
            (array)$result
        );
    }

    /**
     * Test file with self as parent.
     *
     * @return void
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /^Invalid @parent_ini value in/
     */
    public function testSelfAsParent()
    {
        $pathStack = ["$this->path/base"];
        $loader = new ExtendedIni($pathStack);
        $loader->load('self-parent', null);
    }

    /**
     * Test file with a chain of parents.
     *
     * @return void
     */
    public function testParentChain()
    {
        $pathStack = ["$this->path/base"];
        $loader = new ExtendedIni($pathStack);
        $result = $loader->load('child2', null);
        $trace = array_map(function ($name) {
            return "$this->path/base/$name.ini";
        }, ['fake', 'child1', 'child2']);

        $this->assertEquals(
            [
                'test1' => 'test 1',
                'test2' => 'test 2',
                'test3' => 'test three',
                ExtendedIni::TRACE => implode(":", $trace)
            ],
            (array)$result
        );
    }

    /**
     * Test missing path stack.
     *
     * @return void
     *
     * @expectedException        Zend\I18n\Exception\InvalidArgumentException
     * @expectedExceptionMessage Ini file 'en.ini' not found
     */
    public function testMissingPathStack()
    {
        $loader = new ExtendedIni();
        $loader->load('en', null);
    }
}
