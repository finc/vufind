<?php
namespace BszTest;

use Bsz\FormatMapper;
use PHPUnit\Framework\TestCase;

class FormatMapperTest extends TestCase
{
    public function testField007()
    {
        $mapper = new FormatMapper();
        $this->assertEquals($mapper->marc21007('v', 'd'), 'VideoDisc');
        $this->assertEquals($mapper->marc21007('c', 'r'), 'ElectronicResource');
    }

    public function testSimplify()
    {
        $mapper = new FormatMapper();
        $this->assertEquals($mapper->simplify(['Compilation', 'Book']), ['Compilation']);
        $this->assertEquals($mapper->simplify(['E-Journal', 'Newspaper']), ['Newspaper']);
    }

    public function testLeader7()
    {
        $mapper = new FormatMapper();
        $this->assertEquals($mapper->marc21leader7('m', 'C', ''), 'E-Book');
        $this->assertEquals($mapper->marc21leader7('s', '', 'p'), 'Journal');
        $this->assertEquals($mapper->marc21leader7('s', '', 'n'), 'Newspaper');
    }
}
