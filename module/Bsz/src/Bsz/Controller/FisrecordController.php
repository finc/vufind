<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Bsz\Controller;

/**
 * This Controller is needed to provide an alternative route for the interlending
 * detail pages. This is required because otherwise the active tab switches
 * back to search
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class FisrecordController extends \Bsz\Controller\RecordController {
    
    public function __construct(\Zend\Config\Config $config)
    {
        // Override some defaults:
        $this->searchClassId = 'Fis';
        $this->fallbackDefaultTab = isset($config->Site->defaultRecordTab)
            ? $config->Site->defaultRecordTab : 'Holdings';

        // Call standard record controller initialization:
        parent::__construct($config);
    }
    
        /**
     * Is the result scroller active?
     *
     * @return bool
     */
    protected function resultScrollerActive()
    {
        $config = $this->getServiceLocator()->get('VuFind\Config')->get('Search');
        return (bool)(isset($config->Record->next_prev_navigation)
            && $config->Record->next_prev_navigation);
    }
       
    public function getUniqueId() {
        return parent::getUniqueId();
    }

    public function getBreadcrumb() {
        return parent::getBreadcrumb();
    }
    /**
     * 
     * @return View
     */
    public function homeAction() {
        $view = parent::homeAction();
        $view->overrideRecordLink = 'Fis';
        return $view;
    }   

    
    
    
}
