<?php
/**
 * Acquisition Trait
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
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace finc\Controller\CustomTraits;
use VuFind\Exception\Mail as MailException,
    finc\Mailer\Mailer,
    Zend\Mail\Address,
    Zend\Validator\StringLength,
    Zend\Validator\Identical;

/**
 * Acquisition Trait
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
trait AcquisitionTrait
{
    /**
     * Profile to be used for email
     */
    //const ACQUISITION_EMAIL_PROFILE = 'Acquisition';

    /**
     * Acquisition action - controller method
     *
     * @todo Open issue: Implementation of accession/domain check of user by PAIA.
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function acquisitionAction()
    {
        // Check with the set accessPermission if the user is authorized to use PDA
        $accessPermission = 'access.AcquisitionForm';

        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $auth = $this->getAuthorizationService();
        if (!$auth) {
            throw new \Exception('Authorization service missing');
        }

        if (!$auth->isGranted($accessPermission)) {
            $this->flashMessenger()->addMessage('PDA::pda_restriction_text', 'error');
            $view = $this->createViewModel();
            return $this->forwardTo('myresearch', 'home');
        }

        // User is authorized to use Acquisition

        // Do we have valid catalog credentials - if we do, use the catalog user data
        // for the form instead
        if (is_array($patron = $this->catalogLogin())) {
            $catalog = $this->getILS();
            $profile = $catalog->getMyProfile($patron);
        }

        // Start collecting params for Acquisition email

        // prefer profile data from ILS over user data from db
        $params = [
            'username'  => trim($user->cat_username),
            'email'     => isset($profile['email'])
                ? trim($profile['email']) : trim($user->email),
            'firstname' => isset($profile['firstname'])
                ? trim($profile['firstname']) : trim($user->firstname),
            'lastname'  => isset($profile['lastname'])
                ? trim($profile['lastname']) : trim($user->lastname),
            'group' => isset($profile['group'])
                ? trim($profile['group']) : ''
        ];

        // Create view
        $view = $this->createAcquisitionViewModel();

        // Set up reCaptcha
        //todo: testen!
        $view->useRecaptcha = $this->recaptcha()->active('acquisition');

        // Process form submission
        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {

            // Collect the data submitted by form
            $params['field_of_study'] = !empty($view->acquisitionFieldOfStudy) ? $view->acquisitionFieldOfStudy : '';
            $params['proposal']       = !empty($view->acquisitionProposal)     ? $view->acquisitionProposal     : '';
            $params['statement']      = !empty($view->acquisitionStatement)    ? $view->acquisitionStatement    : '';

            // Validate data submitted by form
            $isValid = true;
            $validatorStrLength = new StringLength(['min' => 10]);
            $validatorIdentical = new Identical('-1');

            if (!$validatorStrLength->isValid($params['proposal'])) {
                $this->flashMessenger()
                    ->addMessage('PDA::pda_error_proposal_blank', 'error');
                $isValid = false;
            }
            if (!$validatorStrLength->isValid($params['statement'])) {
                $this->flashMessenger()
                    ->addMessage('PDA::pda_error_statement_blank', 'error');
                $isValid = false;
            }
            if ($validatorIdentical->isValid($params['field_of_study'])) {
                $this->flashMessenger()
                    ->addMessage('PDA::pda_error_field_of_study_blank', 'error');
                $isValid = false;
            }
            if (!$isValid) {
                $view->setTemplate('myresearch/acquisition');
                return $view;
            }

            // All params are valid, set timestamp for current params set
            $params['timestamp'] = date('d.m.Y H:i');

            // Attempt to send the email and show an appropriate flash message:
            try {
                $this->sendAcquisitionEmail($params);
                $this->flashMessenger()->addMessage('PDA::pda_send_success', 'success');
                return $this->forwardTo('myresearch', 'home');
            } catch (MailException $e) {
                $this->flashMessenger()->addMessage($e->getMessage(), 'error');
            }
        }

        // Display the template:
        $view->setTemplate('myresearch/acquisition');
        return $view;
    }

    /**
     * Create a new ViewModel to use as Acquisition form.
     *
     * @param array  $params         Parameters to pass to ViewModel constructor.
     *
     * @return ViewModel
     */
    protected function createAcquisitionViewModel($params = null)
    {
        // Build view:
        $view = $this->createViewModel($params);

        // Load configuration:
        $config = $this->getServiceLocator()->get('VuFind\Config')->get('config');

        $view->fieldOfStudyList = isset($config->CustomSite->field_of_study)
            ? $config->CustomSite->field_of_study->toArray() : [];

        // Send parameters back to view so form can be re-populated:
        if ($this->getRequest()->isPost()) {
            $view->acquisitionFieldOfStudy = $this->params()->fromPost('field_of_study');
            $view->acquisitionProposal = $this->params()->fromPost('proposal');
            $view->acquisitionStatement = $this->params()->fromPost('reasons');
        }

        return $view;
    }

    /**
     * Send Acquisition order via e-mail.
     *
     * @param $params Data to be used for Email template
     *
     * @return void
     * @throws MailException
     */
    protected function sendAcquisitionEmail($params)
    {
        $emailProfile = $this->getEmailProfile('Acquisition');
        $renderer = $this->getViewRenderer();

        // Custom template for emails (html-only)
        $bodyHtml = $renderer->render(
            'Email/acquisition-html.phtml', $params
        );
        // Custom template for emails (text-only)
        $bodyPlain = $renderer->render(
            'Email/acquisition-text.phtml', $params
        );

        // Build the subject
        $subject = (isset($emailProfile->subject))
            ? sprintf(
                $emailProfile->subject,
                $this->translate('PDA::fos_' . $params['field_of_study']),
                $params['username']
            ) : $this->translate('PDA::pda_form_title');

        // Set reply address and name if available
        $reply = (isset($params['email'], $params['firstname'], $params['lastname']))
            ? new Address($params['email'], $params['firstname'] . ' ' . $params['lastname'])
            : null;

        // Get mailer
        $mailer = new Mailer(
            $this->getServiceLocator()
                ->get('VuFind\Mailer')->getTransport()
        );

        // Send the email
        $mailer->sendTextHtml(
            new Address($emailProfile->to),
            new Address($emailProfile->from),
            $reply,
            $subject,
            $bodyHtml,
            $bodyPlain
        );
    }
}
