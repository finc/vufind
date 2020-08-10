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
namespace VuFind\Crypt;

use Exception;/**
 * Klasse to encode and decod enumbers using base62
 * @package  VuFind\Crypt
 * @category boss
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Base62
{
    const BASE62_ALPHABET
        = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    const BASE62_BASE = 62;

    /**
     * Common base62 encoding function.
     * Implemented here so we don't need additional PHP modules like bcmath.
     *
     * @param string $base10Number Number to encode
     *
     * @return string
     *
     * @throws Exception
     */
    public function encode($base10Number)
    {
        $binaryNumber = intval($base10Number);
        if ($binaryNumber === 0) {
            throw new Exception('not a base10 number: "' . $base10Number . '"');
        }

        $base62Number = '';
        while ($binaryNumber != 0) {
            $base62Number = self::BASE62_ALPHABET[$binaryNumber % self::BASE62_BASE]
                . $base62Number;
            $binaryNumber = intdiv($binaryNumber, self::BASE62_BASE);
        }

        return ($base62Number == '') ? '0' : $base62Number;
    }

    /**
     * Common base62 decoding function.
     * Implemented here so we don't need additional PHP modules like bcmath.
     *
     * @param string $base62Number Number to decode
     *
     * @return int
     *
     * @throws Exception
     */
    public function decode($base62Number)
    {
        $binaryNumber = 0;
        for ($i = 0; $i < strlen($base62Number); ++$i) {
            $digit = $base62Number[$i];
            $strpos = strpos(self::BASE62_ALPHABET, $digit);
            if ($strpos === false) {
                throw new Exception('not a base62 digit: "' . $digit . '"');
            }

            $binaryNumber *= self::BASE62_BASE;
            $binaryNumber += $strpos;
        }
        return $binaryNumber;
    }
}
