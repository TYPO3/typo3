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
use TYPO3\CMS\Core\Authentication\UserSettingsSchema;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FormDataProvider for backend user settings.
 *
 * Removes fields disabled via user TSconfig `setup.fields.<fieldName>.disabled = 1`
 * from the showitem definitions of the be_users_settings pseudo-table.
 *
 * @internal
 */
readonly class UserSettingsDisabledFields implements FormDataProviderInterface
{
    public function __construct(
        private UserSettingsSchema $userSettingsSchema,
    ) {}

    public function addData(array $result): array
    {
        if ($result['tableName'] !== 'be_users_settings') {
            return $result;
        }

        $fieldsConfig = $result['userTsConfig']['setup.']['fields.'] ?? [];
        if (!empty($fieldsConfig['password.']['disabled'])) {
            // If password is disabled, disable the password confirmation (password2) as well
            $fieldsConfig['password2.']['disabled'] = 1;
        }

        $disabledTcaFieldNames = [];
        foreach ($fieldsConfig as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['disabled'])) {
                continue;
            }
            $fieldName = rtrim((string)$fieldName, '.');
            if ($this->userSettingsSchema->getColumn($fieldName) === null) {
                continue;
            }
            $disabledTcaFieldNames[] = $this->userSettingsSchema->getTcaFieldName($fieldName);
        }
        if ($disabledTcaFieldNames === []) {
            return $result;
        }

        foreach ($result['processedTca']['types'] ?? [] as $typeName => $typeConfig) {
            $showitemParts = GeneralUtility::trimExplode(',', $typeConfig['showitem'] ?? '', true);
            $showitemParts = array_filter(
                $showitemParts,
                static fn(string $item): bool => !in_array(trim(explode(';', $item, 2)[0]), $disabledTcaFieldNames, true)
            );
            $result['processedTca']['types'][$typeName]['showitem'] = implode(',', $showitemParts);
        }

        return $result;
    }
}
