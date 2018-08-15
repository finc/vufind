<?php
/**
 * External catalogue link view helper
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\View\Helper\Root;

/**
 * External catalogue view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class ExternalCatalogueLink extends \Zend\View\Helper\AbstractHelper
{
    /**
     * VuFind configuration
     *
     * @var \Zend\Config\Config
     */
    protected $config;

    /**
     * Context view helper
     *
     * @var \VuFind\View\Helper\Root\Context
     */
    protected $contextHelper;

    /**
     * External Access configuration
     *
     * @var \Zend\Config\Config
     */
    protected $extCatConf;

    /**
     * Current RecordDriver
     *
     * @var \VuFind\RecordDriver $driver
     */
    protected $driver;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     * @param array $extCatConf External catalogue link configuration
     */
    public function __construct(
        $config, $extCatConf = []
    )
    {
        $this->config = $config;
        $this->extCatConf = $extCatConf;
    }

    /**
     * Render appropriate UI controls for an OpenURL link.
     *
     * @param \VuFind\RecordDriver $driver The current recorddriver
     *
     * @return object
     */
    public function __invoke($driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get external access links to other ILS defined by config setting.
     *
     * @access public
     * @return array     Associative array.
     */
    public function getLinks()
    {
        // if configuration empty return unprocessed
        if (!isset($this->extCatConf)
            || count($this->extCatConf) == 0
        ) {
            return [];
        }
        // if institutions empty return unprocessed
        $institutions = $this->driver->tryMethod('getInstitutions');

        if (!isset($institutions) || count($institutions) == 0) {
            return [];
        }
        $i = -1; // iterator of extUrls
        $extUrls = [];

        foreach ($this->extCatConf as $recordType => $accessUrls) {
            $replaceId = null;
            // get identifier of record id type
            switch ($recordType) {
                case "id":
                    $replaceId = $this->driver->getUniqueID();
                    break;
                case "ppn":
                    $replaceId = $this->driver->tryMethod('getRID');
                    break;
                default:
                    $replaceId = $this->driver->tryMethod('get'.ucfirst($recordType));
            }
            foreach ($accessUrls as $institution => $accessUrl) {
                foreach ($accessUrl as $v) {
                    // pre-filter replaceId
                    if (isset($v['filter'])) {
                        $isReplaceId = (
                            true === $this->filterAccessibilityUrl($v['filter'])
                        ) ? $replaceId : null;
                    }
                    // institution filter
                    if (true === in_array($institution, $institutions)
                        && !empty($isReplaceId)
                    ) {
                        $extUrls[++$i]['desc'] = $institution;
                        $extUrls[$i]['url'] = sprintf($v['pattern'], $replaceId);
                    }
                }
            }
        }
        return $extUrls;
    }


    /**
     * Filter accessibility of external url to defined criteria.
     *
     * @param array $filter Filter criteria
     *
     * @return boolean
     * @access protected
     */
    protected function filterAccessibilityUrl($filter)
    {
        foreach ($filter as $driverMethod => $val) {
            $resType = gettype($res = $this->driver->tryMethod(
                ($this->cleanDriverMethod($driverMethod)))
            );
            if (false === $this->isFilterExclusive($driverMethod)) {
                switch ($resType) {
                    case "string":
                        if (is_array($val)) {
                            return (in_array($res, $val)) ? true : false;
                        } else {
                            return ($res == $val) ? true : false;
                        }
                    case "array":
                        if (is_array($val)) {
                            return (count(array_intersect($res, $val)) > 0) ? true : false;
                        } else {
                            return (in_array($val, $res)) ? true : false;
                        }
                    default:
                        return false;
                }
            // @to-do check if is valid that exclusive filter is poorly the
            // negative opposite. Beware of immediately return.
            } else {
                switch ($resType) {
                    case "string":
                        if (is_array($val)) {
                            return (in_array($res, $val)) ? false : true;
                        } else {
                            return ($res == $val) ? false : true;
                        }
                    case "array":
                        if (is_array($val)) {
                            return (count(array_intersect($res, $val)) > 0) ? false : true;
                        } else {
                            return (in_array($val, $res)) ? false : true;
                        }
                    default:
                        return false;
                }
            }
        }
    }

    /**
     * Check if filter is exclusive than default/standard inclusive.
     *
     * @param string $driverMethod    Value of filter
     *
     * @return boolean
     * @access protected
     */
     protected function isFilterExclusive($driverMethod)
     {
         return (0 < preg_match('/^\-(.*)$/', $driverMethod)) ? true : false;
     }

    /**
     * Clean driver method from additional functions for call of RecordDriver
     *
     * @param string $driverMethod    Value of filter
     *
     * @return boolean
     * @access protected
     */
    protected function cleanDriverMethod($driverMethod)
    {
        $match = [];
        return (0 < preg_match('/^\-(.*)$/', $driverMethod, $match))
            ? $match[1] : $driverMethod;

    }


}