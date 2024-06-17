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

namespace TYPO3\CMS\Backend\Authentication;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The TYPO3 Backend can be locked by creating a file named LOCK_BACKEND in a
 * specified directory (TYPO3_CONF_VARS[BE][lockBackendFile]). The default is
 * var/lock for composer-mode and config/ for legacy.
 *
 * This class encapsulates the logic to check for the existence of the lock file,
 * thus nobody needs to know it's a file and where to put it outside of this class.
 * It also enables future refactoring to support other means of backend un/locking.
 */
class BackendLocker
{
    public function isLocked(): bool
    {
        return @is_file($this->getAbsolutePathToLockFile());
    }

    public function lockBackend(string $redirectUriFromFileContent): void
    {
        GeneralUtility::writeFile($this->getAbsolutePathToLockFile(), $redirectUriFromFileContent);
    }

    public function unlock(): void
    {
        unlink($this->getAbsolutePathToLockFile());
    }

    public function getAbsolutePathToLockFile(): string
    {
        // This setting is empty by default to utilize the fallback storage location.
        // If set specifically, this is the preference.
        if (($GLOBALS['TYPO3_CONF_VARS']['BE']['lockBackendFile'] ?? '') !== '') {
            return Environment::getProjectPath() . '/' . $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBackendFile'];
        }

        return $this->getLockPath() . '/LOCK_BACKEND';
    }

    public function getRedirectUriFromLockContents(): string
    {
        return file_get_contents($this->getAbsolutePathToLockFile());
    }

    /**
     * Based on composer or legacy mode, return a fallback directory
     * location where LOCK_BACKEND can be stored.
     * Composer-mode: "var/lock" is preferred because it is recommended to
     *                be shared and persist across deployments and the location
     *                is writable by the webserver)
     * Legacy: "config/", because usually writable by webserver and persistent.
     */
    protected function getLockPath(): string
    {
        return Environment::isComposerMode()
               ? Environment::getVarPath() . '/lock'
               : Environment::getConfigPath();
    }
}
