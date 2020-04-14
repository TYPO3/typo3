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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DestroySessionHook
{
    /**
     * If a fe_users' or be_users' password is updated, clear all sessions.
     *
     * @param string $status
     * @param string $table
     * @param int $id
     * @param array $fieldArray
     * @param DataHandler $dataHandler
     */
    public function processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, DataHandler $dataHandler)
    {
        if ($table !== 'be_users' && $table !== 'fe_users') {
            return;
        }
        if ($status !== 'update') {
            return;
        }
        if (!isset($fieldArray['password']) || (string)$fieldArray['password'] === '') {
            return;
        }

        $sessionManager = GeneralUtility::makeInstance(SessionManager::class);
        if ($table === 'be_users') {
            // Destroy BE user sessions for backend user
            $backend = $sessionManager->getSessionBackend('BE');
            $sessionManager->invalidateAllSessionsByUserId($backend, (int)$id, $GLOBALS['BE_USER']);
        }
        if ($table === 'fe_users') {
            // Destroy any FE user sessions for the given user
            $backend = $sessionManager->getSessionBackend('FE');
            $sessionManager->invalidateAllSessionsByUserId($backend, (int)$id);
        }
    }
}
