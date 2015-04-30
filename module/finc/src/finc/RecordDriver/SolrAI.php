<?php
/**
 * Recorddriver for Solr records from the aggregated index of Leipzig University
 * Library
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
use \VuFindHttp\HttpServiceAwareInterface as HttpServiceAwareInterface,
    Zend\Log\LoggerAwareInterface as LoggerAwareInterface;

/**
 * Recorddriver for Solr records from the aggregated index of Leipzig University
 * Library
 *
 * @category VuFind2
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class SolrAI extends SolrDefault implements
    HttpServiceAwareInterface, LoggerAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * AI record
     *
     * @var array
     */
    protected $aiRecord;

    /**
     * holds config.ini data
     *
     * @var array
     */
    protected $mainConfig;


    /**
     * gets the description of the record
     *
     * @return string description
     */
    public function getDescriptions()
    {
        return $this->_getAIFullrecordArrayValue('abstract');
    }

    /**
     * gets the edition key from the record
     *
     * @return string edition
     */
    public function getEdition()
    {
        return $this->_getAIFullrecordStringValue('rft.edition');
    }

    /**
     * gets the publication date of the record
     *
     * @return string publication date
     */
    public function getDate()
    {
        return isset($this->fields['publishDateSort']) ?
            $this->fields['publishDateSort'] : '';
    }

    /**
     * gets an array of issues from record
     *
     * @return array of issues
     */
    public function getIssues()
    {
        return $this->_getAIFullrecordStringValue('rft.issue');
    }


    /**
     * Get an array of publication detail of first entry combined from
     * place, publisher and data.
     *
     * @return array
     * @access protected
     */
    /*public function getFirstPublicationDetails()
    {
        $place = $this->getPlacesOfPublication();
        $date = $this->getPublicationDates();
        $array = array();
        if (isset($this->fields['format']) && is_array($this->fields['format'])) {
            $array['issue'] = (isset($this->fields['hierarchy_parent_title']) ?
                'In: '.$this->fields['hierarchy_parent_title'][0] : '');
            $array['date'] = ((is_array($date) && (count($date) > 0) ?
                $date[0] : ''));
            switch ((count($this->fields['format']) > 0) ?
                $this->fields['format'][0] : '') {
                case 'eBook':
                    $array['place'] = ((is_array($place) && (count($place) > 0) ?
                        $place[0] : ''));
                    break;
                case 'ElectronicArticle':
                    $array['place'] = '';
                    break;
                default:
                    break;
            }
        }

        return $array;
    }*/

    /**
     * Has FirstPublicationsDetails a Date in it
     *
     * @return boolean
     * @access protected
     */
    protected function getIsPublicationDetailsDate()
    {
        return true;
    }

    /**
     * Get the main author of the record.
     *
     * @return string
     * @access protected
     */
    public function getPrimaryAuthor()
    {
        return null;
    }

    /**
     * Get additional entries for personal names.
     *
     * @return array
     * @access protected
     * @link http://www.loc.gov/marc/bibliographic/bd700.html
     */
    protected function getAdditionalAuthors()
    {
        if (isset($this->record['authors'])
            && is_array($this->record['authors'])
            && (count($this->record['authors']) > 0)
        ) {
            $retval = [];
            $i = 0;
            $authors = $this->record['authors'];
            foreach ($authors as $value) {
                $retval[$i]['name'] = (isset($value['rft.aulast']) ?
                        $value['rft.aulast'].', ' : '')
                    .(isset($value['rft.aufirst']) ? $value['rft.aufirst'] : '');
                $i++;
            }
            return $retval;
        }
        return [];
    }

    /**
     * Get the title of the item that contains this record (i.e. MARC 773s of a
     * journal).
     *
     * @return string
     */
    public function getContainerTitle()
    {
        return (isset($this->fields['series']) ?
                $this->fields['series'][0] : '');
    }

    /**
     * Get an array of publication detail lines combining information from
     * getPublicationDates(), getPublishers() and getPlacesOfPublication().
     *
     * @return array
     * @access protected
     */
    public function getPublicationDetails()
    {
        $names =  $this->_getAIFullrecordArrayValue('rft.pub');
        $i = 0;
        $retval = [];
        while (isset($names[$i])) {
            // Build objects to represent each set of data; these will
            // transform seamlessly into strings in the view layer.
            $retval[] = new \VuFind\RecordDriver\Response\PublicationDetails(
                null,
                isset($names[$i]) ? $names[$i] : '',
                null
            );
            $i++;
        }
        return $retval;
    }

    /**
     * Get the publication dates of the record.  See also getDateSpan().
     *
     * @return array
     */
    public function getPublicationDates()
    {
        return isset($this->fields['publishDateSort']) ?
            $this->fields['publishDateSort'] : '';
    }

    /**
     * Returns an array with the necessary information to create a detailed
     * "Published in" line in RecordDriver core.phtml
     *
     * @return array
     */
    public function getAIDataIn()
    {
        return [
            'jtitle' => $this->getJTitle(),
            'volume' => $this->getVolume(),
            'date'   => $this->getDate(),
            'issue'  => $this->getIssues(),
            'issns'  => $this->getISSNs(),
            'pages'  => $this->getPages()
        ];
    }

    /**
     * gets an array of series from record
     *
     * @return array of series
     */
    public function getSeries()
    {
        return $this->_getAIFullrecordArrayValue('rft.series');
    }

    /**
     * gets an array of volumes from record
     *
     * @return array of volumes
     */
    public function getVolume()
    {
        return $this->_getAIFullrecordStringValue('rft.volume');
    }

    /**
     * Get the ISSN from a record.
     *
     * @return array
     * @access protected
     * @link https://intern.finc.info/fincproject/issues/969 description
     */
    public function getISSNs()
    {
        return $this->_getAIFullrecordArrayValue('rft.issn');
    }

    /**
     * Get the eISSN from a record.
     *
     * @return array
     * @access protected
     * @link https://intern.finc.info/fincproject/issues/969 description
     */
    public function getEISSNs()
    {
        return $this->_getAIFullrecordArrayValue('rft.eissn');
    }

    /**
     * Get an array of all ISSNs associated with the record (may be empty).
     * Can be the main ISSN and the parent ISSNs.
     *
     * @return array
     * @access protected
     */
    public function getISBNs()
    {
        return $this->_getAIFullrecordArrayValue('rft.isbn');
    }

    /**
     * gets pages as 'start - end' if both exist
     *
     * @return string pages
     */
    public function getPages()
    {
        if ($this->hasStartpages()
            && $this->hasEndpages()
        ) {
            return sprintf(
                '%s - %s',
                $this->aiRecord['rft.spage'],
                $this->aiRecord['rft.epage']
            );
        } else if ($this->hasStartpages()) {
            return $this->aiRecord['rft.spage'][0];
        } else if ($this->hasEndpages()) {
            return $this->aiRecord['rft.epage'][0];
        }

        return '';
    }

    /**
     * Return the jtitle field of ai records
     *
     * @return array   Return jtitle fields.
     * @access public
     */
    public function getJTitle ()
    {
        return $this->_getAIFullrecordStringValue('rft.jtitle');
    }

    /**
     * Return the jtitle field of ai records
     *
     * @return array   Return jtitle fields.
     * @access public
     */
    public function getATitle ()
    {
        return $this->_getAIFullrecordStringValue('rft.atitle');
    }

    /**
     * Return the jtitle field of ai records
     *
     * @return array   Return jtitle fields.
     * @access public
     */
    public function getBTitle ()
    {
        return $this->_getAIFullrecordStringValue('rft.btitle');
    }

    /**
     * Get the OpenURL parameters to represent this record (useful for the
     * title attribute of a COinS span tag).
     *
     * @return string OpenURL parameters.
     */
    public function getOpenURL() {
        // Set up parameters based on the format of the record:
        switch ($this->aiRecord['rft.genre']) {
            case 'book':
                $params = $this->getBookOpenURLParams();
                break;
            case 'article':
                $params = $this->getArticleOpenURLParams();
                break;
            case 'journal':
                $params = $this->getJournalOpenURLParams();
                break;
            default:
                $format = $this->getFormats();
                $params = $this->getUnknownFormatOpenURLParams($format);
                break;
        }

        // Assemble the URL:
        return http_build_query($params);
    }

    /**
     * Get the COinS identifier.
     *
     * @return string
     */
    protected function getCoinsID()
    {
        // Get the COinS ID -- it should be in the OpenURL section of config.ini,
        // but we'll also check the COinS section for compatibility with legacy
        // configurations (this moved between the RC2 and 1.0 releases).
        if (isset($this->mainConfig->OpenURL->rfr_id)
            && !empty($this->mainConfig->OpenURL->rfr_id)
        ) {
            return $this->mainConfig->OpenURL->rfr_id;
        }
        return 'vufind.svn.sourceforge.net';
    }

    /**
     * Get default OpenURL parameters.
     *
     * @return array
     */
    /*protected function getDefaultOpenURLParams()
    {
        // Start an array of OpenURL parameters:
        return [
            'ctx_ver' => 'Z39.88-2004',
            'ctx_enc' => 'info:ofi/enc:UTF-8',
            'rfr_id' => 'info:sid/' . $this->getCoinsID() . ':generator'
        ];
    }*/

    /**
     * Get OpenURL parameters for an article.
     *
     * @return array
     */
    protected function getArticleOpenURLParams()
    {
        $params = $this->getDefaultOpenURLParams();
        // unset default title -- we only want jtitle/atitle here:
        //$params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
        $params['genre'] = 'article';
        if (isset($this->aiRecord['finc.record_id'])) {
            $params['rft_id'] = $this->aiRecord['finc.record_id'];
        }
        if (isset($this->aiRecord['rft.issn'])) {
            foreach ($this->aiRecord['rft.issn'] as $issn) {
                $params['issn'] = $issn;
            }
        }
        // an article may have also an ISBN:
        if (isset($this->aiRecord['rft.isbn'])) {
            $params['isbn'] = $this->aiRecord['rft.isbn'];
        }
        if (isset($this->aiRecord['rft.ssn'])) {
            $params['ssn'] = $this->aiRecord['rft.ssn'];
        }
        if (isset($this->aiRecord['rft.volume'])) {
            $params['volume'] = $this->aiRecord['rft.volume'];
        }
        if (isset($this->aiRecord['rft.issue'])) {
            $params['issue'] = $this->aiRecord['rft.issue'];
        }
        if (isset($this->aiRecord['rft.spage'])) {
            $params['spage'] = $this->aiRecord['rft.spage'];
        }
        if (isset($this->aiRecord['rft.epage'])) {
            $params['epage'] = $this->aiRecord['rft.epage'];
        }
        if (isset($this->aiRecord['rft.pages'])) {
            $params['pages'] = $this->aiRecord['rft.pages'];
        }
        if (isset($this->aiRecord['rft.coden'])) {
            $params['coden'] = $this->aiRecord['rft.coden'];
        }
        if (isset($this->aiRecord['rft.artnum'])) {
            $params['artnum'] = $this->aiRecord['rft.artnum'];
        }
        if (isset($this->aiRecord['rft.sici'])) {
            $params['sici'] = $this->aiRecord['rft.sici'];
        }
        if (isset($this->aiRecord['rft.chron'])) {
            $params['chron'] = $this->aiRecord['rft.chron'];
        }
        if (isset($this->aiRecord['rft.quarter'])) {
            $params['quarter'] = $this->aiRecord['rft.quarter'];
        }
        if (isset($this->aiRecord['rft.part'])) {
            $params['part'] = $this->aiRecord['rft.part'];
        }
        if (isset($this->aiRecord['rft.jtitle'])) {
            $params['jtitle'] = $this->getJTitle();
        }
        if (isset($this->aiRecord['rft.atitle'])) {
            $params['atitle'] = $this->getATitle();
        }
        if (isset($this->aiRecord['rft.stitle'])) {
            $params['stitle'] = $this->aiRecord['rft.stitle'];
        }
        if (isset($this->aiRecord['authors'])) {
            foreach ($this->aiRecord['authors'] as $author) {
                if (isset($author['rft.au'])) {
                    $params['au'] = $author['rft.au'];
                }
                if (isset($author['rft.aulast'])) {
                    $params['aulast'] = $author['rft.aulast'];
                }
                if (isset($author['rft.aucorp'])) {
                    $params['aucorp'] = $author['rft.aucorp'];
                }
                if (isset($author['rft.auinitm'])) {
                    $params['auinitm'] = $author['rft.auinitm'];
                }
                if (isset($author['rft.aufirst'])) {
                    $params['aufirst'] = $author['rft.aufirst'];
                }
                if (isset($author['rft.auinit'])) {
                    $params['auinit'] = $author['rft.auinit'];
                }
                if (isset($author['rft.auinit1'])) {
                    $params['auinit1'] = $author['rft.auinit1'];
                }
                if (isset($author['rft.ausuffix'])) {
                    $params['ausuffix'] = $author['rft.ausuffix'];
                }
            }
        }

        if (isset($this->aiRecord['rft.format'])) {
            $params['format'] = $this->aiRecord['rft.format'];
        }
        if (isset($this->aiRecord['doi'])) {
            $params['rft_id'] = 'info:doi/'.$this->aiRecord['doi'];
        }
        if (isset($this->aiRecord['languages'])) {
            $params['rft.language'] = $this->aiRecord['languages'];
        }
        if (isset($this->aiRecord['rft.date'])) {
            $params['rft.date'] = $this->aiRecord['rft.date'];
        }
        return $params;
    }

    /**
     * Get OpenURL parameters for a book.
     *
     * @return array
     */
    protected function getBookOpenURLParams()
    {
        $params = $this->getDefaultOpenURLParams();
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
        $params['genre'] = 'book';
        if (isset($this->aiRecord['rft.atitle'])) {
            $params['atitle'] = $this->getATitle();
        }
        if (isset($this->aiRecord['rft.btitle'])) {
            $params['rft.btitle'] = $this->getBTitle();
        }
        if (isset($this->aiRecord['finc.record_id'])) {
            $params['rft_id'] = $this->aiRecord['finc.record_id'];
        }
        if (isset($this->aiRecord['rft.issn'])) {
            foreach ($this->aiRecord['rft.issn'] as $issn) {
                $params['issn'] = $issn;
            }
        }
        if (isset($this->aiRecord['rft.edition'])) {
            $params['edition'] = $this->aiRecord['rft.edition'];
        }
        // an article may have also an ISBN:
        if (isset($this->aiRecord['rft.isbn'])) {
            $params['isbn'] = $this->aiRecord['rft.isbn'];
        }
        if (isset($this->aiRecord['rft.ssn'])) {
            $params['ssn'] = $this->aiRecord['rft.ssn'];
        }
        if (isset($this->aiRecord['rft.eissn'])) {
            $params['eissn'] = $this->aiRecord['rft.eissn'];
        }
        if (isset($this->aiRecord['rft.volume'])) {
            $params['volume'] = $this->aiRecord['rft.volume'];
        }
        if (isset($this->aiRecord['rft.issue'])) {
            $params['issue'] = $this->aiRecord['rft.issue'];
        }
        if (isset($this->aiRecord['rft.spage'])) {
            $params['spage'] = $this->aiRecord['rft.spage'];
        }
        if (isset($this->aiRecord['rft.epage'])) {
            $params['epage'] = $this->aiRecord['rft.epage'];
        }
        if (isset($this->aiRecord['rft.pages'])) {
            $params['pages'] = $this->aiRecord['rft.pages'];
        }
        if (isset($this->aiRecord['rft.series'])) {
            $params['series'] = $this->aiRecord['rft.series'];
        }
        if (isset($this->aiRecord['rft.tpages'])) {
            $params['tpages'] = $this->aiRecord['rft.tpages'];
        }
        if (isset($this->aiRecord['rft.bici'])) {
            $params['bici'] = $this->aiRecord['rft.bici'];
        }
        if (isset($this->aiRecord['authors'])) {
            foreach ($this->aiRecord['authors'] as $author) {
                if (isset($author['rft.au'])) {
                    $params['au'] = $author['rft.au'];
                }
                if (isset($author['rft.aulast'])) {
                    $params['aulast'] = $author['rft.aulast'];
                }
                if (isset($author['rft.aucorp'])) {
                    $params['aucorp'] = $author['rft.aucorp'];
                }
                if (isset($author['rft.auinitm'])) {
                    $params['auinitm'] = $author['rft.auinitm'];
                }
                if (isset($author['rft.aufirst'])) {
                    $params['aufirst'] = $author['rft.aufirst'];
                }
                if (isset($author['rft.auinit'])) {
                    $params['auinit'] = $author['rft.auinit'];
                }
                if (isset($author['rft.auinit1'])) {
                    $params['auinit1'] = $author['rft.auinit1'];
                }
                if (isset($author['rft.ausuffix'])) {
                    $params['ausuffix'] = $author['rft.ausuffix'];
                }
            }
        }

        if (isset($this->aiRecord['rft.format'])) {
            $params['format'] = $this->aiRecord['rft.format'];
        }
        if (isset($this->aiRecord['doi'])) {
            $params['rft_id'] = 'info:doi/'.$this->aiRecord['doi'];
        }
        $publishers = $this->getPublishers();
        if (count($publishers) > 0) {
            $params['rft.pub'] = $publishers[0];
        }
        return $params;
    }

    /**
     * Retrieve raw data from object (primarily for use in staff view and
     * autocomplete; avoid using whenever possible).
     *
     * @return mixed
     */
    public function getRawData()
    {
        $tmp = [];
        $i = 0;
        if (!empty($this->aiRecord)) {
            foreach ($this->aiRecord as $key => $value) {
                $tmp[$i]['key'] = $key;
                $tmp[$i]['value'] = $value;
                $i++;
            }
        }
        return $tmp;
    }

    /**
     * Retrieve data from ai-blobserver
     *
     * @param string $id      Record Id of the raw recorddata to be retrieved
     * @param string $baseUrl The Ai fullrecord server url.
     *
     * @return mixed          Raw curl request response (should be json).
     * @throws \Exception
     */
    protected function retrieveAiFullrecord($id, $baseUrl)
    {
        if (!isset($id)) {
            throw new \Exception('no id given');
        }

        $url = sprintf($baseUrl, $id);

        try {
            $response = $this->httpService->get($url);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        if (!$response->isSuccess()) {
            $this->debug(
                'HTTP status ' . $response->getStatusCode() .
                ' received, retrieving data for record: ' . $id
            );

            return false;
        }

        return $response->getBody();
    }

    /**
     * Returns the AI fullrecord as decoded json.
     *
     * @param string $id Record id to be retrieved.
     *
     * @return array
     * @throws \Exception
     */
    protected function getAIJSONFullrecord($id)
    {
        if (!isset($this->recordConfig->General)) {
            throw new \Exception('SolrAI General settings missing.');
        }

        $baseUrl = $this->recordConfig->General->baseUrl;

        if (!isset($baseUrl)) {
            throw new \Exception('no ai-blobserver configurated');
        }

        $response = $this->retrieveAiFullrecord($id, $baseUrl);

        return json_decode($response, true);
    }

    /**
     * returns the value of a certain record key or the default value if not exists
     *
     * @param string $key     of record array
     * @param mixed  $default [optional] return value
     *
     * @return mixed value of key
     * @access private
     */
    private function _getAIFullrecordStringValue($key, $default = '')
    {
        if (!$this->_hasAIFullrecordStringValue($key)) {
            if ($this->_hasAIFullrecordArrayValue($key)) {
                return implode(',', $this->aiRecord[$key]);
            }
            return $default;
        }

        return $this->aiRecord[$key];
    }

    /**
     * returns the value of a certain record key or the default value if not exists
     *
     * @param string $key     of record array
     * @param mixed  $default [optional] return value
     *
     * @return mixed value of key
     * @access private
     */
    private function _getAIFullrecordArrayValue($key, $default = [])
    {
        if (!$this->_hasAIFullrecordArrayValue($key)) {
            return $default;
        }
        return $this->aiRecord[$key];
    }

    /**
     * checks whether a certain array key exists and is not empty in record data array
     *
     * @param string $key Key to be checked.
     *
     * @return boolean true or false
     * @access private
     */
    private function _hasAIFullrecordStringValue($key)
    {
        if (empty($this->aiRecord)) {
            $this->aiRecord = $this->getAIJSONFullrecord($this->fields['id']);
        }
        if (isset($this->aiRecord[$key])
            && !empty($this->aiRecord[$key])
            && !is_array($this->aiRecord[$key])
        ) {
            return true;
        }

        return false;
    }


    /**
     * checks whether a certain array key exists, is an array and has elements in
     * record data array
     *
     * @param string $key Key to be checked
     *
     * @return boolean true or false
     * @access private
     */
    private function _hasAIFullrecordArrayValue($key)
    {
        if (empty($this->aiRecord)) {
            $this->aiRecord = $this->getAIJSONFullrecord($this->fields['id']);
        }
        if (isset($this->aiRecord[$key])
            && is_array($this->aiRecord[$key])
            && count($this->aiRecord[$key]) > 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * checks whether record has start pages
     *
     * @return boolean
     */
    public function hasStartpages()
    {
        if (isset($this->aiRecord['rft.spage'])
            && !empty($this->aiRecord['rft.spage'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * checks whether record has Endpages
     *
     * @return boolean
     */
    public function hasEndpages()
    {
        if (isset($this->aiRecord['rft.epage'])
            && !empty($this->aiRecord['rft.epage'])
        ) {
            return true;
        }

        return false;
    }


}
