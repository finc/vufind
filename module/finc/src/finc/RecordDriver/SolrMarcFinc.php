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
class SolrMarcFinc extends SolrMarc
{
    use SolrMarcFincTrait;

    /**
     * pattern to identify bsz
     */
    const BSZ_PATTERN = '/^(\(DE-576\))(\d+)(\w|)/';

    /**
     * @var string  ISIL of this instance's library
     */
    protected $isil = '';

    /**
     * @var array   Array of ISILs set in the LibraryGroup section in config.ini.
     */
    protected $libraryGroup = [];

    /**
     * @var string|null
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
                                $searchSettings = null)
    {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);

        if (isset($mainConfig->InstitutionInfo->isil)) {
            $this->isil = $this->mainConfig->InstitutionInfo->isil;
        } else {
            $this->debug('InstitutionInfo setting is missing.');
        }

        if (isset($mainConfig->LibraryGroup->libraries)) {
            $this->libraryGroup
                = explode(',', $this->mainConfig->LibraryGroup->libraries);
        } else {
            $this->debug('LibraryGroup setting is missing.');
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
