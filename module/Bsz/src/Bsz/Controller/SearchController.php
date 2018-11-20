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
        $dedup = $this->getServiceLocator()->get('Bsz/Config/Dedup');
        $isils = $this->params()->fromQuery('isil');
        if (count($isils) > 0) {
            return $this->processIsil();
        }
        $view = Parent::resultsAction();
        $view->dedup = $dedup->isActive();
        return $view;
    }
    
    /**
     * Redirect to saveIsil Action
     * 
     * @return redirect
     */
    public function processIsil() 
    {
        $isils = $this->params()->fromQuery('isil');
        $uri = $this->getRequest()->getUri();
        // remove isil from params - otherwise we get a redirection loop
        $params = $this->params()->fromQuery();
        unset($params['isil']);
        
        $referer = sprintf("%s://%s%s?%s", $uri->getScheme(), $uri->getHost(),
            $uri->getPath(), http_build_query($params));
        
        $params = [                
            'referer' => $referer,
            'isil' => $isils,
        ];           
        /**
         * TODO: Get this working with toRoute Redirect
         */
        return $this->redirect()->toUrl('/Bsz/saveIsil?'.
                http_Build_query($params));   
    }    
}
