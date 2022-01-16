<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Install\Service\Session;

use TYPO3\CMS\Core\Security\BlockSerializationTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\Exception;

/**
 * PHP session handling with "secure" session files (hashed session id)
 * see http://www.php.net/manual/en/function.session-set-save-handler.php
 */
class FileSessionHandler implements \SessionHandlerInterface
{
    use BlockSerializationTrait;

    /**
     * The path to our var/session/ folder (where we can write our sessions). Set in the
     * constructor.
     * Path where to store our session files in var/session/.
     *
     * @var string
     */
    private $sessionPath = 'session/';

    /**
     * time (minutes) to expire an unused session
     *
     * @var int
     */
    private $expirationTimeInMinutes = 60;

    public function __construct(string $sessionPath, int $expirationTimeInMinutes)
    {
        $this->sessionPath = rtrim($sessionPath, '/') . '/';
        $this->expirationTimeInMinutes = $expirationTimeInMinutes;
        // Start our PHP session early so that hasSession() works
        session_save_path($this->getSessionSavePath());
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
        $sessionSavePath = $this->sessionPath . GeneralUtility::hmac('session:' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
        $this->ensureSessionSavePathExists($sessionSavePath);
        return $sessionSavePath;
    }

    /**
     * Returns the file where to store our session data
     *
     * @param string $id
     * @return string A filename
     */
    private function getSessionFile(string $id)
    {
        $sessionSavePath = $this->getSessionSavePath();
        return $sessionSavePath . '/hash_' . $this->getSessionHash($id);
    }

    #[\ReturnTypeWillChange]
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

    #[\ReturnTypeWillChange]
    /**
     * Close function. See @session_set_save_handler
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    #[\ReturnTypeWillChange]
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
                    $length = (int)filesize($sessionFile);
                    if ($length > 0) {
                        $content = (string)fread($fd, $length);
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

    #[\ReturnTypeWillChange]
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
                'Session file not writable. Please check permission on ' .
                $this->sessionPath . ' and its subdirectories.',
                1424355157
            );
        }
        return $result;
    }

    #[\ReturnTypeWillChange]
    /**
     * Destroys one session. See @session_set_save_handler
     *
     * @param string $id The session id
     * @return bool
     */
    public function destroy($id): bool
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
    #[\ReturnTypeWillChange]
    public function gc($maxLifeTime)
    {
        $sessionSavePath = $this->getSessionSavePath();
        $files = glob($sessionSavePath . '/hash_*');
        if (!is_array($files)) {
            return true;
        }
        foreach ($files as $filename) {
            if (filemtime($filename) + $this->expirationTimeInMinutes * 60 < time()) {
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

    /**
     * Returns the session ID of the running session.
     *
     * @return string|false the session ID
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
    private function getSessionHash(string $sessionId = '')
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
     * Create directories for the session save path
     * and throw an exception if that fails.
     *
     * @param string $sessionSavePath The absolute path to the session files
     * @throws \TYPO3\CMS\Install\Exception
     */
    private function ensureSessionSavePathExists(string $sessionSavePath)
    {
        if (!is_dir($sessionSavePath)) {
            try {
                GeneralUtility::mkdir_deep($sessionSavePath);
            } catch (\RuntimeException $exception) {
                throw new \TYPO3\CMS\Install\Exception(
                    'Could not create session folder in ' . $this->sessionPath . '. Make sure it is writeable!',
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
            $indexContent = '<!DOCTYPE html>';
            $indexContent .= '<html><head><title></title><meta http-equiv=Refresh Content="0; Url=../../"/>';
            $indexContent .= '</head></html>';
            GeneralUtility::writeFile($sessionSavePath . '/index.html', $indexContent);
        }
    }
}
