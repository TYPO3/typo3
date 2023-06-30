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

namespace TYPO3\CMS\Core\Resource\Enum;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Enumeration for DuplicationBehavior
 */
enum DuplicationBehavior: string
{
    /**
     * If a file is uploaded and another file with
     * the same name already exists, the new file
     * is renamed.
     */
    case RENAME = 'rename';

    /**
     * If a file is uploaded and another file with
     * the same name already exists, the old file
     * gets overwritten by the new file.
     */
    case REPLACE = 'replace';

    /**
     * If a file is uploaded and another file with
     * the same name already exists, the process is
     * aborted.
     */
    case CANCEL = 'cancel';

    /**
     * Return the default duplication behaviour action, set in TSconfig
     */
    public static function getDefaultDuplicationBehaviour(?BackendUserAuthentication $backendUserAuthentication = null): DuplicationBehavior
    {
        if ($backendUserAuthentication === null) {
            return self::CANCEL;
        }
        $defaultAction = $backendUserAuthentication->getTSConfig()['options.']['file_list.']['uploader.']['defaultAction'] ?? '';

        if ($defaultAction === '') {
            return self::CANCEL;
        }

        $duplicationBehavior = self::tryFrom($defaultAction);
        if ($duplicationBehavior !== null) {
            return $duplicationBehavior;
        }

        GeneralUtility::makeInstance(LogManager::class)
            ->getLogger(__CLASS__)
            ->warning('TSConfig: options.file_list.uploader.defaultAction contains an invalid value ("{value}"), fallback to default value: "{default}"', [
                'value' => $defaultAction,
                'default' => self::CANCEL->value,
            ]);

        return self::CANCEL;
    }
}
