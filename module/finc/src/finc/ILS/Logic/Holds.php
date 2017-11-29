<?php
/**
 * Hold Logic Class
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2007.
 * Copyright (C) Leipzig University Library 2016.
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
 * @package  ILS_Logic
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace finc\ILS\Logic;

use VuFind\ILS\Connection as ILSConnection;

/**
 * Hold Logic Class
 *
 * @category VuFind
 * @package  ILS_Logic
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Holds extends \VuFind\ILS\Logic\Holds
{
    /**
     * Support method to rearrange the holdings array for displaying convenience.
     *
     * @param array $holdings An associative array of location => item array
     *
     * @return array          An associative array keyed by location with each
     * entry being an array with 'notes', 'summary' and 'items' keys.  The 'notes'
     * and 'summary' arrays are note/summary information collected from within the
     * items.
     */
    protected function formatHoldings($holdings)
    {
        $retVal = [];

        // Handle purchase history alongside other textual fields
        $textFieldNames = $this->catalog->getHoldingsTextFieldNames();
        // TODO: handle purchase history as is done in super class ?!
        $textFieldNames[] = 'purchase_history';

        foreach ($holdings as $groupKey => $items) {
            $retVal[$groupKey] = [
                'items' => $items,
                'location' => isset($items[0]['location'])
                    ? $items[0]['location'] : '',
                'locationid' => isset($items[0]['locationid'])
                    ? $items[0]['locationid'] : '',
                'locationhref' => isset($items[0]['locationhref'])
                    ? $items[0]['locationhref'] : ''
            ];
            // Copy all text fields from the item to the holdings level
            foreach ($items as $item) {
                foreach ($textFieldNames as $fieldName) {
                    if (in_array($fieldName, ['notes', 'holdings_notes'])) {
                        if (empty($item[$fieldName])) {
                            // begin aliasing
                            if ($fieldName == 'notes'
                                && !empty($item['holdings_notes'])
                            ) {
                                // using notes as alias for holdings_notes
                                $item[$fieldName] = $item['holdings_notes'];
                            } elseif ($fieldName == 'holdings_notes'
                                && !empty($item['notes'])
                            ) {
                                // using holdings_notes as alias for notes
                                $item[$fieldName] = $item['notes'];
                            }
                        }
                    }

                    if (!empty($item[$fieldName])) {
                        $targetRef
                            = &$retVal[$groupKey]['textfields'][$fieldName];
                        foreach ((array)$item[$fieldName] as $field) {
                            if (empty($targetRef)
                                || !in_array(
                                    $field, $targetRef
                                )
                            ) {
                                $targetRef[] = $field;
                            }
                        }
                    }
                }
            }
        }

        return $retVal;
    }

    /**
     * Public method for getting item holdings from the catalog and selecting which
     * holding method to call
     *
     * @param string $id  A Bib ID
     * @param array  $ids A list of Source Records (if catalog is for a consortium)
     *
     * @return array A sorted results set
     */
    public function getHoldings($id, $ids = null)
    {
        $holdings = [];

        // Get Holdings Data
        if ($this->catalog) {
            // Retrieve stored patron credentials; it is the responsibility of the
            // controller and view to inform the user that these credentials are
            // needed for hold data.
            try {
                $patron = $this->ilsAuth->storedCatalogLogin();

                // Does this ILS Driver handle consortial holdings?
                $config = $this->catalog->checkFunction(
                    'Holds', compact('id', 'patron')
                );
            } catch (ILSException $e) {
                $patron = false;
                $config = [];
            }

            if (isset($config['consortium']) && $config['consortium'] == true) {
                $result = $this->catalog->getConsortialHoldings(
                    $id, $patron ? $patron : null, $ids
                );
            } else {
                $result = $this->catalog->getHolding(
                    $id, $patron ? $patron : null
                );
            }

            $grb = 'getRequestBlocks'; // use variable to shorten line below:
            $blocks
                = $patron
            && $this->catalog->checkCapability(
                $grb, compact($patron)
            )
                ? $this->catalog->getRequestBlocks($patron) : false;

            $mode = $this->catalog->getHoldsMode();

            if ($mode == "disabled") {
                $holdings = $this->standardHoldings($result);
            } else {
                if ($mode == "driver") {
                    $holdings = $this->driverHoldings(
                        $result, $config, !empty($blocks)
                    );
                } else {
                    $holdings = $this->generateHoldings(
                        $result, $mode, $config
                    );
                }
            }

            $holdings = $this->processStorageRetrievalRequests(
                $holdings, $id, $patron, !empty($blocks)
            );
            $holdings = $this->processILLRequests(
                $holdings, $id, $patron, !empty($blocks)
            );
            $holdings = $this->processEmailHolds(
                $holdings, $id, $patron, !empty($blocks)
            );
        }
        return [
            'blocks' => $blocks,
            'holdings' => $this->formatHoldings($holdings)
        ];
    }

    /**
     * Process email holds information in holdings and set the links
     * accordingly.
     *
     * @param array  $holdings Holdings
     * @param string $id       Record ID
     * @param array  $patron   Patron
     *
     * @return array Modified holdings
     */
    protected function processEmailHolds(
        $holdings, $id, $patron, $requestsBlocked
    )
    {
        if (!is_array($holdings)) {
            return $holdings;
        }

        $requestConfig = $this->catalog->checkFunction(
            'EmailHold', compact('id', 'patron')
        );

        if (!$requestConfig) {
            return $holdings;
        }

        foreach ($holdings as &$location) {
            foreach ($location as &$copy) {
                if (!$requestsBlocked && isset($copy['addEmailHoldLink'])
                    && $copy['addEmailHoldLink']
                ) {
                    $copy['emailHoldLink'] = $this->getRequestDetails(
                        $copy,
                        $requestConfig['HMACKeys'],
                        'EmailHoldLink'
                    );

                    $copy['checkEmailHold']
                        = $copy['addEmailHoldLink'] === 'check';
                }
            }
        }
        return $holdings;
    }
}
