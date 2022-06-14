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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Security\BlockSerializationTrait;
use TYPO3\CMS\Core\Session\Backend\HashableSessionBackendInterface;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Exception;
use TYPO3\CMS\Install\Service\Session\FileSessionHandler;

/**
 * Secure session handling for the install tool.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class SessionService implements SingletonInterface
{
    use BlockSerializationTrait;

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
    private $expireTimeInMinutes = 15;

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
        ini_set('session.cookie_secure', GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'On' : 'Off');
        ini_set('session.cookie_httponly', 'On');
        ini_set('session.cookie_samesite', Cookie::SAMESITE_STRICT);
        ini_set('session.cookie_path', (string)GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
        // Always call the garbage collector to clean up stale session files
        ini_set('session.gc_probability', (string)100);
        ini_set('session.gc_divisor', (string)100);
        ini_set('session.gc_maxlifetime', (string)($this->expireTimeInMinutes * 2 * 60));
        if ($this->isSessionAutoStartEnabled()) {
            $sessionCreationError = 'Error: session.auto-start is enabled.<br />';
            $sessionCreationError .= 'The PHP option session.auto-start is enabled. Disable this option in php.ini or .htaccess:<br />';
            $sessionCreationError .= '<pre>php_value session.auto_start Off</pre>';
            throw new Exception($sessionCreationError, 1294587485);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $sessionCreationError = 'Session already started by session_start().<br />';
            $sessionCreationError .= 'Make sure no installed extension is starting a session in its ext_localconf.php or ext_tables.php.';
            throw new Exception($sessionCreationError, 1294587486);
        }
    }

    public function initializeSession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_start();
    }

    /**
     * Starts a new session
     *
     * @return string|false The session ID
     */
    public function startSession()
    {
        $this->initializeSession();
        // check if session is already active
        if ($_SESSION['active'] ?? false) {
            return session_id();
        }
        $_SESSION['active'] = true;
        // Be sure to use our own session id, so create a new one
        return $this->renewSession();
    }

    /**
     * Destroys a session
     */
    public function destroySession()
    {
        if ($this->hasSessionCookie()) {
            $this->initializeSession();
            $_SESSION = [];
            $params = session_get_cookie_params();
            $cookie = Cookie::create(($sessionName = session_name()) !== false ? $sessionName : $this->cookieName)
                ->withValue('0')
                ->withPath($params['path'])
                ->withDomain($params['domain'])
                ->withSecure($params['samesite'] === Cookie::SAMESITE_NONE || GeneralUtility::getIndpEnv('TYPO3_SSL'))
                ->withHttpOnly($params['httponly'])
                ->withSameSite($params['samesite']);

            header('Set-Cookie: ' . $cookie);
            session_destroy();
        }
    }

    /**
     * Reset session. Sets _SESSION to empty array.
     */
    public function resetSession()
    {
        $this->initializeSession();
        $_SESSION = [];
        $_SESSION['active'] = false;
    }

    /**
     * Generates a new session ID and sends it to the client.
     *
     * @return string|false the new session ID
     */
    private function renewSession()
    {
        // we do not have parallel ajax requests so we can safely remove the old session data
        session_regenerate_id(true);
        return session_id();
    }

    /**
     * Checks whether whether is session cookie is set
     *
     * @return bool
     */
    public function hasSessionCookie(): bool
    {
        return isset($_COOKIE[$this->cookieName]);
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
     *
     * @param UserSession $userSession session of the current backend user
     */
    public function setAuthorizedBackendSession(UserSession $userSession)
    {
        $nonce = bin2hex(random_bytes(20));
        $sessionBackend = $this->getBackendUserSessionBackend();
        // use hash mechanism of session backend, or pass plain value through generic hmac
        $sessionHmac = $sessionBackend instanceof HashableSessionBackendInterface
            ? $sessionBackend->hash($userSession->getIdentifier())
            : hash_hmac('sha256', $userSession->getIdentifier(), $nonce);

        $_SESSION['authorized'] = true;
        $_SESSION['lastSessionId'] = time();
        $_SESSION['tstamp'] = time();
        $_SESSION['expires'] = time() + $this->expireTimeInMinutes * 60;
        $_SESSION['isBackendSession'] = true;
        $_SESSION['backendUserSession'] = [
            'nonce' => $nonce,
            'userId' => $userSession->getUserId(),
            'hmac' => $sessionHmac,
        ];
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
        if (!$this->hasSessionCookie()) {
            return false;
        }
        $this->initializeSession();
        if (empty($_SESSION['authorized'])) {
            return false;
        }
        return !$this->isExpired();
    }

    /**
     * Check if we have an authorized session from a system maintainer
     *
     * @return bool TRUE if this session has been authorized before and initialized by a backend system maintainer
     */
    public function isAuthorizedBackendUserSession(): bool
    {
        if (!$this->hasSessionCookie()) {
            return false;
        }
        $this->initializeSession();
        if (empty($_SESSION['authorized']) || empty($_SESSION['isBackendSession'])) {
            return false;
        }
        return !$this->isExpired();
    }

    /**
     * Evaluates whether the backend user that initiated this admin tool session,
     * has an active role (is still admin & system maintainer) and has an active backend user interface session.
     *
     * @return bool whether the backend user has an active role and backend user interface session
     */
    public function hasActiveBackendUserRoleAndSession(): bool
    {
        // @see \TYPO3\CMS\Install\Controller\BackendModuleController::setAuthorizedAndRedirect()
        $backendUserSession = $this->getBackendUserSession();
        $backendUserRecord = $this->getBackendUserRecord($backendUserSession['userId']);
        if ($backendUserRecord === null || empty($backendUserRecord['uid'])) {
            return false;
        }
        $isAdmin = (($backendUserRecord['admin'] ?? 0) & 1) === 1;
        $systemMaintainers = array_map('intval', $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers'] ?? []);
        // in case no system maintainers are configured, all admin users are considered to be system maintainers
        $isSystemMaintainer = empty($systemMaintainers) || in_array((int)$backendUserRecord['uid'], $systemMaintainers, true);
        // in development context, all admin users are considered to be system maintainers
        $hasDevelopmentContext = Environment::getContext()->isDevelopment();
        // stop here, in case the current admin tool session does not belong to a backend user having admin & maintainer privileges
        if (!$isAdmin || !$hasDevelopmentContext && !$isSystemMaintainer) {
            return false;
        }

        $sessionBackend = $this->getBackendUserSessionBackend();
        foreach ($sessionBackend->getAll() as $sessionRecord) {
            $sessionUserId = (int)($sessionRecord['ses_userid'] ?? 0);
            // skip, in case backend user id does not match
            if ($backendUserSession['userId'] !== $sessionUserId) {
                continue;
            }
            $sessionId = (string)($sessionRecord['ses_id'] ?? '');
            // use persisted hashed `ses_id` directly, or pass through hmac for plain values
            $sessionHmac = $sessionBackend instanceof HashableSessionBackendInterface
                ? $sessionId
                : hash_hmac('sha256', $sessionId, $backendUserSession['nonce']);
            // skip, in case backend user session id does not match
            if ($backendUserSession['hmac'] !== $sessionHmac) {
                continue;
            }
            // backend user id and session id matched correctly
            return true;
        }
        return false;
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
        if (!$this->hasSessionCookie()) {
            // Session never existed, means it is not "expired"
            return false;
        }
        $this->initializeSession();
        if (empty($_SESSION['authorized'])) {
            // Session never authorized, means it is not "expired"
            return false;
        }
        return $_SESSION['expires'] <= time();
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
     * @return array{userId: int, nonce: string, hmac: string} backend user session references
     */
    public function getBackendUserSession(): array
    {
        if (empty($_SESSION['backendUserSession'])) {
            throw new Exception(
                'The backend user session is only available if invoked via the backend user interface.',
                1624879295
            );
        }
        return $_SESSION['backendUserSession'];
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
        return filter_var(
            ini_get($configOption),
            FILTER_VALIDATE_BOOLEAN,
            [FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE]
        );
    }

    /**
     * Fetching a user record with uid=$uid.
     * Functionally similar to TYPO3\CMS\Core\Authentication\BackendUserAuthentication::setBeUserByUid().
     *
     * @param int $uid The UID of the backend user
     * @return array<string, int>|null The backend user record or NULL
     */
    protected function getBackendUserRecord(int $uid): ?array
    {
        $restrictionContainer = GeneralUtility::makeInstance(DefaultRestrictionContainer::class);
        $restrictionContainer->add(GeneralUtility::makeInstance(RootLevelRestriction::class, ['be_users']));

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->setRestrictions($restrictionContainer);
        $queryBuilder->select('uid', 'admin')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)));

        $resetBeUsersTca = false;
        if (!isset($GLOBALS['TCA']['be_users'])) {
            // The admin tool intentionally does not load any TCA information at this time.
            // The database restictions, needs the enablecolumns TCA information
            // for 'be_users' to load the user correctly.
            // That is why this part of the TCA ($GLOBALS['TCA']['be_users']['ctrl']['enablecolumns'])
            // is simulated.
            // The simulation state will be removed later to avoid unexpected side effects.
            $GLOBALS['TCA']['be_users']['ctrl']['enablecolumns'] = [
                'rootLevel' => 1,
                'deleted' => 'deleted',
                'disabled' => 'disable',
                'starttime' => 'starttime',
                'endtime' => 'endtime',
            ];
            $resetBeUsersTca = true;
        }
        $result = $queryBuilder->executeQuery()->fetchAssociative();
        if ($resetBeUsersTca) {
            unset($GLOBALS['TCA']['be_users']);
        }

        return is_array($result) ? $result : null;
    }

    protected function getBackendUserSessionBackend(): SessionBackendInterface
    {
        return GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend('BE');
    }
}
