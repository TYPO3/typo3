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

namespace TYPO3\CMS\Core\Hooks;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Various display conditions to check for e.g. installed extensions or configuration settings used.
 *
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
class TcaDisplayConditions
{
    /**
     * Check if an extension is loaded.
     *
     * @param array $parameters
     * @return bool
     */
    public function isExtensionInstalled(array $parameters): bool
    {
        $extension = $parameters['conditionParameters'][0] ?? '';
        if (!empty($extension)) {
            return ExtensionManagementUtility::isLoaded($extension);
        }
        return false;
    }

    /**
     * Check if the current record is the current backend user
     *
     * IMPORTANT: This only works for the be_users table.
     *
     * @param array $parameters
     * @return bool
     */
    public function isRecordCurrentUser(array $parameters): bool
    {
        $backendUser = $this->getBackendUser();
        $isCurrentUser = (int)($parameters['record']['uid'] ?? 0) === (int)$backendUser->user[$backendUser->userid_column];
        return strtolower($parameters['conditionParameters'][0] ?? 'true') !== 'true' ? !$isCurrentUser : $isCurrentUser;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
