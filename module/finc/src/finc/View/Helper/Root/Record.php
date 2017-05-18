<?php
/**
 * Record driver view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\View\Helper\Root;
use finc\Rewrite;

/**
 * Record driver view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Record extends \VuFind\View\Helper\Root\Record
{
    /**
     * VuFind configuration
     *
     * @var \Zend\Config\Config
     */
    protected $config;

    /**
     * Authentication manager
     *
     * @var \VuFind\Auth\Manager
     */
    protected $manager;

    /**
     * URL helper
     *
     * @var \Zend\View\Helper\Url
     */
    protected $url;

    /**
     * Rewriter
     *
     * @var \finc\Rewrite
     */
    protected $rewrite;

    /**
     * Resolver configuration
     *
     * @var \Zend\Config\Config
     */
    protected $resolverConfig;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     * @param \Zend\View\Helper\Url $helper URL helper
     */
    public function __construct($config = null,
                                \Zend\View\Helper\Url $helper,
                                \VuFind\Auth\Manager $manager,
                                $rewrite,
                                $resolverConfig)
    {
        parent::__construct($config);
        $this->url = $helper;
        $this->manager = $manager;
        $this->rewrite = $rewrite;
        $this->resolverConfig = $resolverConfig;
    }

    /**
     * Render a (list of) record icons.
     *
     * @param string $tpl Define alternative template for record icon. Default:
     *                      record-icon.phtml
     *
     * @return string
     */
    public function getRecordIcon($tpl = 'record-icon')
    {
        return $this->renderTemplate($tpl . '.phtml');
    }

    /**
     * Get the CSS class used to properly render an icon for given value
     *
     * @param string $value Value to convert into CSS class
     * @param string $classfile Define alternative file for icon class without
     *                              suffix. Default: record-icon-class.phtml
     *
     * @return string
     */
    public function getRecordIconClass($value, $classfile = 'record-icon-class')
    {
        return $this->renderTemplate(
            $classfile . '.phtml', ['value' => $value]
        );
    }

    /**
     * Returns if style based icons should be shown (if covers are disabled!)
     *
     * @return bool
     */
    public function showStyleBasedIcons()
    {
        return isset($this->config->Content->showStyleBasedIcons) ?
            $this->config->Content->showStyleBasedIcons : false;
    }

    /**
     * Get external access links to other ILS defined by config setting.
     *
     * @access public
     * @return array    Associative array.
     */
    public function getExternalAccessLinks()
    {
        $i = -1; // iterator of extUrls
        $extUrls = [];

        // if configuration empty return unprocessed
        if (!isset($this->config->ExternalAccess)
            || count($this->config->ExternalAccess) == 0
        ) {
            return [];
        }
        // if institutions empty return unprocessed
        $institutions = $this->driver->tryMethod('getInstitutions');
        if (!isset($institutions) || count($institutions) == 0) {
            return [];
        }

        foreach ($this->config->ExternalAccess as $recordType => $accessUrl) {
            switch ($recordType) {
            case "id":
                $replaceId = $this->driver->getUniqueID();
                break;
            case "ppn":
                $sourceID = $this->driver->tryMethod('getSourceID');
                $replaceId = (isset($sourceID)
                    && true === in_array($sourceID, ["0", "112"]))
                    ? $this->driver->tryMethod('getRID') : null;
                break;
            default:
                $replaceId = null;
            }
            foreach ($accessUrl as $institution => $urlPattern) {
                if (true === in_array($institution, $institutions)
                    && !empty($replaceId)
                ) {
                    $extUrls[++$i]['desc'] = $institution;
                    $extUrls[$i]['url'] = sprintf($urlPattern, $replaceId);
                }
            }
        }
        return $extUrls;
    }

    /**
     * Render the link of the specified type.
     *
     * @param string $type    Link type
     * @param string $lookfor String to search for at link
     *
     * @return string
     */
    public function getLink($type, $lookfor)
    {
        $lookfor = ($type == 'author'
            ? $this->removeAuthorDates($lookfor) : $lookfor
        );
        return parent::getLink($type, $lookfor);
    }

    /**
     * Get all the links associated with this record.  Returns an array of
     * associative arrays each containing 'desc' and 'url' keys.
     *
     * @param bool $openUrlActive Is there an active OpenURL on the page?
     *
     * @return array
     */
    public function getLinkDetails($openUrlActive = false)
    {
        $links = parent::getLinkDetails($openUrlActive);
        return $this->rewriteLinks($links);
    }

    /**
     * Map list of multiple dates to a view of date range as string e.g.
     * startdate - enddate Sorting of array should be managed by backend.
     *
     * @param array dates
     *
     * @return strings
     */
    public function mapDateListToRangeView($dates)
    {
        if (is_array($dates)) {
            if (count($dates) == 1) {
                return $dates[0];
            } else {
                return array_shift($dates) . '-' . array_pop($dates);
            }
        }
        return $dates;
    }

    /**
     * Remove author dates from author string (used for using author names as search
     * term).
     *
     * @param string authordata
     *
     * @return strings
     */
    public function removeAuthorDates( $author )
    {
        $match = array();
        if (preg_match('/^(\s|.*)\s(fl.\s|d.\s|ca.\s|\*)*\s?(\d{4})\??(\sor\s\d\d?)?\s?(-|–)?\s?(ca.\s|after\s|†)?(\d{1,4})?(.|,)?$/Uu', $author, $match))
        {
            $author = (isset($match[1])) ? trim($match[1]) : $author;
        }
        // delete unnormalized characters of gallica ressource with source_id:20
        if (preg_match('/(.*)(\d.*)/Uus', $author, $match))
        {
            $author = (isset($match[1])) ? trim($match[1]) : $author;
        }
        return $author;
    }

    /**
     * Rewrite links if defined
     *
     * @param array $links List with links schema [url] and [desc] for description.
     *
     * @access protected
     * @return array $links Return processed links.
     */
    protected function rewriteLinks($links = [])
    {
        // if configuration empty return unprocessed
        if (!isset($this->config->LinksRewrite)
            || count($this->config->LinksRewrite) == 0
        ) {
            return $links;
        }

        // if links list empty return unprocessed
        if (count($links) == 0) {
            return $links;
        }

        foreach ($links as &$link) {
            $link['url'] = $this->rewriteLink($link['url']);
        }

        return $links;
    }

    /**
     * Rewrite link
     *
     * @param string $link Link to rewrite
     *
     * @access protected
     * @return string $link Return processed link.
     */
    protected function rewriteLink($link)
    {
        $rewrite = $this->config->LinksRewrite->toArray();
        foreach ($rewrite as $r) {
            // is pattern set so try rewrite url
            if (isset($r['pattern'])) {

                // is search and replace set so try to rewrite url
                if (isset($r['search']) && isset($r['replace'])) {
                    // check if pattern exists. if at least one match than continue
                    if (0 != preg_match('/' . $r['pattern'] . '/i', trim($link))) {
                        // prepare search pattern
                        // should be free of conflicting meta characters
                        $pattern = str_replace(array('.'), array('\.'), $r['search']);
                        $pattern = '/(' . $pattern . ')/i';
                        // replace it only one time
                        $link = preg_replace($pattern, trim($r['replace']), trim($link), 1, $count);
                        // add http if needed
                        // @todo make it https compatible
                        if (!preg_match('/^(http:\/\/)/', $link)) {
                            $link = 'http://' . $link;
                        }
                    }
                }
                // is method set so call alternatively method proceed link
                if (isset($r['method']) && method_exists($this, $r['method'])) {
                    /* && $count > 0) { @todo fix */
                    if (0 != preg_match('/' . $r['pattern'] . '/i', trim($link))) {
                        $link = $this->$r['method']($link);
                    }
                } // end if isset method

            } // end if isset pattern

        } // end foreach
        return $link;
    }

    /**
     * Resolve Rewrite/Schweitzer Url
     *
     * @param string $link Link to rewrite
     *
     * @access protected
     * @return string $link Return processed link.
     */
    protected function resolveEblLink($link)
    {
        if (false === ($user = $this->manager->isLoggedIn())) {
            $id = $this->driver->getUniqueId();
            return $this->url->__invoke(
                'record-ebllink', [], ['query' => ['link' => $link, 'id' => $id]]
            );
        }
        $url = $this->rewrite->resolveLink($link, $user);
        return $url;
    }

    /**
     * Customized method for multi resolver support
     *
     * Get all the links associated with this record depending on the OpenURL setting
     * replace_other_urls.  Returns an array of associative arrays each containing
     * 'desc' and 'url' keys.
     *
     * @return bool
     */
    protected function hasOpenUrlReplaceSetting()
    {
        return isset($this->resolverConfig->General->replace_other_urls)
        && $this->resolverConfig->General->replace_other_urls;
    }
}