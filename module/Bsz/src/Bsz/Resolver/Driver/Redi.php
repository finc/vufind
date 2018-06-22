<?php

namespace Bsz\Resolver\Driver;

/**
 * Redi Link Resolver
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Redi extends \VuFind\Resolver\Driver\Redi
{
       
    /**
     * Allows for resolver driver specific enabling/disabling of the more options
     * link which will link directly to the resolver URL. This should return false if
     * the resolver returns data in XML or any other human unfriendly response.
     *
     * @return bool
     */
    public function supportsMoreOptionsLink()
    {
        return true;
    }
}
