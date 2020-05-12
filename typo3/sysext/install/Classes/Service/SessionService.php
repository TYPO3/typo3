<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Install\Service;

use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\CookieHeaderTrait;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Security\BlockSerializationTrait;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Exception;
use TYPO3\CMS\Install\Service\Session\FileSessionHandler;

/**
 * Secure session handling for the install tool.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class SessionService implements SingletonInterface
{
    use BlockSerializationTrait;
    use CookieHeaderTrait;

    /**
     * the cookie to store the session ID of the install tool
     *
     * @var string
     */
    private $cookieName = 'Typo3InstallTool';

    /**
     * time (minutes) to expire an unused session
     *
     * @var int
     */
    private $expireTimeInMinutes = 60;

    /**
     * time (minutes) to generate a new session id for our current session
     *
     * @var int
     */
    private $regenerateSessionIdTime = 5;

    /**
     * Constructor. Starts PHP session handling in our own private store
     *
     * Side-effect: might set a cookie, so must be called before any other output.
     */
    public function __construct()
    {
        // Register our "save" session handler
        $sessionHandler = GeneralUtility::makeInstance(
            FileSessionHandler::class,
            Environment::getVarPath() . '/session',
            $this->expireTimeInMinutes
        );
        session_set_save_handler($sessionHandler);
        session_name($this->cookieName);
        ini_set('session.cookie_httponly', true);
        if ($this->hasSameSiteCookieSupport()) {
            ini_set('session.cookie_samesite', Cookie::SAMESITE_STRICT);
        }
        ini_set('session.cookie_path', (string)GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
        // Always call the garbage collector to clean up stale session files
        ini_set('session.gc_probability', (string)100);
        ini_set('session.gc_divisor', (string)100);
        ini_set('session.gc_maxlifetime', (string)$this->expireTimeInMinutes * 2 * 60);
        if ($this->isSessionAutoStartEnabled()) {
            $sessionCreationError = 'Error: session.auto-start is enabled.<br />';
            $sessionCreationError .= 'The PHP option session.auto-start is enabled. Disable this option in php.ini or .htaccess:<br />';
            $sessionCreationError .= '<pre>php_value session.auto_start Off</pre>';
            throw new Exception($sessionCreationError, 1294587485);
        }
        if (defined('SID')) {
            $sessionCreationError = 'Session already started by session_start().<br />';
            $sessionCreationError .= 'Make sure no installed extension is starting a session in its ext_localconf.php or ext_tables.php.';
            throw new Exception($sessionCreationError, 1294587486);
        }
        session_start();
        if (!$this->hasSameSiteCookieSupport()) {
            $this->resendCookieHeader();
        }
    }

    /**
     * Starts a new session
     *
     * @return string The session ID
     */
    public function startSession()
    {
        $_SESSION['active'] = true;
        // Be sure to use our own session id, so create a new one
        return $this->renewSession();
    }

    /**
     * Destroys a session
     */
    public function destroySession()
    {
        session_destroy();
    }

    /**
     * Reset session. Sets _SESSION to empty array.
     */
    public function resetSession()
    {
        $_SESSION = [];
        $_SESSION['active'] = false;
    }

    /**
     * Generates a new session ID and sends it to the client.
     *
     * @return string the new session ID
     */
    private function renewSession()
    {
        session_regenerate_id();
        if (!$this->hasSameSiteCookieSupport()) {
            $this->resendCookieHeader([$this->cookieName]);
        }
        return session_id();
    }

    /**
     * Checks whether we already have an active session.
     *
     * @return bool TRUE if there is an active session, FALSE otherwise
     */
    public function hasSession()
    {
        return $_SESSION['active'] === true;
    }

    /**
     * Marks this session as an "authorized" one (login successful).
     * Should only be called if:
     * a) we have a valid session running
     * b) the "password" or some other authorization mechanism really matched
     */
    public function setAuthorized()
    {
        $_SESSION['authorized'] = true;
        $_SESSION['lastSessionId'] = time();
        $_SESSION['tstamp'] = time();
        $_SESSION['expires'] = time() + $this->expireTimeInMinutes * 60;
        // Renew the session id to avoid session fixation
        $this->renewSession();
    }

    /**
     * Marks this session as an "authorized by backend user" one.
     * This is called by BackendModuleController from backend context.
     */
    public function setAuthorizedBackendSession()
    {
        $_SESSION['authorized'] = true;
        $_SESSION['lastSessionId'] = time();
        $_SESSION['tstamp'] = time();
        $_SESSION['expires'] = time() + $this->expireTimeInMinutes * 60;
        $_SESSION['isBackendSession'] = true;
        // Renew the session id to avoid session fixation
        $this->renewSession();
    }

    /**
     * Check if we have an already authorized session
     *
     * @return bool TRUE if this session has been authorized before (by a correct password)
     */
    public function isAuthorized()
    {
        if (!$_SESSION['authorized']) {
            return false;
        }
        if ($_SESSION['expires'] < time()) {
            // This session has already expired
            return false;
        }
        return true;
    }

    /**
     * Check if we have an authorized session from a system maintainer
     *
     * @return bool TRUE if this session has been authorized before and initialized by a backend system maintainer
     */
    public function isAuthorizedBackendUserSession()
    {
        if (!$_SESSION['authorized'] || !$_SESSION['isBackendSession']) {
            return false;
        }
        if ($_SESSION['expires'] < time()) {
            // This session has already expired
            return false;
        }
        return true;
    }

    /**
     * Check if our session is expired.
     * Useful only right after a FALSE "isAuthorized" to see if this is the
     * reason for not being authorized anymore.
     *
     * @return bool TRUE if an authorized session exists, but is expired
     */
    public function isExpired()
    {
        if (!$_SESSION['authorized']) {
            // Session never existed, means it is not "expired"
            return false;
        }
        if ($_SESSION['expires'] < time()) {
            // This session was authorized before, but has expired
            return true;
        }
        return false;
    }

    /**
     * Refreshes our session information, rising the expire time.
     * Also generates a new session ID every 5 minutes to minimize the risk of
     * session hijacking.
     */
    public function refreshSession()
    {
        $_SESSION['tstamp'] = time();
        $_SESSION['expires'] = time() + $this->expireTimeInMinutes * 60;
        if (time() > $_SESSION['lastSessionId'] + $this->regenerateSessionIdTime * 60) {
            // Renew our session ID
            $_SESSION['lastSessionId'] = time();
            $this->renewSession();
        }
    }

    /**
     * Add a message to "Flash" message storage.
     *
     * @param FlashMessage $message A message to add
     */
    public function addMessage(FlashMessage $message)
    {
        if (!is_array($_SESSION['messages'])) {
            $_SESSION['messages'] = [];
        }
        $_SESSION['messages'][] = $message;
    }

    /**
     * Return stored session messages and flush.
     *
     * @return FlashMessage[] Messages
     */
    public function getMessagesAndFlush()
    {
        $messages = [];
        if (is_array($_SESSION['messages'])) {
            $messages = $_SESSION['messages'];
        }
        $_SESSION['messages'] = [];
        return $messages;
    }

    /**
     * Check if php session.auto_start is enabled
     *
     * @return bool TRUE if session.auto_start is enabled, FALSE if disabled
     */
    protected function isSessionAutoStartEnabled()
    {
        return $this->getIniValueBoolean('session.auto_start');
    }

    /**
     * Cast an on/off php ini value to boolean
     *
     * @param string $configOption
     * @return bool TRUE if the given option is enabled, FALSE if disabled
     */
    protected function getIniValueBoolean($configOption)
    {
        return filter_var(ini_get($configOption), FILTER_VALIDATE_BOOLEAN, [FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE]);
    }
}
