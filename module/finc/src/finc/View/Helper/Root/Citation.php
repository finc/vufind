<?php
/**
 * Citation view helper
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
 * @category VuFind
 * @package  View_Helpers
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\View\Helper\Root;

/**
 * Citation view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Citation extends \VuFind\View\Helper\Root\Citation
{

    /**
     * Store a record driver object and return this object so that the appropriate
     * template can be rendered.
     *
     * @param \VuFind\RecordDriver\Base $driver Record driver object.
     *
     * @return Citation
     */
    public function __invoke($driver)
    {
        // Build author list:
        $authors = [];
        $primary = $driver->tryMethod('getPrimaryAuthor');
        if (empty($primary)) {
            $primary = $driver->tryMethod('getCorporateAuthor');
        }
        if (!empty($primary)) {
            $authors[] = $primary;
        }
        $secondary = $driver->tryMethod('getSecondaryAuthors');
        if (is_array($secondary) && !empty($secondary)) {
            $authors = array_unique(array_merge($authors, $secondary));
        }

        // Get best available title details:
        $title = $driver->tryMethod('getShortTitle');
        $subtitle = $driver->tryMethod('getSubtitle');
        if (empty($title)) {
            $title = $driver->tryMethod('getTitle');
        }
        if (empty($title)) {
            $title = $driver->getBreadcrumb();
        }
        // Find subtitle in title if they're not separated:
        if (empty($subtitle) && strstr($title, ':')) {
            list($title, $subtitle) = explode(':', $title, 2);
        }

        // Extract the additional details from the record driver:
        $publishers = $driver->tryMethod('getPublishers');
        $pubDates = $driver->tryMethod('getPublicationDates');
        $pubPlaces = $driver->tryMethod('getPlacesOfPublication');
        $edition = $driver->tryMethod('getEdition');

        // Store everything:
        $this->driver = $driver;
        $this->details = [
            'authors' => $this->prepareAuthors($authors),
            'primaryauthor' => empty($primary) ? false : $primary,
            'title' => trim($title), 'subtitle' => trim($subtitle),
            'pubPlace' => isset($pubPlaces[0]) ? $pubPlaces[0] : null,
            'pubName' => isset($publishers[0]) ? $publishers[0] : null,
            'pubDate' => isset($pubDates[0]) ? $pubDates[0] : null,
            'edition' => empty($edition) ? [] : [$edition],
            'journal' => $driver->tryMethod('getContainerTitle')
        ];

        return $this;
    }

    /**
     * Strip the dates off the end of a name.
     *
     * @param string $str Name to clean.
     *
     * @return string     Cleaned name.
     */
    protected function cleanNameDates($str)
    {
        $fincDateStrip = function ($string) {
            if (preg_match('/^(\s|.*)\s(fl.\s|d.\s|ca.\s|\*)*\s?(\d{4})\??(\sor\s\d\d?)?\s?(-|–)?\s?(ca.\s|after\s|†)?(\d{1,4})?(.|,)?$/Uu', $string, $match)) {
                return (isset($match[1])) ? trim($match[1]) : $string;
            }
            return $string;
        };

        $fincGallicaStrip = function ($string) {
            // delete unnormalized characters of gallica ressource with source_id:20
            if (preg_match('/(.*)(\d.*)/Uus', $string, $match)) {
                return (isset($match[1])) ? trim($match[1]) : $string;
            }
            return $string;
        };

        return parent::cleanNameDates($fincGallicaStrip($fincDateStrip($str)));
    }

    /**
     * Get an array of authors for an MLA or Chicago Style citation.
     *
     * @param int $etAlThreshold The number of authors to abbreviate with 'et al.'
     * This is the only difference between MLA/Chicago Style.
     *
     * @return array
     */
    protected function getMLAAuthors($etAlThreshold = 4)
    {
        $authorStr = '';
        if (isset($this->details['authors'])
            && is_array($this->details['authors'])
        ) {
            $i = 0;
            if (count($this->details['authors']) > $etAlThreshold) {
                $author = $this->details['authors'][0];
                $authorStr = $this->cleanNameDates($author) . ', et al';
            } else {
                foreach ($this->details['authors'] as $author) {
                    $author = $this->cleanNameDates($author);
                    if (($i+1 == count($this->details['authors'])) && ($i > 0)) {
                        // Last
                        $authorStr .= ', and ' .
                            $this->reverseName($this->stripPunctuation($author));
                    } elseif ($i > 0) {
                        $authorStr .= ', ' .
                            $this->reverseName($this->stripPunctuation($author));
                    } else {
                        // First
                        $authorStr .= $this->cleanNameDates($author);
                    }
                    $i++;
                }
            }
        }
        return (empty($authorStr) ? false : $this->stripPunctuation($authorStr));
    }

    /**
     * Get an array of authors for an APA citation.
     *
     * @return array
     */
    protected function getAPAAuthors()
    {
        $authorStr = '';
        if (isset($this->details['authors'])
            && is_array($this->details['authors'])
        ) {
            $i = 0;
            $ellipsis = false;
            foreach ($this->details['authors'] as $author) {
                $author = $this->abbreviateName($author);
                $author = preg_replace('/[\(|\.]/','',$author);
                $author = $this->cleanNameDates($author);
                if (($i + 1 == count($this->details['authors']))
                    && ($i > 0)
                ) { // Last
                    // Do we already have periods of ellipsis?  If not, we need
                    // an ampersand:
                    $authorStr .= $ellipsis ? ' ' : '& ';
                    $authorStr .= $this->stripPunctuation($author) . '.';
                } elseif (count($this->details['authors']) > 1) {
                    $authorStr .= $author . ', ';
                } else { // First and only
                    $authorStr .= $this->stripPunctuation($author) . '.';
                }
                $i++;
            }
        }
        return (empty($authorStr) ? false : $authorStr);
    }

    /**
     * Get APA citation for SolrAI.
     *
     * This function assigns all the necessary variables and then returns an APA
     * citation for SolrAI.
     *
     * @return string
     */
    public function getCitationAPAAI()
    {
        $apa = [
            'title' => $this->getAPATitle(),
            'authors' => $this->getAPAAuthors(),
            'edition' => $this->driver->tryMethod('getEdition')
        ];
        // Show a period after the title if it does not already have punctuation
        // and is not followed by an edition statement:
        $apa['periodAfterTitle']
            = (!$this->isPunctuated($apa['title']) && empty($apa['edition']));

        // Behave differently for books vs. journals:
        $partial = $this->getView()->plugin('partial');
        if (empty($this->details['journal'])) {
            $apa['publisher'] = $this->getPublisher();
            $apa['year'] = $this->getYear();
            return $partial('Citation/apaai.phtml', $apa);
        } else {
            $apa['jtitle'] = $this->driver->tryMethod('getJTitle');
            $apa['volume'] = $this->driver->tryMethod('getVolume');
            $apa['issue'] = $this->driver->tryMethod('getIssues');
            $apa['year'] = $this->driver->tryMethod('getPublishDateSort');
            $apa['journal'] = $this->details['journal'];
            $apa['pageRange'] = $this->driver->tryMethod('getPages');
            if ($doi = $this->driver->tryMethod('getDOI')) {
                $apa['doi'] = $doi;
            }
            return $partial('Citation/apaai-article.phtml', $apa);
        }
    }

    /**
     * Get MLA citation for SolrAI.
     *
     * This function assigns all the necessary variables and then returns an MLA
     * citation for SolrAI.
     *
     * @param int $etAlThreshold The number of authors to abbreviate with 'et
     * al.'
     *
     * @return string
     */
    public function getCitationMLAAI($etAlThreshold = 4)
    {
        $mla = [
            'title' => $this->getMLATitle(),
            'authors' => $this->getMLAAuthors($etAlThreshold),
            'edition' => $this->driver->tryMethod('getEdition')
        ];
        $mla['periodAfterTitle'] = !$this->isPunctuated($mla['title']);

        // Behave differently for books vs. journals:
        $partial = $this->getView()->plugin('partial');
        if (empty($this->details['journal'])) {
            $mla['publisher'] = $this->getPublisher();
            $mla['year'] = $this->getYear();
            return $partial('Citation/mlaai.phtml', $mla);
        } else {
            // Add other journal-specific details:
            $mla['pageRange'] = $this->getPageRange();
            $mla['volume'] = $this->driver->tryMethod('getVolume');
            $mla['issue'] = $this->driver->tryMethod('getIssues');
            $mla['year'] = $this->driver->tryMethod('getPublishDateSort');
            $mla['journal'] =  $this->capitalizeTitle($this->details['journal']);
            $mla['pageRange'] = $this->driver->tryMethod('getPages');
            return $partial('Citation/mlaai-article.phtml', $mla);
        }
    }

    /**
     * Get ISBD citation.
     *
     * This function assigns all the necessary variables and then returns an ISBD
     * citation.
     *
     * @return string
     */
    public function getCitationISBD()
    {
        $isbd = [
            'title' => $this->getISBDTitle(),
            'authors' => $this->getISBDAuthor(),
            'isArticle' => !empty($this->details['journal']) ? true : false
        ];
        $isbd['periodAfterTitle'] = !$this->isPunctuated($isbd['title']);

        // Behave differently for books vs. journals:
        $partial = $this->getView()->plugin('partial');
        if (empty($this->details['journal'])) {
            $isbd['publisher'] = $this->getISBDPublisher();
            $isbd['edition'] = $this->getEdition();
            $isbd['series'] = $this->getISBDSeries();
            $isbd['notes'] = $this->getISBDNotes();
            $isbd['isbn'] = implode(', ', $this->driver->tryMethod('getISBNs'));
            return $partial('Citation/isbd.phtml', $isbd);
        } else {
            // Add other journal-specific details:
            $isbd['pageRange'] = $this->getPageRange();
            $isbd['journal'] =  $this->capitalizeTitle($this->details['journal']);
            return $partial('Citation/isbd-article.phtml', $isbd);
        }
    }

    /**
     * Get the primary author for an ISBD citation.
     *
     * @return string
     */
    protected function getISBDAuthor()
    {
        return isset($this->details['primaryauthor'])
            && is_array($this->details['primaryauthor'])
                ? $this->stripPunctuation(
                    $this->cleanNameDates($this->details['primaryauthor']))
                : false;
    }

    /**
     * Get the full title for an ISBD citation.
     *
     * @return string
     */
    protected function getISBDTitle()
    {
        // Create Title
        $title = $this->stripPunctuation($this->details['title']);
        if (isset($this->details['subtitle'])) {
            $subtitle = $this->stripPunctuation($this->details['subtitle']);
            // Capitalize subtitle and apply it, assuming it really exists:
            if (!empty($subtitle)) {
                $subtitle
                    = strtoupper(substr($subtitle, 0, 1)) . substr($subtitle, 1);
                $title .= ' : ' . $subtitle;
            }
        }
        $titleStatement = array();
        $rawTitlestatement = $this->driver->tryMethod('getTitleStatement');
        if (isset($rawTitlestatement)) {
            $titleStatement[] = $rawTitlestatement;
            // Capitalize subtitle and apply it, assuming it really exists:
            if (count($titleStatement) > 0) {
                $statement = $this->stripPunctuation(implode(' ', $titleStatement));
                $title .=  ' / ' . $statement;
                // $title .= (!empty($subtitle)) ? ' / ' . $statement : $statement;
            }
        }
        return $title . '.';
    }

    /**
     * Get the footnotes for an ISBD citation.
     *
     * @return string
     */
    protected function getISBDNotes()
    {
        $noteStr = '';
        $footnote = '';
        if (isset($this->details['footnote'])
            && count($this->details['footnote']) > 0
        ) {
            foreach ($this->details['footnote'] as $line) {
                $footnote .= $line;
            }
        }
        $dissertation = '';
        if (isset($this->details['dissertationNote'])
            && count($this->details['dissertationNote']) > 0
        ) {
            foreach ($this->details['dissertationNote'] as $line) {
                $dissertation .= $line;
            }
        }
        if (!empty($footnote)) {
            $footnote .= '.';
        }
        if (!empty($footnote) && !empty($dissertation)) {
            $noteStr = $footnote . ' - ' . $dissertation;
        } else {
            $noteStr = $footnote . $dissertation;
        }
        return $noteStr;
    }

    /**
     * Get the publishing string for an ISBD citation.
     * - publishPlace : publisher, publishDate. - physical
     *
     * @return string
     */
    protected function getISBDPublisher()
    {
        $publisher = '';

        if (isset($this->details['pubPlace'])
            && !empty($this->details['pubPlace'])
        ) {
            $publisher = ' — ' . $this->stripPunctuation($this->details['pubPlace']);
        }
        if (isset($this->details['pubName'])
            && !empty($this->details['pubName'])
        ) {
            $publisher = $publisher . ' : '
                . $this->stripPunctuation($this->details['pubName']);
            $publisher
                = (false !== $this->getYear()) ? $publisher .', ' : $publisher;
        }
        if (false !== $this->getYear()) {
            $publisher = $publisher . $this->getYear() . '.';
        }
        $physical = $this->driver->tryMethod('getPhysicalDescriptions');
        if (isset($physical)
            && !empty($physical)
        ) {
            $publisher = $publisher . ' — '
                . $this->stripPunctuation(implode(' ', $physical)) . '.';
        }
        if (empty($publisher)) {
            return false;
        }
        return $publisher;
    }

    /**
     * Get series/ uniform title for an ISBD citation.
     *
     * @return string
     */
    protected function getISBDSeries()
    {
        $series = $this->driver->tryMethod('getSeries');
        if (isset($series) && !empty($series)
        ) {
            if (isset($series[0]) && isset($series[1])) {
                $seriesStr
                  = trim($this->stripPunctuation($series[0]) .' ; ' . $series[1]);
            } else {
                $seriesStr
                  = trim($this->stripPunctuation($series[0]) . $series[1]);
            }
            if (empty($seriesStr)) {
                return false;
            }
            return '(' . $seriesStr . ')';
        }
        return false;
    }

    /**
     * Get the year of publication for inclusion in a citation.
     * Shared by APA and MLA functionality.
     *
     * @return string
     */
    protected function getYear()
    {
        if (isset($this->details['pubDate'])) {
            if (strlen($this->details['pubDate']) > 4) {
                try {
                    return $this->dateConverter->convertFromDisplayDate(
                        'Y', $this->details['pubDate']
                    );
                } catch (\Exception $e) {
                    // Ignore date errors -- no point in dying here:
                    return false;
                }
            }
            return $this->details['pubDate'];
        }
        return false;
    }

    public function isArticle() {
        return !empty($this->details['journal']) ? true : false;
    }
}