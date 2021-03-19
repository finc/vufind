<?php
namespace Bsz\Auth;

use Bsz\Config\Libraries;
use Bsz\Config\Library;
use VuFind\Exception\Auth as AuthException;

/**
 * Adaptions for our Shibboleth installation
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Shibboleth extends \VuFind\Auth\Shibboleth
{
    protected $libraries;
    protected $isil;

    /**
     * Constructor
     *
     * @param \Zend\Session\ManagerInterface $sessionManager Session manager
     */
    public function __construct(
        \Zend\Session\ManagerInterface $sessionManager,
        Libraries $libraries,
        $isil)
    {
        $this->sessionManager = $sessionManager;
        $this->libraries = $libraries;
        $this->isil = array_shift($isil);
    }

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
        $user = parent::authenticate($request);

        if (strpos($user->username, '@') !== false) {
            try {
                $domain = preg_replace('/.+@/', '', $user->username);
                $library = $this->libraries->getByIdPDomain($domain);
                if (isset($library)) {
                    $user->home_library = $library->getIsil();
                    $user->save();
                }
            } catch (\Exception $ex) {
                // in case this does not work - don't worry, user can still manually
                // select library
            }
        }
        return $user;
    }

    public function logout($url)
    {
        $library = $this->libraries->getFirstActive($this->isil);
        if ($library instanceof Library) {
            $url = $library->getLogoutUrl();
        }

        return parent::logout($url);
    }
}
