<?php
namespace Bsz\Controller;

/**
 * Add flash messages to search Controller
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SearchController extends \VuFind\Controller\SearchController
{
    use IsilTrait;

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
            $this->flashMessenger()->addWarningMessage($msg);
        }
        $request = $this->getRequest();
        $referer = $request->getHeader('referer');

        if (is_object($referer) &&
            (strpos($referer->getUri(), '.boss') !== false
                || strpos($referer->getUri(), 'localhost') !== false)
        ) {
            $view->referer = $referer;
        }
        return $view;
    }

    public function resultsAction()
    {
        $dedup = $this->serviceLocator->get('Bsz\Config\Dedup');
        $isils = $this->params()->fromQuery('isil');
        if ($isils) {
            return $this->processIsil();
        }
        $view = Parent::resultsAction();
        $view->dedup = $dedup->isActive();
        return $view;
    }
}
