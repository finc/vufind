<?php
/**
 * PDA Trait
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
 * PDA Trait
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
trait PdaTrait
{
    /**
     * Profile to be used for email
     */
    //const PDA_EMAIL_PROFILE = 'Pda';

    /**
     * PDA action - controller method
     *
     * @todo Open issue: Implementation of accession/domain check of user by PAIA.
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function pdaAction()
    {
        // Check with the set accessPermission if the user is authorized to use PDA
        $accessPermission = 'access.PDAForm';

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
            $view->setTemplate('record/pdamessage');
            return $view;
        }

        // User is authorized to use PDA

        // Do we have valid catalog credentials - if we do, use the catalog user data
        // for the form instead
        if (is_array($patron = $this->catalogLogin())) {
            $catalog = $this->getILS();
            $profile = $catalog->getMyProfile($patron);
        }

        // Start collecting params for PDA

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
        $view = $this->createPDAEmailViewModel();

        // Set up reCaptcha
        //todo: testen!
        $view->useRecaptcha = $this->recaptcha()->active('pda');

        // Process form submission
        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {

            // Collect the data submitted by form
            $params['field_of_study'] = !empty($view->pdaFieldOfStudy) ? $view->pdaFieldOfStudy : '';
            $params['statement'] = !empty($view->pdaStatement) ? $view->pdaStatement : '';

            // Validate data submitted by form
            $isValid = true;
            $validatorStrLength = new StringLength(['min' => 10]);
            $validatorIdentical = new Identical('-1');

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
            if ($isValid) {
                // All params are valid, set timestamp for current params set
                $params['timestamp'] = date('d.m.Y H:i');

                // Attempt to send the email and show an appropriate flash message:
                try {
                    $this->sendPdaEmail($params);
                    $this->flashMessenger()->addMessage('PDA::pda_send_success', 'success');
                    return $this->redirectToRecord();
                } catch (MailException $e) {
                    $this->flashMessenger()->addMessage($e->getMessage(), 'error');
                }
            }
        }

        // Display the template:
        $view->setTemplate('record/pdaform');
        return $view;
    }

    /**
     * Create a new ViewModel to use as a PDA-Email form.
     *
     * @param array  $params         Parameters to pass to ViewModel constructor.
     *
     * @return ViewModel
     */
    protected function createPDAEmailViewModel($params = null)
    {
        // Build view:
        $view = $this->createViewModel($params);

        // Load configuration:
        $config = $this->getServiceLocator()->get('VuFind\Config')->get('config');
        $view->fieldOfStudyList = isset($config->CustomSite->field_of_study)
            ? $config->CustomSite->field_of_study->toArray() : [];

        // Send parameters back to view so form can be re-populated:
        if ($this->getRequest()->isPost()) {
            $view->pdaFieldOfStudy = $this->params()->fromPost('pdaFieldOfStudy');
            $view->pdaStatement = $this->params()->fromPost('pdaStatement');
        }

        return $view;
    }

    /**
     * Send PDA order via e-mail.
     *
     * @param $params Data to be used for Email template
     *
     * @return void
     * @throws MailException
     */
    protected function sendPdaEmail($params)
    {
        $emailProfile = $this->getEmailProfile('Pda');
        $renderer = $this->getViewRenderer();

        // Collect the records metadata
        $keyMethodMapper = [
            'id'            => 'getUniqueID',
            'author'        => 'getCombinedAuthors',
            'title'         => 'getTitle',
            'price'         => 'getPrice',
            'publisher'     => 'getPublishers',
            'format'        => 'getFormats',
            'language'      => 'getLanguages',
            'publishDate'   => 'getPublicationDetails',
            'isbn'          => 'getISBNs',
            'physical'      => 'getPhysicalDescriptions',
            'footnote'      => 'getFootnotes',
            'source_id'     => 'getSourceID'
        ];
        $driver = $this->loadRecord();
        foreach ($keyMethodMapper as $var => $method) {
            $params[$var] = $driver->tryMethod($method);
        }
        $params['driver'] = $driver;

        // Custom template for emails (html-only)
        $bodyHtml = $renderer->render(
            'Email/acquisitionpda-html.phtml', $params
        );
        // Custom template for emails (text-only)
        $bodyPlain = $renderer->render(
            'Email/acquisitionpda-plain.phtml', $params
        );

        // Build the subject
        $subject = (isset($emailProfile->subject))
            ? sprintf(
                $emailProfile->subject,
                $params['id'],
                $this->translate('PDA::fos_' . $params['field_of_study']),
                $params['username']
            ) : $this->translate('PDA::Acquisition');

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
