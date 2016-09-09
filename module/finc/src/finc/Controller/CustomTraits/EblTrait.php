<?php
/**
 * Ebl Trait
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) Leipzig University Library 2016.
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
 * @category Vufind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace finc\Controller\CustomTraits;

/**
 * Ebl Trait
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

trait EblTrait
{

    /**
     * Link action to manage rewrite with authorization and permission treatment.
     * Redirect directly to external EBL service after successful login.
     *
     * @return mixed
     * @access public
     */
    public function eblLinkAction()
    {
        $link = $tag = $this->params()->fromQuery('link');
        //$id = $tag = $this->params()->fromQuery('id');

        // Force login:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }
        $rewrite = $this->getRewrite();
        $link = $rewrite->resolveLink($link, $user);
        return $this->redirect()->toUrl($link);
    }
}