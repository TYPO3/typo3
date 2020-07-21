<?php

declare(strict_types=1);

namespace TYPO3\CMS\Install\Updates\RowUpdater;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Migrate any translatable table rows from transOrigDiffSourceField being stored as serialized
 * string to json_encoded string.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class L18nDiffsourceToJsonMigration implements RowUpdaterInterface
{
    public function getTitle(): string
    {
        return 'Migrate transOrigDiffSourceField field to json encoded string.';
    }

    public function hasPotentialUpdateForTable(string $tableName): bool
    {
        return BackendUtility::isTableLocalizable($tableName) && !empty($GLOBALS['TCA'][$tableName]['ctrl']['transOrigDiffSourceField']);
    }

    public function updateTableRow(string $tableName, array $row): array
    {
        $fieldName = $GLOBALS['TCA'][$tableName]['ctrl']['transOrigDiffSourceField'];
        if (
            !empty($row[$fieldName])
            && is_scalar($row[$fieldName])
            && ($fieldContent = @unserialize((string)$row[$fieldName], ['allowed_classes' => false])) !== false
        ) {
            if (is_object($fieldContent)) {
                $row[$fieldName] = null;
            } else {
                $row[$fieldName] = json_encode($fieldContent);
            }
        }
        return $row;
    }
}
