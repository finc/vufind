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

use Bsz\Exception;
use Bsz\ILL\Logic;
use PHPUnit\Framework\TestCase;
use Zend\Config\Config;

class LogicTest extends TestCase
{
    private function getLogic()
    {
        $config = new Config([
            'Checks' => [
                'methods' => '!Hebis8, !Free, !SerialOrCollection, !CurrentLibrary, !ParallelEditions, ~JournalAvailable, Format, Indicator"'
            ]

        ]);
        $logic = new Logic($config);
        return $logic;
    }

    public function testLogic()
    {
        $logic = $this->getLogic();
        $this->assertInstanceOf(Logic::class, $logic);
        $this->expectException(Exception::class);
        $logic->isAvailable();


    }
}
