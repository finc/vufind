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
     * @param string $to      Recipient email address
     * @param string $from    Sender email address
     * @param string $reply   reply email address
     * @param string $subject Subject line for message
     * @param string $body    Message body
     *
     * @throws MailException
     * @return void
     */
    public function sendTextHtml($to, $from, $reply, $reply_name, $subject, $body_html, $body_text)
    {
        // Validate sender and recipient
        $validator = new \Zend\Validator\EmailAddress();
        if (!$validator->isValid($to)) {
            throw new MailException('Invalid Recipient Email Address');
        }
        if (!$validator->isValid($from)) {
            throw new MailException('Invalid Sender Email Address');
        }
        if (!$validator->isValid($reply)) {
            throw new MailException('Invalid Reply Email Address');
        }

        // Convert all exceptions thrown by mailer into MailException objects:
        try {
            // Send message
            $htmlPart = new MimePart($body_html);
            $htmlPart->type = 'text/html';
            $htmlPart->charset = 'UTF-8';
            $textPart = new MimePart($body_text);
            $textPart->type = 'text/plain';
            $textPart->charset = 'UTF-8';
            $body = new MimeMessage();
            $body->addPart($textPart);
            $body->addPart($htmlPart);

            $alternativePart = new MimePart($body->generateMessage());
            $alternativePart->type = 'multipart/alternative';
            $alternativePart->boundary = $body->getMime()->boundary();
            $alternativePart->charset = 'utf-8';

            $mimeBody = new MimeMessage();
            $mimeBody->addPart($alternativePart);

            //$reply_to = $reply_name . '<' . $reply . '>';
            $reply_to = $reply;

            $message = $this->getNewTextHtmlMessage()
                ->addFrom($from)
                ->addTo($to)
                ->addReplyTo($reply_to)
                ->setBody($mimeBody)
                ->setSubject($subject);
            $message->getHeaders()
                ->get('content-type')
                ->setType('multipart/alternative');
            $this->getTransport()->send($message);
        } catch (\Exception $e) {
            throw new MailException($e->getMessage());
        }
    }
}