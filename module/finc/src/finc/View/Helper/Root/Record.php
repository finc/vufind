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
use Zend\View\Exception\RuntimeException, Zend\View\Helper\AbstractHelper;

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
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
    }

    /**
     * Render the link of the type ISN.
     *
     * @param array $issns Array with ISSNS
     *
     * @return string
     */
    public function getLinkISN($issns)
    {
        return $this->renderTemplate(
            'link-isn.phtml', ['issns' => $issns]
        );
    }

    /**
     * Render a (list of) record icons.
     *
     * @return string
     */
    public function getRecordIcon()
    {
        return $this->renderTemplate('record-icon.phtml');
    }

    /**
     * Get the CSS class used to properly render an icon for given value
     *
     * @param string $value Value to convert into CSS class
     *
     * @return string
     */
    public function getRecordIconClass($value)
    {
        return $this->renderTemplate(
            'record-icon-class.phtml', ['value' => $value]
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
     * Render the link of the specified type.
     *
     * @param string $type    Link type
     * @param string $lookfor String to search for at link
     *
     * @return string
     */
    public function getLink($type, $lookfor)
    {
        $link = $this->renderTemplate(
            'link-' . $type . '.phtml',
            [
                'lookfor' => ($type == 'author'
                    ? $this->removeAuthorDates($lookfor) : $lookfor
                )
            ]
        );
        $link .= $this->getView()->plugin('searchTabs')
            ->getCurrentHiddenFilterParams($this->driver->getSourceIdentifier());
        return $link;
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
}