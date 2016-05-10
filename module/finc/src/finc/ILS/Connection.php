<?php
/**
 * Catalog Connection Class
 *
 * This wrapper works with a driver class to pass information from the ILS to
 * VuFind.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
namespace finc\ILS;
use VuFind\Exception\ILS as ILSException,
    VuFind\ILS\Driver\DriverInterface,
    VuFind\I18n\Translator\TranslatorAwareInterface;

/**
 * Catalog Connection Class
 *
 * This wrapper works with a driver class to pass information from the ILS to
 * VuFind.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */
class Connection extends \VuFind\ILS\Connection implements TranslatorAwareInterface
{
    /**
     * Check Email Hold
     *
     * A support method for checkFunction(). This is responsible for checking
     * the driver configuration to determine if the system supports storage
     * retrieval requests.
     *
     * @param array $functionConfig The email hold configuration values
     * @param array $params         An array of function-specific params (or null)
     *
     * @return mixed On success, an associative array with specific function keys
     * and values either for placing requests via a form; on failure, false.
     */
    protected function checkMethodEmailHold($functionConfig, $params)
    {
        $response = false;

        // $params doesn't include all of the keys used by
        // placeStorageRetrievalRequest, but it is the best we can do in the context.
        /*$check = $this->checkCapability(
            'placeEmailHold', [$params ?: []]
        );*/
        if (isset($functionConfig['HMACKeys'])) {
            //$response = ['function' => 'placeEmailHold'];
            if (isset($functionConfig['emailTo'])) {
                $response['emailTo'] = $functionConfig['emailTo'];
            }
            if (isset($functionConfig['emailFrom'])) {
                $response['emailFrom'] = $functionConfig['emailFrom'];
            }
            $response['HMACKeys'] = explode(':', $functionConfig['HMACKeys']);
            if (isset($functionConfig['extraFields'])) {
                $response['extraFields'] = $functionConfig['extraFields'];
            }
            if (isset($functionConfig['helpText'])) {
                $response['helpText'] = $this->getHelpText(
                    $functionConfig['helpText']
                );
            }
        }
        return $response;
    }
}
