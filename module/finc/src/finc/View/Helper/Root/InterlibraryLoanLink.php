<?php
/**
 * Record link view helper
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
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace finc\View\Helper\Root;

use Zend\View\Helper\AbstractHelper;

/**
 * Record link view helper
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class InterlibraryLoanLink extends AbstractHelper
{

    /**
     * Given a record driver, generate a URL for interlibrary loans to SWB.
     *
     * @param \VuFind\RecordDriver\AbstractBase $driver Host Record.
     *
     * @return string
     */
    public function getSwbLink($driver)
    {
        $url_search = "http://flportal.bsz-bw.de/servlet/locator?sigel=15";
        $url_default = "http://flportal.bsz-bw.de/jsp/start.jsp?sigel=15";

        foreach (array('issn', 'isbn') as $signifier) {
            $method = "getClean" . strtoupper($signifier);
            $$signifier = $driver->$method();
            if (!empty($$signifier)) {
                return $url_search . "&" . $signifier . "=" . $$signifier;
            }
        }
        return $url_default;
    }
}

