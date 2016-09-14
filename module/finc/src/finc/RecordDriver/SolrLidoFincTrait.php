<?php
/**
 * finc specific model for LIDO records with a fullrecord in Solr.
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2016.
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
 * @package  RecordDrivers
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace finc\RecordDriver;

/**
 * finc specific model for Lido records with a fullrecord in Solr.
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
trait SolrLidoFincTrait
{
    /**
     * Return access notes for the record.
     *
     * @return array
     */
    public function getAccessNote()
    {
        $restrictions = [];
        if ($rights = $this->getSimpleXML()->xpath(
            'lido/administrativeMetadata/resourceWrap/resourceSet/rightsResource/'
            . 'rightsType'
        )
        ) {
            $i = 0;
            $notes = [];
            foreach ($rights as $right) {
                if (!isset($right->conceptID)) {
                    continue;
                }
                $type = strtolower((string)$right->conceptID->attributes()->type);
                if (strtolower($type) == 'uri') {
                    $conceptID = (string)$right->conceptID;
                    $term = (string)$right->term;
                    if ($term) {
                        $notes[$i] = ['term' => $term, 'uri' => $conceptID];
                    }
                }
                $i++;
            }
        }
        return $notes;
    }


    /**
     * Get comprehensive measurements description without dismantling in type, unit
     * and value.
     *
     * @return array
     */
    public function getMeasurementsDescription()
    {
        $results = [];
        foreach ($this->getSimpleXML()->xpath(
            'lido/descriptiveMetadata/'
            . 'objectIdentificationWrap/objectMeasurementsWrap/'
            . 'objectMeasurementsSet/displayObjectMeasurements'
        ) as $node) {
            $results[] = (string)$node;
        }
        return $results;
    }

}