<?php
/**
 * ResetPassword Trait
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
    Zend\Validator\Identical,
    Zend\Validator\EmailAddress;

/**
 * ResetPassword Trait
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
trait ResetPasswordTrait
{
    /**
     * Profile to be used for email
     */
    //const ACQUISITION_EMAIL_PROFILE = 'ResetPassword';

    /**
     * Reset password action - Allows the reset password form to appear.
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function resetPasswordAction()
    {
        // Create view
        $view = $this->createResetPasswordViewModel();

        // Set up reCaptcha
        //todo: testen!
        $view->useRecaptcha = $this->recaptcha()->active('resetPassword');

        // Process form submission:
        if ($this->formWasSubmitted('submit', $view->useRecaptcha)) {

            $params = [
                'firstname' => !empty($view->firstname) ? $view->firstname : '',
                'lastname'  => !empty($view->lastname)  ? $view->lastname  : '',
                'username'  => !empty($view->username)  ? $view->username  : '',
                'email'     => !empty($view->email)     ? $view->email     : ''
            ];

            // Validate data submitted by form
            $isValid = true;
            $validatorIdentical = new Identical('');
            $validatorEmailAddress = new EmailAddress();

            if ($validatorIdentical->isValid($params['firstname'])) {
                $this->flashMessenger()
                    ->addMessage('Please enter your firstname.', 'error');
                $isValid = false;
            }
            if ($validatorIdentical->isValid($params['lastname'])) {
                $this->flashMessenger()
                    ->addMessage('Please enter your lastname.', 'error');
                $isValid = false;
            }
            if ($validatorIdentical->isValid($params['username'])) {
                $this->flashMessenger()
                    ->addMessage('Please enter your library card number.', 'error');
                $isValid = false;
            }
            if (!$validatorEmailAddress->isValid($params['email'])) {
                $this->flashMessenger()
                    ->addMessage('Please enter a valid email address.', 'error');
                $isValid = false;
            }
            if (!$isValid) {
                $view->setTemplate('Auth/AbstractBase/resetpassword');
                return $view;
            }

            $params['timestamp'] = date('d.m.Y H:i');

            // Attempt to send the email and show an appropriate flash message:
            try {
                $this->sendResetPasswordEmail($params);
                $this->flashMessenger()->addMessage('reset_password_text', 'success');
                return $this->forwardTo('myresearch', 'home');
            } catch (MailException $e) {
                $this->flashMessenger()->addMessage($e->getMessage(), 'error');
            }
        }

        // Display the template:
        $view->setTemplate('Auth/AbstractBase/resetpassword');
        return $view;
    }

    /**
     * Create a new ViewModel to use as ResetPassword form.
     *
     * @param array  $params         Parameters to pass to ViewModel constructor.
     *
     * @return ViewModel
     */
    protected function createResetPasswordViewModel($params = null)
    {
        // Build view:
        $view = $this->createViewModel($params);

        // Send parameters back to view so form can be re-populated:
        if ($this->getRequest()->isPost()) {
            $view->firstname = $this->params()->fromPost('firstname');
            $view->lastname  = $this->params()->fromPost('lastname');
            $view->username  = $this->params()->fromPost('username');
            $view->email     = $this->params()->fromPost('email');
        }

        return $view;
    }

    /**
     * Send ResetPassword e-mail.
     *
     * @param $params Data to be used for Email template
     *
     * @return void
     * @throws MailException
     */
    protected function sendResetPasswordEmail($params)
    {
        $emailProfile = $this->getEmailProfile('ResetPassword');
        $renderer = $this->getViewRenderer();

        // Custom template for emails (html-only)
        $bodyHtml = $renderer->render(
            'Email/resetpassword-html.phtml', $params
        );
        // Custom template for emails (text-only)
        $bodyPlain = $renderer->render(
            'Email/resetpassword-plain.phtml', $params
        );

        // Build the subject
        $subject = (isset($emailProfile->subject))
            ? sprintf(
                $emailProfile->subject,
                $params['firstname'],
                $params['lastname']
            ) : $this->translate('Reset Password');

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
