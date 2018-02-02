<?php

namespace BszTheme\View\Helper\Bodensee;

use VuFind\Search\Base\Results;
use VuFind\Search\Results\PluginManager;
use VuFind\Search\SearchTabsHelper;
use Zend\Http\Request;
use Zend\View\Helper\Url;

/**
 * BSZ extension of searchTabs View Helper
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SearchTabs extends \VuFind\View\Helper\Root\SearchTabs
{
    public function isILL($searchClassId) {
        $hiddenFilterStr = urldecode($this->getCurrentHiddenFilterParams($searchClassId));
        if (strpos($hiddenFilterStr, 'consortium:FL') !== FALSE) {
            return true;
        }
        return false;
    }
}
