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

namespace TYPO3\CMS\Reactions\Form;

use TYPO3\CMS\Core\Imaging\IconFactory;

/**
 * Helper method for TCA / FormEngine to list tables available for reactions
 *
 * @internal
 */
class ReactionItemsProcFunc
{
    public function __construct(
        private readonly IconFactory $iconFactory
    ) {
    }

    public function populateAvailableContentTables(array &$fieldDefinition): void
    {
        foreach ($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
            if ($tableConfiguration['ctrl']['adminOnly'] ?? false) {
                // Hide "admin only" tables
                continue;
            }
            if (($tableConfiguration['ctrl']['groupName'] ?? '') !== 'content' && $tableName !== 'pages') {
                // Hide tables that are not in the content group
                continue;
            }
            $fieldDefinition['items'][] = [
                ($tableConfiguration['ctrl']['title'] ?? '') ?: $tableName,
                $tableName,
                $this->iconFactory->mapRecordTypeToIconIdentifier($tableName, []),
            ];
        }
    }
}
