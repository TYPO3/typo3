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

namespace TYPO3\CMS\Backend\Search\LiveSearch;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

enum DatabaseRecordActionType: string
{
    case EDIT = 'edit';
    case LIST = 'list';
    case LAYOUT = 'layout';
    case PREVIEW = 'preview';

    /**
     * Resolve the default action identifier from TSconfig:
     * 1. Table-specific default (options.liveSearch.actions.<TABLE>.default)
     * 2. Global default (options.liveSearch.actions.default)
     * 3. Fallback to EDIT (for pages to LAYOUT)
     * The value is then converted to a DatabaseRecordActionType enum;
     * if conversion fails, fallback is used as a safe default.
     *
     * @param BackendUserAuthentication $backendUser The current backend user
     * @param string $table The table name to find the default action for
     * @return DatabaseRecordActionType NULL if file is missing or deleted, the generated url otherwise
     */
    public static function fromUserForTable(BackendUserAuthentication $backendUser, string $table): DatabaseRecordActionType
    {
        $defaultAction = $table === 'pages' ? self::LAYOUT : self::EDIT;
        $userTsConfig = $backendUser->getTSConfig();

        return self::tryFrom(
            $userTsConfig['options.']['liveSearch.']['actions.'][$table . '.']['default']
            ?? $userTsConfig['options.']['liveSearch.']['actions.']['default']
            ?? $defaultAction->value
        ) ?? $defaultAction;
    }
}
