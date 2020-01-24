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

namespace Bsz\RecordDriver;


trait OriginalLanguageTrait
{

    /**
     * @return string
     */
    public function getTitleOl(): string
    {
        $tmp = [
            $this->getShortTitleOl(),
            ' : ',
            $this->getSubtitleOl(),
        ];
        $title = implode(' ', $tmp);
        return $this->cleanString($title);
    }

    /**
     * @return string
     */
    public function getShortTitleOl(): string
    {
        return $this->getOriginalLanguage(245, 'a');
    }

    /**
     * @return string
     */
    public function getSubtitleOl(): string
    {
        return $this->getOriginalLanguage(245, 'b');
    }

    /**
     * GRetrieve the original language string for a given field anf subfield
     *
     * @param $targetField
     * @param $targetSubfield
     * @return string
     */
    public function getOriginalLanguage($targetField, $targetSubfield) : string
    {

        $return = '';
        $fields = $this->getMarcRecord()->getFields('880');

        foreach ($fields as $field) {
            $subfield6 = $field->getSubfield('6')->getData();
            $sf= $field->getSubfield($targetSubfield);
            if ($sf !== false) {
                $data = $sf->getData();
                if (substr_count($subfield6, $targetField) > 0 && isset($data)) {
                    $return = $data;
                }
            }
        }
        return $return;
    }

    /**
     * @param array $targets
     * @return array
     */
    public function getOriginalLanguageMulti(array $targets) : array
    {
        $return = [];
        foreach ($targets as $tag => $subfields) {
            $returnSub = [];
            foreach ($subfields as $subfield) {
                $returnSub[] = $this->getOriginalLanguage($tag, $subfield);
            }
            $return[] = implode(' ', $returnSub);
        }
        return $return;
    }

    /**
     * @return array
     */
    public function getPlacesOfPublicationOl()
    {
        $fields = [
            260 => 'a',
            264 => 'a',
        ];
        return $this->getOriginalLanguageMulti($fields);
    }







}