<?php

/**
 * OpenURL view helper
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
 * @package  View_Helpers
 * @author   Demian Katz <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace BszTheme\View\Helper\Bodensee;
use \VuFind\View\Helper\Root\Context;

/**
 * OpenURL view helper
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class OpenUrl extends \VuFind\View\Helper\Root\OpenUrl
{
    protected $params;
    protected $isil;
    
    /**
     * Constructor
     *
     * @param \VuFind\View\Helper\Root\Context $context      Context helper
     * @param array                            $openUrlRules VuFind OpenURL rules
     * @param \Zend\Config\Config              $config       VuFind OpenURL config
     * @param string                           $isil         ISIL, if possible
     */
    public function __construct(Context $context,
        $openUrlRules, $config = null, $isil = null
    ) {
        $this->context = $context;
        $this->openUrlRules = $openUrlRules;
        $this->config = $config;
        $this->isil = $isil;
    }
    
    /**
     * Render appropriate UI controls for an OpenURL link.
     *
     * @param \VuFind\RecordDriver $driver The current recorddriver
     * @param string               $area   OpenURL context ('results', 'record'
     *  or 'holdings'
     *
     * @return object
     */
    public function __invoke($driver, $area)
    {
        $this->recordDriver = $driver;
        $this->area = $area;
        $this->params = $this->recordDriver->getOpenUrl();
        return $this;
    }

    /**
     * Public method to render the OpenURL template
     *
     * @param bool $imagebased Indicates if an image based link
     * should be displayed or not (null for system default)
     *
     * @return string
     */
    public function renderTemplate($imagebased = null)
    {
        if (null !== $this->config && isset($this->config->url)) {
            // Trim off any parameters (for legacy compatibility -- default config
            // used to include extraneous parameters):
            list($base) = explode('?', $this->config->url);
        } else {
            $base = false;
        }

        $embed = (isset($this->config->embed) && !empty($this->config->embed));

        $embedAutoLoad = isset($this->config->embed_auto_load)
            ? $this->config->embed_auto_load : false;
        // ini values 'true'/'false' are provided via ini reader as 1/0
        // only check embedAutoLoad for area if the current area passed checkContext
        if (!($embedAutoLoad === "1" || $embedAutoLoad === "0")
            && !empty($this->area)
        ) {
            // embedAutoLoad is neither true nor false, so check if it contains an
            // area string defining where exactly to use autoloading
            $embedAutoLoad = in_array(
                strtolower($this->area),
                array_map(
                    'trim',
                    array_map(
                        'strtolower',
                        explode(',', $embedAutoLoad)
                    )
                )
            );
        }
        
        $paramString = $this->parse($this->params);
        
        // Build parameters needed to display the control:
        $viewParams = [
            'openUrl' => $paramString,
            'openUrlBase' => $this->isRedi() ? $base.$this->config->rediid : $base,
            'openUrlWindow' => empty($this->config->window_settings) ? false : $this->config->window_settings,
            'openUrlGraphic' => empty($this->config->graphic) ? false : 
                $this->config->graphic . '?' . $paramString,
            'openUrlGraphicWidth' => empty($this->config->graphic_width) ? false : $this->config->graphic_width,
            'openUrlGraphicHeight' => empty($this->config->graphic_height) ? false : $this->config->graphic_height,
            'openUrlEmbed' => $embed,
            'openUrlEmbedAutoLoad' => $embedAutoLoad
        ];
        $this->addImageBasedParams($imagebased, $viewParams);
        
        // Render the subtemplate:
        return $this->render($viewParams);
    }

    /**
     * ADDs some BSZ specific params to tthe given OpenUrl
     * @param array $params
     * @return string
     */
    protected function parse($params)
    {
        // here, we check if all mandytory fields are set. If not, we return []. 
        if ($this->check($params)) {            

            $orgParams = [
                'sid' => $this->config->rfr_id,
                'pid' => ''
            ];
            if ($this->area != 'illform') {
                
                if (!empty($this->config->bibid)) {
                    $orgParams['pid'] = 'bibid=' . $this->config->bibid;
                } elseif (!empty($this->isil)) {
                    $orgParams['pid'] = 'isil=' . $this->isil;                
                } 
                if (array_key_exists('pid', $params)) {
                    $orgParams['pid'] .= '&'.$params['pid'];
                    unset($params['pid']);
                }                
            }
            
            $params = array_merge($params, $orgParams);
        } else {
            $params = [];
        }
        if ($this->config->version == '0.1') {
            $this->mapOpenUrl($params);            
        }
        return http_build_query($params);
    }
    
    /**
     * Simply returns a valid openURL, without rendering this nasty template
     * 
     * @param string $baseUrl
     * @param array $additionalParams
     * @param bool $map                 map params to old 0.1 standard. 
     * 
     * 
     * @return string 
     */
    public function getUrl($baseUrl, $additionalParams = [], $map = false) {
        $params = $this->recordDriver->getOpenUrl();
        if ($map) {
            $params = $this->mapOpenUrl($params, false);            
        }
        $query = http_build_query(array_merge($params, $additionalParams));
        return $baseUrl.'?'.$query;          
    }
    
    /**
     * Is this a redi client?
     * @return boolean
     */
    public function isRedi() {
        if (strpos($this->config->url, 'redi') === false ) {
            return false;
        } elseif (strpos($this->config->url, 'redi') !== false) {
            return true;
        }        
    }

    /**
     * is this a JOP cliebnt ?
     * @return boolean
     */
    public function isJop() {
        if (strpos($this->config->url, 'redi') === false ) {
            return true;
        } elseif (strpos($this->config->url, 'redi') !== false) {
            return false;
        }        
    }  
    
        /**
     * Maps an OpenUrl version >=1.0 to old 0.1
     * 
     * @param array $params
     * @param bool $filterGenre filter Genre according to JOP standard
     * 
     * @return array
     */
    public function mapOpenUrl(& $params, $filterGenre = true)
    {
        $newParams = [];
        $mapping = [
            'rft_val_fmt' => false,
            'rft.genre' => 'genre',
            'rft.issn' => 'issn',
            'rft.isbn' => 'isbn',
            'rft.volume' => 'volume',
            'rft.issue' => 'issue',
            'rft.spage' => 'spage',
            'rft.epage' => 'epage',
            'rft.pages' => 'pages',
            'rft.place' => 'place',
            'rft.title' => 'title',
            'rft.atitle' => 'atitle',
            'rft.btitle' => 'title',            
            'rft.jtitle' => 'title',
            'rft.au' => 'aulast',
            'rft.date' => 'date',
            'rft.format' => false,
            'pid' => 'pid',
            'sid' => 'sid',
        ];
        foreach ($params as $key => $value) {
            if (isset($mapping[$key]) && $mapping[$key] !== false) {
                $newParams[$mapping[$key]] = $value;
            }
        }
        if (isset($params['rft.series'])) {
            $newParams['title'] = $params['rft.series'].': '
                    .$newParams['title'];
        }
        // for the open url ill form, we need genre = bookitem
        if ($this->area == 'illform' && $newParams['genre'] == 'article'
                && $this->recordDriver->isContainerMonography()) {
            $newParams['genre'] = 'bookitem';           
        }
        
        // JOP has a really limited amount of allowed genres
        $allowedJopGenres = ['article', 'journal'];
        if ($filterGenre && ($this->isJop() || $this->isRedi()) && 
                array_key_exists('genre', $newParams) &&                        
                !in_array($newParams['genre'], $allowedJopGenres)                
                        
            ) {
            switch ($newParams['genre']) {
                case 'issue': $newParams['genre'] = 'journal';
                    break;
                case 'proceeding': $newParams['genre'] = 'journal';
                    break;
                case 'conference': $newParams['genre'] = 'journal';
                    break;
                // no support for books
                case 'book': return [];
                    break;
                // articles are more probably
                default: $newParams['genre'] = 'article';
 
            }
                    
        }
        $params = array_filter($newParams);  
        return $params;
    }
    
    /**
     * Distinguish between Redi and Jop
     * @param array $params
     * @return string
     */
    protected function render($params) 
    {
        if ($this->isJop()) {
            return $this->context->__invoke($this->getView())->renderInContext(
                            'Helpers/openurl/jop.phtml', $params
            );            
        } else if($this->isRedi()) {
            return $this->context->__invoke($this->getView())->renderInContext(
                            'Helpers/openurl/redi.phtml', $params
            ); 
        } else {
            return '';
        }
    }
    
    /**
     * Check whether all mandytory params are available
     * @param array $params
     * @return boolean
     */
    public function check($params) 
    {
        $valid = true;
        // genre is mandatory
        if (!isset($params['rft.genre'])) {
            $valid = false;
        }
        // there should be at least one title
        if (!isset($params['rft.title']) && !isset($params['rft.atitle'])
                && !isset($params['rft.jtitle']) && !isset($params['rft.btitle'])) {
            $valid = false;
        }
// at least REDI can handle missing isbn / issn
//        if (!isset($params['rft.issn']) && !isset($params['rft.isbn'])) {
//            $valid = false;
//        }        
        return $valid;
            
    }
}
