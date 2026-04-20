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

namespace TYPO3\CMS\Core\Authentication;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides unified access to backend user settings configuration.
 *
 * This class consolidates access to user settings from both:
 * - TCA at $GLOBALS['TCA']['be_users']['columns']['user_settings']
 * - Legacy $GLOBALS['TYPO3_USER_SETTINGS'] (deprecated)
 *
 * TCA is the preferred source. Legacy global is supported for backward compatibility.
 *
 * @internal This class is not part of the TYPO3 Core API yet.
 */
readonly class UserSettingsSchema
{
    /**
     * Get all column configurations in legacy format.
     * This merges TCA-based config with legacy global, preferring TCA.
     *
     * @return array<string, array>
     */
    public function getColumns(): array
    {
        $columns = [];

        // First, get columns from TCA (primary source)
        $tcaColumns = $GLOBALS['TCA']['be_users']['columns']['user_settings']['columns'] ?? [];
        foreach ($tcaColumns as $fieldName => $tcaConfig) {
            $columns[$fieldName] = $this->resolveTcaColumn($fieldName, $tcaConfig);
        }

        // Then merge legacy global (for third-party backward compat)
        // @deprecated since TYPO3 v14, remove in TYPO3 v15
        $legacyColumns = $GLOBALS['TYPO3_USER_SETTINGS']['columns'] ?? [];
        foreach ($legacyColumns as $fieldName => $legacyConfig) {
            if (!isset($columns[$fieldName])) {
                $columns[$fieldName] = $legacyConfig;
            }
        }

        return $columns;
    }

    /**
     * Get configuration for a specific field in legacy format.
     */
    public function getColumn(string $fieldName): ?array
    {
        // Check TCA first
        $tcaConfig = $GLOBALS['TCA']['be_users']['columns']['user_settings']['columns'][$fieldName] ?? null;
        if ($tcaConfig !== null) {
            return $this->resolveTcaColumn($fieldName, $tcaConfig);
        }

        // Fall back to legacy global
        // @deprecated since TYPO3 v14, remove in TYPO3 v15
        return $GLOBALS['TYPO3_USER_SETTINGS']['columns'][$fieldName] ?? null;
    }

    /**
     * Returns a "fake TCA" for the be_users_settings pseudo-table.
     */
    public function getTca(): array
    {
        $columns = $GLOBALS['TCA']['be_users']['columns']['user_settings']['columns'] ?? [];
        foreach ($columns as $fieldName => $columnConfig) {
            $partitionedFieldName = $this->getTcaFieldName($fieldName);
            $columns[$partitionedFieldName] = $this->resolveInheritFromParent($fieldName, $columnConfig);
        }

        return [
            'be_users_settings' => [
                'ctrl' => [
                    'title' => 'backend.user_profile:user_settings',
                ],
                'columns' => $columns,
                'types' => [
                    '0' => [
                        'showitem' => $this->getTcaShowitem(),
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns a partitioned field name for use in TCA.
     * - e.g. `be_users__password`, reflecting `be_users` values
     * - e.g. `user_settings__titleLen`, reflecting JSON values
     */
    public function getTcaFieldName(string $fieldName): string
    {
        $configuration = $this->getColumn($fieldName);
        $partition = ($configuration['table'] ?? null) === 'be_users' ? 'be_users' : 'user_settings';
        return $partition . '__' . $fieldName;
    }

    private function resolveTcaFieldName(string $fieldName, bool $strict = true): string
    {
        $configuration = $this->getColumn($fieldName);
        if ($configuration !== null) {
            $partition = ($configuration['table'] ?? null) === 'be_users' ? 'be_users' : 'user_settings';
            return $partition . '__' . $fieldName;
        }
        if (!$strict) {
            return $fieldName;
        }
        throw new \LogicException(
            sprintf(
                'Column "%s" not found in UserSettingsSchema',
                $fieldName
            ),
            1776439141
        );
    }

    /**
     * Get the partitioned showitem string to be used as virtual TCA.
     */
    public function getTcaShowitem(): string
    {
        $items = GeneralUtility::trimExplode(',', $this->getRawShowitem(), true);
        $items = array_map(
            fn(string $fieldName): string => $this->resolveTcaFieldName($fieldName, false),
            $items
        );
        return implode(',', $items);
    }

    /**
     * Get the raw showitem string (merged from TCA and legacy global).
     */
    public function getRawShowitem(): string
    {
        $tcaShowitem = trim($GLOBALS['TCA']['be_users']['columns']['user_settings']['showitem'] ?? '');
        // @deprecated since TYPO3 v14, remove in TYPO3 v15
        $legacyShowitem = trim($GLOBALS['TYPO3_USER_SETTINGS']['showitem'] ?? '');

        if ($tcaShowitem !== '' && $legacyShowitem !== '') {
            // Merge: TCA first, then legacy additions
            return $tcaShowitem . ',' . $legacyShowitem;
        }

        return $tcaShowitem !== '' ? $tcaShowitem : $legacyShowitem;
    }

    /**
     * @return list<string>
     */
    public function getJsonFieldSettingKeys(): array
    {
        $keys = [];
        foreach ($this->getColumns() as $key => $config) {
            // Fields with 'table' => 'be_users' are stored in be_users columns directly
            // Also skip non-storable types like 'button' and 'mfa'
            $type = $config['type'] ?? 'text';
            if (($config['table'] ?? '') !== 'be_users'
                && !in_array($type, ['button', 'mfa'], true)
            ) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    /**
     * @return string[]
     */
    public function getDbColumnSettingKeys(): array
    {
        $keys = [];
        foreach ($this->getColumns() as $key => $config) {
            $type = $config['type'] ?? 'text';
            if (($config['table'] ?? '') === 'be_users'
                && !in_array($type, ['button', 'mfa', 'password'], true)
            ) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    public function isJsonFieldSetting(string $key): bool
    {
        return in_array($key, $this->getJsonFieldSettingKeys(), true);
    }

    public function isDbColumnSetting(string $key): bool
    {
        return in_array($key, $this->getDbColumnSettingKeys(), true);
    }

    /**
     * Returns field names that should trigger a JS persistent storage update
     * when their value changes in User Settings, so JS components can react
     * immediately without a page reload.
     *
     * @return string[]
     */
    public function getPersistentUpdateFieldNames(): array
    {
        $keys = [];
        foreach ($this->getColumns() as $key => $config) {
            if (!empty($config['persistentUpdate'])) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    public function getDefault(string $key): mixed
    {
        $config = $this->getColumn($key);
        return $config['default'] ?? null;
    }

    /**
     * Resolves inheritFromParent for a TCA column by merging with the
     * parent be_users TCA column configuration. Returns TCA format
     * (without legacy conversion).
     *
     * @internal
     */
    public function resolveInheritFromParent(string $fieldName, array $tcaConfig): array
    {
        if (!empty($tcaConfig['inheritFromParent'])) {
            $parentConfig = $GLOBALS['TCA']['be_users']['columns'][$fieldName] ?? [];
            $tcaConfig = array_replace_recursive($parentConfig, $tcaConfig);
            unset($tcaConfig['inheritFromParent']);
        }
        return $tcaConfig;
    }

    /**
     * Resolves a TCA column configuration, handling inheritFromParent,
     * and converts to legacy format.
     */
    private function resolveTcaColumn(string $fieldName, array $tcaConfig): array
    {
        return $this->convertTcaToLegacyFormat($fieldName, $this->resolveInheritFromParent($fieldName, $tcaConfig));
    }

    /**
     * Converts a TCA column configuration to legacy format.
     */
    private function convertTcaToLegacyFormat(string $fieldName, array $tcaConfig): array
    {
        $legacyConfig = [
            'label' => $tcaConfig['label'] ?? '',
        ];

        $config = $tcaConfig['config'] ?? [];
        $tcaType = $config['type'] ?? 'input';
        $renderType = $config['renderType'] ?? '';

        // Determine if this field is stored in be_users table
        // Fields with inheritFromParent that exist in be_users columns are table fields
        if (isset($GLOBALS['TCA']['be_users']['columns'][$fieldName])) {
            $legacyConfig['table'] = 'be_users';
        }

        // Convert TCA type to legacy type
        switch ($tcaType) {
            case 'input':
                $legacyConfig['type'] = 'text';
                if (isset($config['max'])) {
                    $legacyConfig['max'] = $config['max'];
                }
                break;

            case 'email':
                $legacyConfig['type'] = 'email';
                if (isset($config['max'])) {
                    $legacyConfig['max'] = $config['max'];
                }
                break;

            case 'number':
                $legacyConfig['type'] = 'number';
                break;

            case 'password':
                $legacyConfig['type'] = 'password';
                break;

            case 'check':
                $legacyConfig['type'] = 'check';
                break;

            case 'select':
                $legacyConfig['type'] = 'select';
                if (isset($config['items'])) {
                    $legacyConfig['items'] = $this->convertSelectItemsToLegacy($config['items']);
                }
                if (isset($config['itemsProcFunc'])) {
                    $legacyConfig['itemsProcFunc'] = $config['itemsProcFunc'];
                }
                break;

            case 'language':
                $legacyConfig['type'] = 'language';
                break;

            case 'file':
                $legacyConfig['type'] = 'avatar';
                break;

            case 'button':
                $legacyConfig['type'] = 'button';
                if (isset($config['buttonLabel'])) {
                    $legacyConfig['buttonlabel'] = $config['buttonLabel'];
                }
                if (isset($config['confirm'])) {
                    $legacyConfig['confirm'] = $config['confirm'];
                }
                if (isset($config['confirmData'])) {
                    $legacyConfig['confirmData'] = $config['confirmData'];
                }
                break;

            case 'mfa':
                $legacyConfig['type'] = 'mfa';
                break;

            case 'user':
                $legacyConfig['type'] = 'user';
                if (isset($config['renderType'])) {
                    $legacyConfig['userFunc'] = $config['renderType'];
                }
                break;

            default:
                $legacyConfig['type'] = 'text';
        }

        // Copy additional properties
        if (isset($config['default'])) {
            $legacyConfig['default'] = $config['default'];
        }
        if (isset($tcaConfig['access'])) {
            $legacyConfig['access'] = $tcaConfig['access'];
        }
        if (!empty($tcaConfig['persistentUpdate'])) {
            $legacyConfig['persistentUpdate'] = true;
        }

        return $legacyConfig;
    }

    /**
     * Converts TCA select items format to legacy format.
     * Handles both TCA format ([['label' => '...', 'value' => '...'], ...])
     * and legacy format (['value' => 'label', ...]).
     */
    private function convertSelectItemsToLegacy(array $items): array
    {
        $legacyItems = [];
        foreach ($items as $key => $item) {
            if (is_array($item) && isset($item['value']) && isset($item['label'])) {
                // TCA format: [['label' => '...', 'value' => '...'], ...]
                $legacyItems[$item['value']] = $item['label'];
            } elseif (is_string($item)) {
                // Legacy format: ['value' => 'label', ...]
                $legacyItems[$key] = $item;
            }
        }
        return $legacyItems;
    }
}
