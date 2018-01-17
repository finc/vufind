<?php
/**
 * Record Controller
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
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Bsz\Controller;
use VuFind\RecordDriver\AbstractBase as AbstractRecordDriver;

/**
 * This class was created to make a default record tab behavior possible
 */
class RecordController extends \VuFind\Controller\RecordController
{
    use \VuFind\Controller\HoldsTrait;
    use \VuFind\Controller\ILLRequestsTrait;
    use \VuFind\Controller\StorageRetrievalRequestsTrait;
    
    
     /**
     * Default tab for Solr is holdings, excepts its a collection, then volumes. 
     *
     * @param AbstractRecordDriver $driver Record driver
     *
     * @return string
     */
    protected function getDefaultTabForRecord(AbstractRecordDriver $driver)
    {
        // Load configuration:
        $config = $this->getTabConfiguration();

        // Get the current record driver's class name, then start a loop
        // in case we need to use a parent class' name to find the appropriate
        // setting.
        $className = get_class($driver);
        while (true) {
            $multipart = $driver->tryMethod('getMultipartLevel');
            if(isset($multipart)) {
                if($multipart == \Bsz\RecordDriver\SolrMarc::MULTIPART_COLLECTION) {
                    return 'Volumes';
                }
                else {
                    return 'Holdings';
                }
            }
            elseif (isset($config[$className]['defaultTab'])) {
                return $config[$className]['defaultTab'];
            }
            $className = get_parent_class($className);
            if (empty($className)) {
                // No setting found...
                return null;
            }
        }
    }

}
