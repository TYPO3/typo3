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

namespace TYPO3\CMS\Backend\Configuration;

use TYPO3\CMS\Core\Authentication\UserSettingsSchema;

/**
 * Provides TCA configuration for the backend user settings form.
 *
 * This creates a "fake TCA" structure for the user settings form, similar to
 * how site configuration uses fake TCA for sites stored in YAML files.
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
readonly class UserSettingsTcaConfiguration
{
    public function __construct(
        private UserSettingsSchema $userSettingsSchema,
    ) {}

    /**
     * Returns a "fake TCA" for the be_users_settings pseudo-table.
     */
    public function getTca(): array
    {
        $columns = $GLOBALS['TCA']['be_users']['columns']['user_settings']['columns'] ?? [];
        foreach ($columns as $fieldName => $columnConfig) {
            $columns[$fieldName] = $this->userSettingsSchema->resolveInheritFromParent($fieldName, $columnConfig);
        }

        return [
            'be_users_settings' => [
                'ctrl' => [
                    'title' => 'backend.user_profile:user_settings',
                ],
                'columns' => $columns,
                'types' => [
                    '0' => [
                        'showitem' => $this->userSettingsSchema->getShowitem(),
                    ],
                ],
            ],
        ];
    }
}
