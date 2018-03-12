<?php

/**
 * Resolver for EZB links
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
namespace Bsz\Resolver\Driver;

class Ezb extends \VuFind\Resolver\Driver\Ezb
{
        /**
     * Get Resolver Url
     *
     * Transform the OpenURL as needed to get a working link to the resolver.
     *
     * @param string $openURL openURL (url-encoded)
     *
     * @return string Link
     */
    public function getResolverUrl($openURL)
    {
        // Unfortunately the EZB-API only allows OpenURL V0.1 and
        // breaks when sending a non expected parameter (like an ISBN).
        // So we do have to 'downgrade' the OpenURL-String from V1.0 to V0.1
        // and exclude all parameters that are not compliant with the EZB.

        // Parse OpenURL into associative array:
        $tmp = explode('&', $openURL);
        $parsed = [];

        foreach ($tmp as $current) {
            $tmp2 = explode('=', $current, 2);
            $parsed[$tmp2[0]] = $tmp2[1];
        }

        // Downgrade 1.0 to 0.1
        if ($parsed['ctx_ver'] == 'Z39.88-2004') {
            $openURL = $this->downgradeOpenUrl($parsed);
        }
        

        // Make the call to the EZB and load results
        $url = $this->baseUrl . '?' . $openURL;

        return $url;
    }
    
    public function getResolverImageParams($params)
    {
        $tmp = explode('&', $params);
        $parsed = [];

        foreach ($tmp as $current) {
            $tmp2 = explode('=', $current, 2);
            $parsed[$tmp2[0]] = $tmp2[1];
        }

        // Downgrade 1.0 to 0.1
        if ($parsed['ctx_ver'] == 'Z39.88-2004') {
            $openURL = $this->downgradeOpenUrl($parsed);
        }
        

        // Make the call to the EZB and load results
        $paramstring = $openURL;

        return $paramstring;;
    }
    
     /**
     * Downgrade an OpenURL from v1.0 to v0.1 for compatibility with EZB.
     *
     * @param array $parsed Array of parameters parsed from the OpenURL.
     *
     * @return string       EZB-compatible v0.1 OpenURL
     */
    protected function downgradeOpenUrl($parsed)
    {
        $downgraded = [];

        // we need 'genre' but only the values
        // article or journal are allowed...
        $downgraded[] = "genre=article";

        // ignore all other parameters
        foreach ($parsed as $key => $value) {
            // exclude empty parameters
            if (isset($value) && $value !== '') {
                if ($key == 'rfr_id') {
                    $newKey = 'sid';
                } elseif ($key == 'rft.date') {
                    $newKey = 'date';
                } elseif ($key == 'rft.issn') {
                    $newKey = 'issn';
                } elseif ($key == 'rft.volume') {
                    $newKey = 'volume';
                } elseif ($key == 'rft.issue') {
                    $newKey = 'issue';
                } elseif ($key == 'rft.spage') {
                    $newKey = 'spage';
                } elseif ($key == 'rft.pages') {
                    $newKey = 'pages';
                } else {
                    $newKey = false;
                }
                if ($newKey !== false) {
                    $downgraded[] = "$newKey=$value";
                }
            }
        }

        return implode('&', $downgraded);
    }
}
