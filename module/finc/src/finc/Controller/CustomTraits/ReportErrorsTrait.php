<?php
/**
 * ReportErrors Trait
 *
 * PHP version 5
 *
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
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
namespace finc\Controller\CustomTraits;
use VuFind\Exception\Mail as MailException,
    finc\Mailer\Mailer,
    Zend\Mail\Address,
    Zend\Validator\StringLength,
    Zend\Validator\Identical;

/**
 * ReportErrors Trait
 *
 * @category VuFind
 * @package  Controller
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
trait ReportErrorsTrait
{
    /**
     * PDA action - controller method
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function reportErrorsAction()
    {
        // Force login if necessary:
        $config = $this->getConfig();
        if ((!isset($config->Mail->require_login) || $config->Mail->require_login)
            && !$this->getUser()
        ) {
            return $this->forceLogin();
        }

        $params['email'] = null;
        if ($user = $this->getUser()) {
            $params['email'] = trim($user->email);
        }

        // Create view
        $view = $this->createReportErrorsEmailViewModel($params);

        // Set up reCaptcha
        //todo: testen!
        $view->useRecaptcha = $this->recaptcha()->active('reportErrors');

        // Process form submission
        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {

            // Collect the data submitted by form
            $params['reply_requested'] = $view->reply_requested;
            $params['email']  = !empty($view->email) ? $view->email : '';
            if ($view->reply_requested) {
                if ($user) {
                    $params['firstname'] = trim($user->firstname);
                    $params['lastname'] = trim($user->lastname);
                }
            }
            $params['comment']    = !empty($view->comment) ? $view->comment : '';

            // Validate data submitted by form
            $validatorStrLength = new StringLength(['min' => 10]);

            if (!$validatorStrLength->isValid($params['comment'])) {
                $this->flashMessenger()
                    ->addMessage('report_errors_comment_blank', 'error');
            } else {
                // All params are valid, set timestamp for current params set
                $params['timestamp'] = date('d.m.Y H:i');

                // Attempt to send the email and show an appropriate flash message:
                try {
                    $this->sendReportErrorsEmail($params);
                    $this->flashMessenger()->addMessage('report_errors_send_success', 'success');
                    return $this->redirectToRecord();
                } catch (MailException $e) {
                    $this->flashMessenger()->addMessage($e->getMessage(), 'error');
                }
            }
        }

        // Display the template:
        $view->setTemplate('record/reporterrorsform');
        return $view;
    }

    /**
     * Create a new ViewModel to use as a PDA-Email form.
     *
     * @param array  $params         Parameters to pass to ViewModel constructor.
     *
     * @return ViewModel
     */
    protected function createReportErrorsEmailViewModel($params = null)
    {
        // Build view:
        $view = $this->createViewModel($params);

        // Send parameters back to view so form can be re-populated:
        if ($this->getRequest()->isPost()) {
            $view->email = $this->params()->fromPost('reporterrors_email');
            $view->reply_requested = ($this->params()->fromPost('reporterrors_checkbox') ? true : false);
            $view->comment = $this->params()->fromPost('comment');
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
    protected function sendReportErrorsEmail($params)
    {
        $emailProfile = $this->getEmailProfile('ReportErrors');
        $renderer = $this->getViewRenderer();

        // Collect the records metadata
        $keyMethodMapper = [
            'id'            => 'getUniqueID',
            'author'        => 'getCombinedAuthors',
            'title'         => 'getTitle',
            'title_short'   => 'getShortTitle'
        ];
        $driver = $this->loadRecord();
        foreach ($keyMethodMapper as $var => $method) {
            $params[$var] = $driver->tryMethod($method);
        }
        $params['driver'] = $driver;

        // Custom template for emails (html-only)
        $bodyHtml = $renderer->render(
            'Email/reporterrors-html.phtml', $params
        );
        // Custom template for emails (text-only)
        $bodyPlain = $renderer->render(
            'Email/reporterrors-plain.phtml', $params
        );

        // Build the subject
        $subject = (isset($emailProfile->subject))
            ? sprintf(
                $emailProfile->subject,
                $params['title_short'],
                $params['id'],
                ($params['reply_requested'] == true 
                    ? $this->translate('reporterrors_response_requested_subject') 
                    : ''
                )
            ) : $this->translate('ReportErrors');

        // Set reply address and name if available
        if ($params['reply_requested'] == true) {
            $replyTo = !empty($params['email']) ? $params['email'] : $emailProfile->from;
            $replyToName
                = (isset($params['firstname']) && isset($params['lastname'])) && !empty($replyTo)
                ? $params['firstname'] . ' ' . $params['lastname']
                : '';
            $reply = new Address($replyTo, $replyToName);
        }

        // Get mailer
        $mailer = new Mailer(
            $this->getServiceLocator()
                ->get('VuFind\Mailer')->getTransport()
        );

        // Send the email
        $mailer->sendTextHtml(
            new Address($emailProfile->to),
            new Address($emailProfile->from),
            isset($reply) ? $reply : $emailProfile->from,
            $subject,
            $bodyHtml,
            $bodyPlain
        );
    }
}
