<?php
/**
 * finc specific model for MARC records without a fullrecord in Solr. The fullrecord is being
 * retrieved from an external source.
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
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace finc\RecordDriver;

/**
 * finc specific model for MARC records without a fullrecord in Solr. The fullrecord is being
 * retrieved from an external source.
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrMarcRemoteFinc extends SolrMarcRemote
{
    use SolrMarcFincTrait;

    /**
     * Pattern to identify bsz
     */
    const BSZ_PATTERN = '/^(\(DE-576\))(\d+)(\w|)/';

    /**
     * List of isil of institution
     *
     * @var string  ISIL of this instance's library
     */
    protected $isil = [];

    /**
     * Local marc field of institution participated in Finc.
     *
     * @var  string|null
     * @link https://intern.finc.info/fincproject/projects/finc-intern/wiki/FincMARC_-_Erweiterung_von_MARC21_f%C3%BCr_finc
     */
    protected $localMarcFieldOfLibrary = null;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $mainConfig     VuFind main configuration (omit for
     * built-in defaults)
     * @param \Zend\Config\Config $recordConfig   Record-specific configuration file
     * (omit to use $mainConfig as $recordConfig)
     * @param \Zend\Config\Config $searchSettings Search-specific configuration file
     */
    public function __construct($mainConfig = null, $recordConfig = null,
                                $searchSettings = null
    )
    {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);

        if (isset($mainConfig->InstitutionInfo->isil)
            && count($mainConfig->InstitutionInfo->isil) > 0
        ) {
            $this->isil = $this->mainConfig->InstitutionInfo->isil->toArray();
        } else {
            $this->debug('InstitutionInfo setting: isil is missing.');
        }

        if (isset($this->mainConfig->CustomSite->namespace)) {
            // map for marc fields
            $map = [
                'che' => '971',
                'hgb' => '979',
                'hfbk' => '978',
                'hfm' => '977',
                'hmt' => '970',
                'htw' => '973',
                'htwk' => '974',
                'tuf' => '972',
                'ubl' => '969',
                'zit' => '976',
                'zwi' => '975',
            ];
            $this->localMarcFieldOfLibrary
                = isset($map[$this->mainConfig->CustomSite->namespace]) ?
                    $map[$this->mainConfig->CustomSite->namespace] : null;
        } else {
            $this->debug('Namespace setting for localMarcField is missing.');
        }
    }
}
