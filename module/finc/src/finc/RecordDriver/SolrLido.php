<?php
/**
 * finc specific model for Lido records with a fullrecord in Solr.
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2015.
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
 * finc specific model for LIDO records with a fullrecord in Solr.
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrLido extends SolrLidoNdl
{
    use SolrLidoFincTrait;

    /**
     * Date Converter
     *
     * @var \VuFind\Date\Converter
     */
    protected $dateConverter;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $mainConfig VuFind main configuration (omit
     * for built-in defaults)
     * @param \Zend\Config\Config $recordConfig Record-specific configuration
     * file (omit to use $mainConfig as $recordConfig)
     * @param \Zend\Config\Config $searchSettings Search-specific configuration
     * file
     * @param \VuFind\Date\Converter $dateConverter Date Converter
     */
    public function __construct($mainConfig = null, $recordConfig = null,
                                $searchSettings = null, $dateConverter = null
    )
    {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);
        $this->dateConverter = $dateConverter;
    }

}