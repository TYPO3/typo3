<?php
namespace TYPO3\CMS\Install\Service;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Secure session handling for the install tool.
 */
class SessionService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * The path to our typo3temp/var/ (where we can write our sessions). Set in the
     * constructor.
     *
     * @var string
     */
    private $basePath;

    /**
     * Path where to store our session files in typo3temp. %s will be
     * non-guessable.
     *
     * @var string
     */
    private $sessionPath = 'InstallToolSessions/%s';

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
        $this->basePath = PATH_site . 'typo3temp/var/';
        // Start our PHP session early so that hasSession() works
        $sessionSavePath = $this->getSessionSavePath();
        // Register our "save" session handler
        session_set_save_handler([$this, 'open'], [$this, 'close'], [$this, 'read'], [$this, 'write'], [$this, 'destroy'], [$this, 'gc']);
        session_save_path($sessionSavePath);
        session_name($this->cookieName);
        ini_set('session.cookie_path', GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
        // Always call the garbage collector to clean up stale session files
        ini_set('session.gc_probability', 100);
        ini_set('session.gc_divisor', 100);
        ini_set('session.gc_maxlifetime', $this->expireTimeInMinutes * 2 * 60);
        if (\TYPO3\CMS\Core\Utility\PhpOptionsUtility::isSessionAutoStartEnabled()) {
            $sessionCreationError = 'Error: session.auto-start is enabled.<br />';
            $sessionCreationError .= 'The PHP option session.auto-start is enabled. Disable this option in php.ini or .htaccess:<br />';
            $sessionCreationError .= '<pre>php_value session.auto_start Off</pre>';
            throw new \TYPO3\CMS\Install\Exception($sessionCreationError, 1294587485);
        }
        if (defined('SID')) {
            $sessionCreationError = 'Session already started by session_start().<br />';
            $sessionCreationError .= 'Make sure no installed extension is starting a session in its ext_localconf.php or ext_tables.php.';
            throw new \TYPO3\CMS\Install\Exception($sessionCreationError, 1294587486);
        }
        session_start();
    }

    /**
     * Returns the path where to store our session files
     *
     * @throws \TYPO3\CMS\Install\Exception
     * @return string Session save path
     */
    private function getSessionSavePath()
    {
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            throw new \TYPO3\CMS\Install\Exception(
                'No encryption key set to secure session',
                1371243449
            );
        }
        $sessionSavePath = sprintf(
            $this->basePath . $this->sessionPath,
            GeneralUtility::hmac('session:' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])
        );
        $this->ensureSessionSavePathExists($sessionSavePath);
        return $sessionSavePath;
    }

    /**
     * Create directories for the session save path
     * and throw an exception if that fails.
     *
     * @param string $sessionSavePath The absolute path to the session files
     * @throws \TYPO3\CMS\Install\Exception
     */
    private function ensureSessionSavePathExists($sessionSavePath)
    {
        if (!is_dir($sessionSavePath)) {
            try {
                GeneralUtility::mkdir_deep($sessionSavePath);
            } catch (\RuntimeException $exception) {
                throw new \TYPO3\CMS\Install\Exception(
                    'Could not create session folder in typo3temp/. Make sure it is writeable!',
                    1294587484
                );
            }
            $htaccessContent = '
# Apache < 2.3
<IfModule !mod_authz_core.c>
	Order allow,deny
	Deny from all
	Satisfy All
</IfModule>

# Apache ≥ 2.3
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
			';
            GeneralUtility::writeFile($sessionSavePath . '/.htaccess', $htaccessContent);
            $indexContent = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">';
            $indexContent .= '<HTML><HEAD<TITLE></TITLE><META http-equiv=Refresh Content="0; Url=../../">';
            $indexContent .= '</HEAD></HTML>';
            GeneralUtility::writeFile($sessionSavePath . '/index.html', $indexContent);
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
     * Returns the session ID of the running session.
     *
     * @return string the session ID
     */
    public function getSessionId()
    {
        return session_id();
    }

    /**
     * Returns a session hash, which can only be calculated by the server.
     * Used to store our session files without exposing the session ID.
     *
     * @param string $sessionId An alternative session ID. Defaults to our current session ID
     * @throws \TYPO3\CMS\Install\Exception
     * @return string the session hash
     */
    private function getSessionHash($sessionId = '')
    {
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            throw new \TYPO3\CMS\Install\Exception(
                'No encryption key set to secure session',
                1371243450
            );
        }
        if (!$sessionId) {
            $sessionId = $this->getSessionId();
        }
        return md5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . '|' . $sessionId);
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
     * @param \TYPO3\CMS\Install\Status\StatusInterface $message A message to add
     */
    public function addMessage(\TYPO3\CMS\Install\Status\StatusInterface $message)
    {
        if (!is_array($_SESSION['messages'])) {
            $_SESSION['messages'] = [];
        }
        $_SESSION['messages'][] = $message;
    }

    /**
     * Return stored session messages and flush.
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface> Messages
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

    /*************************
     *
     * PHP session handling with "secure" session files (hashed session id)
     * see http://www.php.net/manual/en/function.session-set-save-handler.php
     *
     *************************/
    /**
     * Returns the file where to store our session data
     *
     * @param string $id
     * @return string A filename
     */
    private function getSessionFile($id)
    {
        $sessionSavePath = $this->getSessionSavePath();
        return $sessionSavePath . '/hash_' . $this->getSessionHash($id);
    }

    /**
     * Open function. See @session_set_save_handler
     *
     * @param string $savePath
     * @param string $sessionName
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * Close function. See @session_set_save_handler
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data. See @session_set_save_handler
     *
     * @param string $id The session id
     * @return string
     */
    public function read($id)
    {
        $sessionFile = $this->getSessionFile($id);
        $content = '';
        if (file_exists($sessionFile)) {
            if ($fd = fopen($sessionFile, 'rb')) {
                $lockres = flock($fd, LOCK_SH);
                if ($lockres) {
                    $length = filesize($sessionFile);
                    if ($length > 0) {
                        $content = fread($fd, $length);
                    }
                    flock($fd, LOCK_UN);
                }
                fclose($fd);
            }
        }
        // Do a "test write" of the session file after opening it. The real session data is written in
        // __destruct() and we can not create a sane error message there anymore, so this test should fail
        // before if final session file can not be written due to permission problems.
        $this->write($id, $content);
        return $content;
    }

    /**
     * Write session data. See @session_set_save_handler
     *
     * @param string $id The session id
     * @param string $sessionData The data to be stored
     * @throws Exception
     * @return bool
     */
    public function write($id, $sessionData)
    {
        $sessionFile = $this->getSessionFile($id);
        $result = false;
        $changePermissions = !@is_file($sessionFile);
        if ($fd = fopen($sessionFile, 'cb')) {
            if (flock($fd, LOCK_EX)) {
                ftruncate($fd, 0);
                $res = fwrite($fd, $sessionData);
                if ($res !== false) {
                    fflush($fd);
                    $result = true;
                }
                flock($fd, LOCK_UN);
            }
            fclose($fd);
            // Change the permissions only if the file has just been created
            if ($changePermissions) {
                GeneralUtility::fixPermissions($sessionFile);
            }
        }
        if (!$result) {
            throw new Exception(
                'Session file not writable. Please check permission on typo3temp/var/InstallToolSessions and its subdirectories.',
                1424355157
            );
        }
        return $result;
    }

    /**
     * Destroys one session. See @session_set_save_handler
     *
     * @param string $id The session id
     * @return string
     */
    public function destroy($id)
    {
        $sessionFile = $this->getSessionFile($id);
        return @unlink($sessionFile);
    }

    /**
     * Garbage collect session info. See @session_set_save_handler
     *
     * @param int $maxLifeTime The setting of session.gc_maxlifetime
     * @return bool
     */
    public function gc($maxLifeTime)
    {
        $sessionSavePath = $this->getSessionSavePath();
        $files = glob($sessionSavePath . '/hash_*');
        if (!is_array($files)) {
            return true;
        }
        foreach ($files as $filename) {
            if (filemtime($filename) + $this->expireTimeInMinutes * 60 < time()) {
                @unlink($filename);
            }
        }
        return true;
    }

    /**
     * Writes the session data at the end, to overcome a PHP APC bug.
     *
     * Writes the session data in a proper context that is not affected by the APC bug:
     * http://pecl.php.net/bugs/bug.php?id=16721.
     *
     * This behaviour was introduced in #17511, where self::write() made use of GeneralUtility
     * which due to the APC bug throws a "Fatal error: Class 'GeneralUtility' not found"
     * (and the session data is not saved). Calling session_write_close() at this point
     * seems to be the most easy solution, according to PHP author.
     */
    public function __destruct()
    {
        session_write_close();
    }
}
