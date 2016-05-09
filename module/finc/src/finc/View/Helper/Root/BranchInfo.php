<?php
/**
 * Branch info view helper
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
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace finc\View\Helper\Root;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Branch info view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class BranchInfo  extends AbstractHelper
{
    /**
     * YAML branches filename.
     *
     * @var string
     */
    protected $branchesYaml;

    /**
     * Superior service manager.
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Load the branches
     *
     * @return array
     */
    protected function loadBranches()
    {
        return $this->serviceLocator->get('VuFind\BranchesReader')
          ->get('branches.yaml');
    }

    /**
     * @param $branchID
     * @return string
     */
    public function getBranchInfo($branchID)
    {
        $yamlData = $this->loadBranches();
        if (isset($yamlData)) {
            $data = $yamlData[$branchID];
            return $this->getView()->render('Helpers/branchinfo.phtml', ['info' => $data]);
        }
        return null;
    }
}