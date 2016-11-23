<?php
/**
 * Model for PDA MARC records in Solr.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  RecordDrivers
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace finc\RecordDriver;

use VuFindHttp\HttpServiceAwareInterface as HttpServiceAwareInterface;

/**
 * Model for PDA MARC records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrMarcFincPDA extends SolrMarcFinc implements
    HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Get the price in Euro for the record.
     *
     * @return string Price in Euro for the record. Currency will be added in view
     *                via SafeMoneyFormat View-Helper
     */
    public function getPrice()
    {
        // currency format should be conform to ISO 4217
        $currency = $this->getFirstFieldValue('365', ['c']);
        $price = $this->getFirstFieldValue('365', ['b']);
        $ecbEuroUrl = "http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml";

        if (!empty($currency) && !empty($price)) {
            // if currency format is not EUR try to convert the value using daily
            // updated xml of ECB
            if ($currency != "EUR") {
                try {
                    $response = $this->httpService->get($ecbEuroUrl);
                } catch (\Exception $e) {
                    $this->debug("Could not retrieve Euro exchange rate from url:" . 
                        $ecbEuroUrl . "\nExited with exception: " . 
                        $e->getMessage());
                }

                if ($response->isSuccess()) {
                    if (false !== ($xml = simplexml_load_string($response->getBody()))
                    ) {
                        foreach ($xml->Cube->Cube->Cube as $rate) {
                            if ($rate['currency']->__toString() == $currency) {
                                // conversion rate available for current currency, so
                                // return the converted price
                                return ((1 / $rate['rate']->__toString()) * $price);
                            }
                        }
                    }
                }
            }
        }
        return !empty($price) ? $price : '';
    }

    /**
     * Do we have an attached ILS connection?
     *
     * @return bool
     */
    protected function hasILS()
    {
        return false;
    }
}