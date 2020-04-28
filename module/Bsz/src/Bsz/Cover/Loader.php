<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
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
 */
namespace Bsz\Cover;

use Exception;
use VuFindCode\ISBN;

/**
 * Adapted version of cover loader
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Loader extends \VuFind\Cover\Loader
{
    protected $ean;

    /**
     * Load an image given an ISBN and/or content type.
     *
     * @param string $isbn       ISBN
     * @param string $size       Requested size
     * @param string $type       Content type
     * @param string $title      Title of book (for dynamic covers)
     * @param string $author     Author of the book (for dynamic covers)
     * @param string $callnumber Callnumber (unique id for dynamic covers)
     * @param string $issn       ISSN
     * @param string $oclc       OCLC number
     * @param string $upc        UPC number
     * @param string $ean        EAN number
     *
     * @return null|url
     */
    public function loadImage($isbn = null, $size = 'small', $type = null,
        $title = null, $author = null, $callnumber = null, $issn = null,
        $oclc = null, $upc = null, $ean = null
    ) {
        // Sanitize parameters:
        $this->isbn = new ISBN($isbn);
        $this->issn = empty($issn)
            ? null
            : substr(preg_replace('/[^0-9X]/', '', strtoupper($issn)), 0, 8);
        $this->oclc = $oclc;
        $this->upc = $upc;
        $this->ean = $ean;
        $this->type = preg_replace("/[^a-zA-Z]/", "", $type);
        $this->size = $size;

        // Display a fail image unless our parameters pass inspection and we
        // are able to display an ISBN or content-type-based image.
        if (!in_array($this->size, $this->validSizes)) {
            $this->loadUnavailable();
        } elseif (!$this->fetchFromAPI()
            && !$this->fetchFromContentType()
        ) {
            if (isset($this->config->Content->makeDynamicCovers)
                && false !== $this->config->Content->makeDynamicCovers
            ) {
                $this->image = $this->getCoverGenerator()
                    ->generate($title, $author, $callnumber);
                $this->contentType = 'image/jpeg';
                // Generator returns empty string if makeDynamicCovers is
                // html or false
                if (strlen($this->image) > 0) {
                    return $this->image;
                } else {
                    $this->loadUnavailable();
                }
            }
        }
        return null;
    }

    /**
     * Get all valid identifiers as an associative array.
     *
     * @return array
     */
    protected function getIdentifiers()
    {
        $ids = [];
        if ($this->isbn && $this->isbn->isValid()) {
            $ids['isbn'] = $this->isbn;
        }
        if ($this->issn && strlen($this->issn) == 8) {
            $ids['issn'] = $this->issn;
        }
        if ($this->oclc && strlen($this->oclc) > 0) {
            $ids['oclc'] = $this->oclc;
        }
        if ($this->upc && strlen($this->upc) > 0) {
            $ids['upc'] = $this->upc;
        }
        if ($this->ean && strlen($this->ean) > 0) {
            $ids['ean'] = $this->ean;
        }
        return $ids;
    }

    /**
     * Support method for fetchFromAPI() -- set the localFile property.
     *
     * @param array $ids IDs returned by getIdentifiers() method
     *
     * @return void
     */
    protected function determineLocalFile($ids)
    {
        // We should check whether we have cached images for the 13- or 10-digit
        // ISBNs. If no file exists, we'll favor the 10-digit number if
        // available for the sake of brevity.
        if (isset($ids['isbn'])) {
            $file = $this->getCachePath($this->size, $ids['isbn']->get13());
            if (!is_readable($file) && $ids['isbn']->get10()) {
                return $this->getCachePath($this->size, $ids['isbn']->get10());
            }
            return $file;
        } elseif (isset($ids['issn'])) {
            return $this->getCachePath($this->size, $ids['issn']);
        } elseif (isset($ids['oclc'])) {
            return $this->getCachePath($this->size, 'OCLC' . $ids['oclc']);
        } elseif (isset($ids['upc'])) {
            return $this->getCachePath($this->size, 'UPC' . $ids['upc']);
        } elseif (isset($ids['ean'])) {
            return $this->getCachePath($this->size, 'EAN' . $ids['ean']);
        }
        throw new Exception('Unexpected code path reached!');
    }
}
