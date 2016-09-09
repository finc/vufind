<?php
/**
 * VuFind Mailer Class
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2009.
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
 * @package  Mailer
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\Mailer;
use VuFind\Exception\Mail as MailException,
    Zend\Mail\Address,
    Zend\Mail\AddressList,
    Zend\Mail\Message,
    Zend\Mime\Message as MimeMessage,
    Zend\Mime\Part as MimePart,
    Zend\Mime as Mime;

/**
 * VuFind Mailer Class
 *
 * @category VuFind
 * @package  Mailer
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class Mailer extends \VuFind\Mailer\Mailer
{
    /**
     * Get a blank html email message object.
     *
     * @return Message
     */
    public function getNewTextHtmlMessage()
    {
        $message = new Message();
        $message->setEncoding('UTF-8');
        return $message;
    }

    /**
     * Send an email message.
     *
     * @param string|Address|AddressList $to         Recipient email address (or
     * delimited list)
     * @param string|Address             $from       Sender name and email address
     * @param string|Address             $reply      Reply name and email address
     * @param string                     $subject    Subject line for message
     * @param string                     $body_html  HTML message body
     * @param string                     $body_text  Plain text message body
     *
     * @throws MailException
     * @return void
     */
    public function sendTextHtml($to, $from, $reply, $subject, $body_html, 
         $body_text
    ) {
        if ($to instanceof AddressList) {
            $recipients = $to;
        } else if ($to instanceof Address) {
            $recipients = new AddressList();
            $recipients->add($to);
        } else {
            $recipients = $this->stringToAddressList($to);
        }

        // Validate email addresses:
        $validator = new \Zend\Validator\EmailAddress();
        if (count($recipients) == 0) {
            throw new MailException('Invalid Recipient Email Address');
        }
        foreach ($recipients as $current) {
            if (!$validator->isValid($current->getEmail())) {
                throw new MailException('Invalid Recipient Email Address');
            }
        }
        
        $fromEmail = ($from instanceof Address)
            ? $from->getEmail() : $from;
        if (!$validator->isValid($fromEmail)) {
            throw new MailException('Invalid Sender Email Address');
        }

        $replyEmail = ($reply instanceof Address)
            ? $reply->getEmail() : $reply;
        if (!empty($reply) && !$validator->isValid($replyEmail)) {
            throw new MailException('Invalid Reply Email Address');
        }

        // Convert all exceptions thrown by mailer into MailException objects:
        try {
            // Send message

            // html body is optional
            if (!empty($body_html)) {
                $htmlPart = new MimePart($body_html);
                $htmlPart->type = 'text/html';
                $htmlPart->charset = 'UTF-8';
            }

            $textPart = new MimePart($body_text);
            $textPart->type = 'text/plain';
            $textPart->charset = 'UTF-8';
            $body = new MimeMessage();
            $body->addPart($textPart);

            // html body is optional
            if (isset($htmlPart)) {
                $body->addPart($htmlPart);
            }

            $alternativePart = new MimePart($body->generateMessage());
            $alternativePart->type = 'multipart/alternative';
            $alternativePart->boundary = $body->getMime()->boundary();
            $alternativePart->charset = 'utf-8';

            $mimeBody = new MimeMessage();
            $mimeBody->addPart($alternativePart);

            $message = $this->getNewTextHtmlMessage()
                ->addFrom($from)
                ->addTo($recipients)
                ->setBody($mimeBody)
                ->setSubject($subject);

            if (!empty($reply)) {
                $message->addReplyTo($reply);
            }

            $message->getHeaders()
                ->get('content-type')
                ->setType('multipart/alternative');
            $this->getTransport()->send($message);
        } catch (\Exception $e) {
            throw new MailException($e->getMessage());
        }
    }
}