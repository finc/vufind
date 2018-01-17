<?php
/**
 * Fernleihe Controller
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @category VuFind2
 * @package  Controller
 * @author   Cornelius Amzar <cornelkius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Bsz\Controller;

use VuFind\Exception\Mail as MailException;
use VuFind\Solr\Utils as SolrUtils;
use Zend\Stdlib\Parameters;
use Zend\View\Model\ViewModel;


/**
 * Controller for Interlending tab
 *
 * @category VuFind2
 * @package  Controller
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class InterlendingController extends \VuFind\Controller\SearchController {
    
    protected $searchClassId = 'Interlending';
    
    /**
     * Handle an advanced search
     *
     * @return mixed
     */
    public function advancedAction()
    {
        // Standard setup from base class:
        $view = parent::advancedAction();

        // Set up facet information:
        $view->facetList = $this->processAdvancedFacets(
            $this->getAdvancedFacets()->getFacetList(), $view->saved
        );
        $specialFacets = $this->parseSpecialFacetsSetting(
            $view->options->getSpecialAdvancedFacets()
        );
        if (isset($specialFacets['illustrated'])) {
            $view->illustratedLimit
                = $this->getIllustrationSettings($view->saved);
        }
        if (isset($specialFacets['checkboxes'])) {
            $view->checkboxFacets = $this->processAdvancedCheckboxes(
                $specialFacets['checkboxes'], $view->saved
            );
        }
        $view->ranges = $this->getAllRangeSettings($specialFacets, $view->saved);
        $view->hierarchicalFacets = $this->getHierarchicalFacets();

        return $view;
    }
    /**
     * Results action.
     *
     * @return mixed
     */
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
            return $this->forwardTo('Tag', 'Home');
        }        
        $view = parent::resultsAction();
        //QnD get Link to /InterlendingRecord/<id> insteadof /Record/<id>
        $view->overrideRecordLink = $this->searchClassId;
        $this->layout()->setVariable('overrideRecordLink', $this->searchClassId);
        $client = $this->getServiceLocator()->get('bsz\client');
        if ($client->isIsilSession() && !$client->hasIsilSession()) {
            $this->FlashMessenger()->addErrorMessage('missing_isil');
        }
        
        return $view;
    }
    
    public function homeAction() {
        $view = parent::homeAction();
        $view->overrideRecordLink = $this->searchClassId;
        $this->layout()->setVariable('overrideRecordLink', $this->searchClassId);
        return $view;
    } 
    
    
}
