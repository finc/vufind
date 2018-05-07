<?php

namespace Bsz\Controller;

use VuFind\Exception\Mail as MailException;
/**
 * Add flash messages to search Controller
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SearchController extends \VuFind\Controller\SearchController
{
        /**
     * Home action
     *
     * @return mixed
     */
    public function homeAction()
    {
        $view = parent::homeAction();
        $msg = getenv('MAINTENANCE_MODE');
        if ($msg != '') {
            $this->FlashMessenger()->addWarningMessage($msg);
        }
        return $view;
    }
}
