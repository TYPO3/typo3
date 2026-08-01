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

/**
 * FormDataProvider for backend user settings.
 *
 * Renders fields overridden via user TSconfig `setup.override.<fieldName>`
 * as read-only in the be_users_settings pseudo-table, as their values are
 * enforced on every request and cannot be changed by the user.
 *
 * @internal
 */
readonly class UserSettingsOverrideFields implements FormDataProviderInterface
{
    public function __construct(
        private UserSettingsSchema $userSettingsSchema,
    ) {}

    public function addData(array $result): array
    {
        if ($result['tableName'] !== 'be_users_settings') {
            return $result;
        }

        $overrideConfig = $result['userTsConfig']['setup.']['override.'] ?? [];
        foreach (array_keys($overrideConfig) as $fieldName) {
            $fieldName = rtrim((string)$fieldName, '.');
            if ($this->userSettingsSchema->getColumn($fieldName) === null) {
                continue;
            }
            $tcaFieldName = $this->userSettingsSchema->getTcaFieldName($fieldName);
            if (isset($result['processedTca']['columns'][$tcaFieldName])) {
                $result['processedTca']['columns'][$tcaFieldName]['config']['readOnly'] = true;
            }
        }

        return $result;
    }
}
