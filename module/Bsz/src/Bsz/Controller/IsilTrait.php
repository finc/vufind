<?php

namespace Bsz\Controller;

/**
 * Store and fetch ISILs to/from session
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
trait IsilTrait
{
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
