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
    
    public function resultsAction()
    {
        // Special case -- redirect tag searches.
        $tag = $this->params()->fromQuery('tag');
        if (!empty($tag)) {
            $query = $this->getRequest()->getQuery();
            $query->set('lookfor', $tag);
            $query->set('type', 'tag');
        }
        if ($this->params()->fromQuery('type') == 'tag') {
            // Because we're coming in from a search, we want to do a fuzzy
            // tag search, not an exact search like we would when linking to a
            // specific tag name.
            $query = $this->getRequest()->getQuery()->set('fuzzy', 'true');
            return $this->forwardTo('Tag', 'Home');
        }

        // Default case -- standard behavior.
        return parent::resultsAction();
    }
}
