<?php
/**
 * Email Hold trait (for subclasses of AbstractRecord)
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
use finc\Mailer\Mailer as Mailer;

/**
 * Email Hold trait (for subclasses of AbstractRecord)
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
trait EmailHoldTrait
{
    /**
     * Action for dealing with blocked email holds.
     *
     * @return mixed
     */
    public function blockedEmailHoldAction()
    {
        $this->flashMessenger()
            ->addMessage('EmailHold::email_hold_error_blocked', 'error');
        return $this->redirectToRecord('#top');
    }

    /**
     * Action for dealing with email holds.
     *
     * @return mixed
     */
    public function emailHoldAction()
    {
        $driver = $this->loadRecord();

        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        // If we're not supposed to be here, give up now!
        $catalog = $this->getILS();
        $checkRequests = $catalog->checkFunction(
            'EmailHold',
            [
                'id' => $driver->getUniqueID(),
                'patron' => $patron
            ]
        );
        if (!$checkRequests) {
            return $this->redirectToRecord();
        }

        // Do we have valid information?
        // Sets $this->logonURL and $this->gatheredDetails
        $gatheredDetails = $this->emailHold()->validateRequest(
            $checkRequests['HMACKeys']
        );
        if (!$gatheredDetails) {
            return $this->redirectToRecord();
        }

        // Block invalid requests:
        if (!$catalog->checkEmailHoldIsValid(
            $driver->getUniqueID(), $gatheredDetails, $patron
        )) {
            return $this->blockedEmailHoldAction();
        }

        // Send various values to the view so we can build the form:
        $pickup = $catalog->getPickUpLocations($patron, $gatheredDetails);
        $extraFields = isset($checkRequests['extraFields'])
            ? explode(":", $checkRequests['extraFields']) : [];

        foreach ($catalog->getStatus($gatheredDetails['id']) as $item) {
            if (isset($item['item_id'])
                && $item['item_id'] == $gatheredDetails['item_id']
            ) {
                // make the full status information for the current item available in
                // the view 
                $itemStatus = $item;
            }
        }

        // Process form submissions if necessary:
        if (!is_null($this->params()->fromPost('placeEmailHold'))) {
            // If we made it this far, we're ready to email the hold;
            // if successful, we will redirect and can stop here.

            // Add Patron Data to Submitted Data
            $details = $gatheredDetails + ['patron' => $patron, 'itemStatus' => $itemStatus];

            // Add needed Record Data to Submitted Data
            $details['record'] = [
                'title'      => $driver->getTitle(),
                'author'     => $driver->getPrimaryAuthor(),
            ];

            // Attempt to send the email and show an appropriate flash message:
            try {
                // select the appropriate emailProfile based on the
                // emailProfileSelector setting in the ILS drivers EmailHold section
                // and default to emailProfile EmailHold if setting or profile is not
                // set
                $emailProfile
                    = (
                        isset($checkRequests['emailProfileSelector'])
                        && !empty($checkRequests['emailProfileSelector'])
                    ) &&
                    (
                        isset($gatheredDetails[$checkRequests['emailProfileSelector']])
                        && $this->getEmailProfile($gatheredDetails[$checkRequests['emailProfileSelector']]) != []
                    )
                    ? $this->getEmailProfile($gatheredDetails[$checkRequests['emailProfileSelector']])
                    : $this->getEmailProfile('EmailHold');
                
                $renderer = $this->getViewRenderer();

                // Custom template for emails (html-only)
                $bodyHtml = $renderer->render(
                    'Email/journalhold-html.phtml', $details
                );
                // Custom template for emails (text-only)
                $bodyPlain = $renderer->render(
                    'Email/journalhold-plain.phtml', $details
                );
                $subject = "Zeitschrift von " .
                    $details['patron']['lastname'] . ", " .
                    $details['patron']['firstname'] .
                    " | Signatur: " . $details['callnumber'];

                $from = (isset($details['patron']['email'])) ? $details['patron']['email'] : $emailProfile->from ;
                $to = $emailProfile->to;
                // Get mailer
                $mailer = new Mailer(
                    $this->getServiceLocator()
                        ->get('VuFind\Mailer')->getTransport()
                );

                $mailer->sendTextHtml(
                    $to,
                    $from,
                    $from,
                    '',
                    $subject,
                    $bodyHtml,
                    $bodyPlain
                );

                $this->flashMessenger()->addMessage(
                    'EmailHold::email_hold_place_success', 'success'
                );
                return $this->redirectToRecord('#top');
            } catch (MailException $e) {
                $this->flashMessenger()->addMessage($e->getMessage(), 'error');
            }
        }

        // Find and format the default required date:
        $defaultRequired = $this->emailHold()
            ->getDefaultRequiredDate($checkRequests);
        $defaultRequired = $this->getServiceLocator()->get('VuFind\DateConverter')
            ->convertToDisplayDate("U", $defaultRequired);
        try {
            $defaultPickup
                = $catalog->getDefaultPickUpLocation($patron, $gatheredDetails);
        } catch (\Exception $e) {
            $defaultPickup = false;
        }

        $view = $this->createViewModel(
            [
                'itemStatus' => $itemStatus,
                'gatheredDetails' => $gatheredDetails,
                'pickup' => $pickup,
                'defaultPickup' => $defaultPickup,
                'homeLibrary' => $this->getUser()->home_library,
                'extraFields' => $extraFields,
                'defaultRequiredDate' => $defaultRequired,
                'helpText' => isset($checkRequests['helpText'])
                    ? $checkRequests['helpText'] : null
            ]
        );
        $view->setTemplate('record/emailhold');
        return $view;
    }
}
