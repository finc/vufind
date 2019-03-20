<?php

namespace Bsz\Auth;

/**
 * Adaptions for our Shibboleth installation
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Shibboleth extends \VuFind\Auth\Shibboleth
{
    /**
     * Attempt to authenticate the current user.  Throws exception if login fails.
     *
     * @param \Zend\Http\PhpEnvironment\Request $request Request object containing
     * account credentials.
     *
     * @throws AuthException
     * @return \VuFind\Db\Row\User Object representing logged-in user.
     */
    public function authenticate($request) 
    {
        parent::authenticate($request);
    }
}
