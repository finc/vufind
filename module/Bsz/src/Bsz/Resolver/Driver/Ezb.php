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
}
