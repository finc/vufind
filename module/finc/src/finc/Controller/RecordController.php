<?php
/**
 * Record Controller
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace finc\Controller;
use VuFind\Exception\Mail as MailException;

/**
 * Record Controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class RecordController extends \VuFind\Controller\RecordController
{
    use EblTrait;
    use PdaTrait;
    use EmailHoldTrait;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     */
    public function __construct(\Zend\Config\Config $config)
    {
        // Call standard record controller initialization:
        parent::__construct($config);
    }

    /**
     * Returns the email profile configured in MailForms.ini
     *
     * @param $profile
     * @return array
     */
    protected function getEmailProfile($profile)
    {
        $mailConfig
            = $this->getServiceLocator()->get('VuFind\Config')->get('EmailProfiles');

        if (isset($mailConfig->$profile)) {
            return $mailConfig->$profile;
        } else {
            throw new MailException('Missing email profile: ' + $profile);
        }
    }

    /**
     * Returns rewrite object
     *
     * @return object
     */
    protected function getRewrite()
    {
        return $this->getServiceLocator()->get('finc\Rewrite');
    }

}
