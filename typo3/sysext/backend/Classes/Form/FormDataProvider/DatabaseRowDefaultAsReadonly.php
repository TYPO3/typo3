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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Special data provider for replacing a database field with the value of
 * the default record in case "l10n_display" is set to "defaultAsReadonly".
 */
class DatabaseRowDefaultAsReadonly implements FormDataProviderInterface
{
    /**
     * Check each field for being an overlay, having l10n_display set to defaultAsReadonly
     * and whether the field exists in the default language row. If so, the current
     * database value will be replaced by the one from the default language row.
     */
    public function addData(array $result): array
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (!isset($result['defaultLanguageRow'][$fieldName])) {
                // No default value available for this field
                continue;
            }
            if (!GeneralUtility::inList(($result['processedTca']['columns'][$fieldName]['l10n_display'] ?? ''), 'defaultAsReadonly')) {
                // defaultAsReadonly is not set for this field
                continue;
            }
            if (!($result['databaseRow'][$result['processedTca']['ctrl']['languageField'] ?? null] ?? false)
                || !($result['databaseRow'][$result['processedTca']['ctrl']['transOrigPointerField'] ?? null] ?? false)
            ) {
                // The current record is not an overlay. Note: This check might have already took place
                // while creating the default language row. However, since this field might be set by
                // other data providers unintentional, we check this here again to be sure.
                continue;
            }
            if ((int)$result['databaseRow'][$result['processedTca']['ctrl']['transOrigPointerField']] !== (int)$result['defaultLanguageRow']['uid']) {
                // The current records "transOrigPointerField" doesn't point to the current default language row
                continue;
            }

            // Override the current database field with the one from the default language
            $result['databaseRow'][$fieldName] = $result['defaultLanguageRow'][$fieldName];
        }

        return $result;
    }
}
