<?php
/**
 * EmailProfile Trait
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Controller
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace finc\Controller\CustomTraits;

/**
 * EmailProfile Trait
 *
 * @category VuFind
 * @package  Controller
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
trait EmailProfileTrait
{
    /*
     * Important:
     * Usage of this Trait requires that the including class implements the
     * Zend\Log\LoggerAwareInterface and uses VuFind\Log\LoggerAwareTrait
     */

    /**
     * Returns the email profile configured in MailForms.ini
     *
     * @param $profile
     * @return array
     */
    protected function getEmailProfile($profile)
    {
        $mailConfig
            = $this->serviceLocator->get('VuFind\Config')->get('EmailProfiles');

        if (isset($mailConfig->$profile)) {
            return $mailConfig->$profile;
        } else {
            $this->debug('Missing email profile: ' . $profile);
            return [];
        }
    }
}
