<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace BszTheme\View\Helper\Bodensee;

/**
 * Description of Piwik
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Piwik extends \VuFind\View\Helper\Root\Piwik
{
    protected $globalSiteId;

    /**
     * Constructor
     *
     * @param string|bool $url        Piwik address (false if disabled)
     * @param int         $siteId     Piwik site ID
     * @param bool        $customVars Whether to track additional information in
     * custom variables
     */
    public function __construct($url, $siteId, $customVars, $globalSiteId = 0)
    {
        $this->url = $url;
        if ($url && substr($url, -1) != '/') {
            $this->url .= '/';
        }
        $this->siteId = (int)$siteId;
        $this->globalSiteId = (int)$globalSiteId;
        $this->customVars = $customVars;
    }

    /**
     * Returns Piwik code (if active) or empty string if not.
     *
     * @return string
     */
    public function __invoke($action = '')
    {
        if ($action == 'siteid') {
            return $this->getSiteId();
        } elseif ($action == 'globalsiteid') {
            return $this->getglobalSiteId();
        } elseif ($action == 'baseurl') {
            return $this->getBaseUrl();
        } else {
            if (!$this->url) {
                return '';
            }
            $params = [
                'piwikUrl' => $this->url,
                'globalSiteId'   => $this->globalSiteId,
                'siteId'   => $this->siteId

            ];

            $view = $this->getView()->partial('Helpers/piwik.phtml', $params);

            return $view;
        }
    }

    /**
     * Return site id
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * Return site id
     * @return int
     */
    public function getGlobalSiteId()
    {
        return $this->globalSiteId;
    }

    /**
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->url;
    }
}
