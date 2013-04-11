<?php
namespace TYPO3\CMS\Install;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ernesto Baschny <ernst@cron-it.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Secure session handling for the install tool.
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class Session {

	/**
	 * The path to our typo3temp (where we can write our sessions). Set in the
	 * constructor.
	 *
	 * @var string
	 */
	private $typo3tempPath;

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
	 * time (minutes) to expire an ununsed session
	 *
	 * @var integer
	 */
	private $expireTimeInMinutes = 60;

	/**
	 * time (minutes) to generate a new session id for our current session
	 *
	 * @var integer
	 */
	private $regenerateSessionIdTime = 5;

	/**
	 * part of the referer when the install tool has been called from the backend
	 *
	 * @var string
	 */
	private $backendFile = 'backend.php';

	/**
	 * Constructor. Starts PHP session handling in our own private store
	 *
	 * Side-effect: might set a cookie, so must be called before any other output.
	 */
	public function __construct() {
		$this->typo3tempPath = PATH_site . 'typo3temp/';
		// Start our PHP session early so that hasSession() works
		$sessionSavePath = $this->getSessionSavePath();
		// Register our "save" session handler
		session_set_save_handler(array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc'));
		session_save_path($sessionSavePath);
		session_name($this->cookieName);
		ini_set('session.cookie_path', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
		// Always call the garbage collector to clean up stale session files
		ini_set('session.gc_probability', 100);
		ini_set('session.gc_divisor', 100);
		ini_set('session.gc_maxlifetime', $this->expireTimeInMinutes * 2 * 60);
		if (\TYPO3\CMS\Core\Utility\PhpOptionsUtility::isSessionAutoStartEnabled()) {
			$sessionCreationError = 'Error: session.auto-start is enabled.<br />';
			$sessionCreationError .= 'The PHP option session.auto-start is enabled. Disable this option in php.ini or .htaccess:<br />';
			$sessionCreationError .= '<pre>php_value session.auto_start Off</pre>';
			throw new \RuntimeException($sessionCreationError, 1294587485);
		} elseif (defined('SID')) {
			$sessionCreationError = 'Session already started by session_start().<br />';
			$sessionCreationError .= 'Make sure no installed extension is starting a session in its ext_localconf.php or ext_tables.php.';
			throw new \RuntimeException($sessionCreationError, 1294587486);
		}
		session_start();
	}

	/**
	 * Returns the path where to store our session files
	 */
	private function getSessionSavePath() {
		$sessionSavePath = sprintf($this->typo3tempPath . $this->sessionPath, \TYPO3\CMS\Core\Utility\GeneralUtility::hmac('session:' . $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword']));
		$this->ensureSessionSavePathExists($sessionSavePath);
		return $sessionSavePath;
	}

	/**
	 * Create directories for the session save path
	 * and throw an exception if that fails.
	 *
	 * @param string $sessionSavePath The absolute path to the session files
	 * @throws \RuntimeException
	 */
	private function ensureSessionSavePathExists($sessionSavePath) {
		if (!is_dir($sessionSavePath)) {
			try {
				\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($sessionSavePath);
			} catch (\RuntimeException $exception) {
				throw new \RuntimeException('Could not create session folder in typo3temp/. Make sure it is writeable!', 1294587484);
			}
			\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($sessionSavePath . '/.htaccess', 'Order deny, allow' . '
' . 'Deny from all' . '
');
			$indexContent = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">';
			$indexContent .= '<HTML><HEAD<TITLE></TITLE><META http-equiv=Refresh Content="0; Url=../../">';
			$indexContent .= '</HEAD></HTML>';
			\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($sessionSavePath . '/index.html', $indexContent);
		}
	}

	/**
	 * Starts a new session
	 *
	 * @return string The session ID
	 */
	public function startSession() {
		$_SESSION['created'] = time();
		// Be sure to use our own session id, so create a new one
		return $this->renewSession();
	}

	/**
	 * Destroys a session
	 */
	public function destroySession() {
		session_destroy();
	}

	/**
	 * Generates a new session ID and sends it to the client.
	 *
	 * @return string the new session ID
	 */
	private function renewSession() {
		session_regenerate_id();
		return session_id();
	}

	/**
	 * Checks whether we already have an active session.
	 *
	 * @return boolean TRUE if there is an active session, FALSE otherwise
	 */
	public function hasSession() {
		return isset($_SESSION['created']);
	}

	/**
	 * Returns the session ID of the running session.
	 *
	 * @return string the session ID
	 */
	public function getSessionId() {
		return session_id();
	}

	/**
	 * Returns a session hash, which can only be calculated by the server.
	 * Used to store our session files without exposing the session ID.
	 *
	 * @param string $sessionId An alternative session ID. Defaults to our current session ID
	 * @return string the session hash
	 */
	private function getSessionHash($sessionId = '') {
		if (!$sessionId) {
			$sessionId = $this->getSessionId();
		}
		return md5($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] . '|' . $sessionId);
	}

	/**
	 * Marks this session as an "authorized" one (login successful).
	 * Should only be called if:
	 * a) we have a valid session running
	 * b) the "password" or some other authorization mechanism really matched
	 *
	 * @return void
	 */
	public function setAuthorized() {
		$_SESSION['authorized'] = TRUE;
		$_SESSION['lastSessionId'] = time();
		$_SESSION['tstamp'] = time();
		$_SESSION['expires'] = time() + $this->expireTimeInMinutes * 60;
		// Renew the session id to avoid session fixation
		$this->renewSession();
	}

	/**
	 * Check if we have an already authorized session
	 *
	 * @return boolean TRUE if this session has been authorized before (by a correct password)
	 */
	public function isAuthorized() {
		if (!$_SESSION['authorized']) {
			return FALSE;
		}
		if ($_SESSION['expires'] < time()) {
			// This session has already expired
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Check if our session is expired.
	 * Useful only right after a FALSE "isAuthorized" to see if this is the
	 * reason for not being authorized anymore.
	 *
	 * @return boolean TRUE if an authorized session exists, but is expired
	 */
	public function isExpired() {
		if (!$_SESSION['authorized']) {
			// Session never existed, means it is not "expired"
			return FALSE;
		}
		if ($_SESSION['expires'] < time()) {
			// This session was authorized before, but has expired
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Refreshes our session information, rising the expire time.
	 * Also generates a new session ID every 5 minutes to minimize the risk of
	 * session hijacking.
	 *
	 * @return void
	 */
	public function refreshSession() {
		$_SESSION['tstamp'] = time();
		$_SESSION['expires'] = time() + $this->expireTimeInMinutes * 60;
		if (time() > $_SESSION['lastSessionId'] + $this->regenerateSessionIdTime * 60) {
			// Renew our session ID
			$_SESSION['lastSessionId'] = time();
			$this->renewSession();
		}
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
	private function getSessionFile($id) {
		$sessionSavePath = $this->getSessionSavePath();
		return $sessionSavePath . '/hash_' . $this->getSessionHash($id);
	}

	/**
	 * Open function. See @session_set_save_handler
	 *
	 * @param string $savePath
	 * @param string $sessionName
	 * @return boolean
	 */
	public function open($savePath, $sessionName) {
		return TRUE;
	}

	/**
	 * Close function. See @session_set_save_handler
	 *
	 * @return boolean
	 */
	public function close() {
		return TRUE;
	}

	/**
	 * Read session data. See @session_set_save_handler
	 *
	 * @param string $id The session id
	 * @return string
	 */
	public function read($id) {
		$sessionFile = $this->getSessionFile($id);
		return (string) (@file_get_contents($sessionFile));
	}

	/**
	 * Write session data. See @session_set_save_handler
	 *
	 * @param string $id The session id
	 * @param string $sessionData The data to be stored
	 * @return boolean
	 */
	public function write($id, $sessionData) {
		$sessionFile = $this->getSessionFile($id);
		return \TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($sessionFile, $sessionData);
	}

	/**
	 * Destroys one session. See @session_set_save_handler
	 *
	 * @param string $id The session id
	 * @return string
	 */
	public function destroy($id) {
		$sessionFile = $this->getSessionFile($id);
		return @unlink($sessionFile);
	}

	/**
	 * Garbage collect session info. See @session_set_save_handler
	 *
	 * @param integer $maxLifeTime The setting of session.gc_maxlifetime
	 * @return boolean
	 */
	public function gc($maxLifeTime) {
		$sessionSavePath = $this->getSessionSavePath();
		$files = glob($sessionSavePath . '/hash_*');
		if (!is_array($files)) {
			return TRUE;
		}
		foreach ($files as $filename) {
			if (filemtime($filename) + $this->expireTimeInMinutes * 60 < time()) {
				@unlink($filename);
			}
		}
		return TRUE;
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
	 *
	 * @return void
	 */
	public function __destruct() {
		session_write_close();
	}

}


?>