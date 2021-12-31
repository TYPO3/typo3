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

namespace TYPO3\CMS\Core\Migrations;

/**
 * Migrate TCA from old to new syntax. Used in bootstrap and Flex Form Data Structures.
 *
 * @internal Class and API may change any time.
 */
class TcaMigration
{
    /**
     * Accumulate migration messages
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Run some general TCA validations, then migrate old TCA to new TCA.
     *
     * This class is typically called within bootstrap with empty caches after all TCA
     * files from extensions have been loaded. The migration is then applied and
     * the migrated result is cached.
     * For flex form TCA, this class is called dynamically if opening a record in the backend.
     *
     * See unit tests for details.
     *
     * @param array $tca
     * @return array
     */
    public function migrate(array $tca): array
    {
        $this->validateTcaType($tca);

        $tca = $this->migrateColumnsConfig($tca);
        $tca = $this->migrateLocalizeChildrenAtParentLocalization($tca);
        $tca = $this->migratePagesLanguageOverlayRemoval($tca);
        $tca = $this->removeSelIconFieldPath($tca);
        $tca = $this->removeSetToDefaultOnCopy($tca);
        $tca = $this->sanitizeControlSectionIntegrity($tca);
        $tca = $this->removeEnableMultiSelectFilterTextfieldConfiguration($tca);
        $tca = $this->removeExcludeFieldForTransOrigPointerField($tca);
        $tca = $this->removeShowRecordFieldListField($tca);
        $tca = $this->removeWorkspacePlaceholderShadowColumnsConfiguration($tca);
        $tca = $this->migrateLanguageFieldToTcaTypeLanguage($tca);
        $tca = $this->migrateSpecialLanguagesToTcaTypeLanguage($tca);
        $tca = $this->removeShowRemovedLocalizationRecords($tca);
        $tca = $this->migrateFileFolderConfiguration($tca);
        $tca = $this->migrateLevelLinksPosition($tca);
        $tca = $this->migrateRootUidToStartingPoints($tca);
        $tca = $this->migrateSelectAuthModeIndividualItemsKeywordToNewPosition($tca);

        return $tca;
    }

    /**
     * Get messages of migrated fields. Can be used for deprecation messages after migrate() was called.
     *
     * @return array Migration messages
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Check for required TCA configuration
     *
     * @param array $tca Incoming TCA
     */
    protected function validateTcaType(array $tca)
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['config']) && is_array($fieldConfig['config']) && empty($fieldConfig['config']['type'])) {
                    throw new \UnexpectedValueException(
                        'Missing "type" in TCA of field "[\'' . $table . '\'][\'' . $fieldName . '\'][\'config\']".',
                        1482394401
                    );
                }
            }
        }
    }

    /**
     * Find columns fields that don't have a 'config' section at all, add
     * ['config']['type'] = 'none'; for those to enforce config
     *
     * @param array $tca Incoming TCA
     * @return array
     */
    protected function migrateColumnsConfig(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if ((!isset($fieldConfig['config']) || !is_array($fieldConfig['config'])) && !isset($fieldConfig['type'])) {
                    $fieldConfig['config'] = [
                        'type' => 'none',
                    ];
                    $this->messages[] = 'TCA table "' . $table . '" columns field "' . $fieldName . '"'
                        . ' had no mandatory "config" section. This has been added with default type "none":'
                        . ' TCA "' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'type\'] = \'none\'"';
                }
            }
        }
        return $tca;
    }

    /**
     * Option $TCA[$table]['columns'][$columnName]['config']['behaviour']['localizeChildrenAtParentLocalization']
     * is always on, so this option can be removed.
     *
     * @param array $tca
     * @return array the modified TCA structure
     */
    protected function migrateLocalizeChildrenAtParentLocalization(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? null) !== 'inline') {
                    continue;
                }

                $localizeChildrenAtParentLocalization = ($fieldConfig['config']['behaviour']['localizeChildrenAtParentLocalization'] ?? null);
                if ($localizeChildrenAtParentLocalization === null) {
                    continue;
                }

                if ($localizeChildrenAtParentLocalization) {
                    $this->messages[] = 'The TCA setting \'localizeChildrenAtParentLocalization\' is deprecated '
                        . ' and should be removed from TCA for ' . $table . '[\'columns\']'
                        . '[\'' . $fieldName . '\'][\'config\'][\'behaviour\'][\'localizeChildrenAtParentLocalization\']';
                } else {
                    $this->messages[] = 'The TCA setting \'localizeChildrenAtParentLocalization\' is deprecated '
                        . ', as this functionality is always enabled. The option should be removed from TCA for '
                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'behaviour\']'
                        . '[\'localizeChildrenAtParentLocalization\']';
                }
                unset($fieldConfig['config']['behaviour']['localizeChildrenAtParentLocalization']);
            }
        }
        return $tca;
    }

    /**
     * Removes $TCA['pages_language_overlay'] if defined.
     *
     * @param array $tca
     * @return array the modified TCA structure
     */
    protected function migratePagesLanguageOverlayRemoval(array $tca)
    {
        if (isset($tca['pages_language_overlay'])) {
            $this->messages[] = 'The TCA table \'pages_language_overlay\' is'
                . ' not used anymore and has been removed automatically in'
                . ' order to avoid negative side-effects.';
            unset($tca['pages_language_overlay']);
        }
        return $tca;
    }

    /**
     * Removes configuration removeEnableMultiSelectFilterTextfield
     *
     * @param array $tca
     * @return array the modified TCA structure
     */
    protected function removeEnableMultiSelectFilterTextfieldConfiguration(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (!isset($fieldConfig['config']['enableMultiSelectFilterTextfield'])) {
                    continue;
                }

                $this->messages[] = 'The TCA setting \'enableMultiSelectFilterTextfield\' is deprecated '
                    . ' and should be removed from TCA for ' . $table . '[\'columns\']'
                    . '[\'' . $fieldName . '\'][\'config\'][\'enableMultiSelectFilterTextfield\']';
                unset($fieldConfig['config']['enableMultiSelectFilterTextfield']);
            }
        }
        return $tca;
    }

    /**
     * Removes $TCA[$mytable][ctrl][selicon_field_path]
     *
     * @param array $tca
     * @return array the modified TCA structure
     */
    protected function removeSelIconFieldPath(array $tca): array
    {
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['ctrl']['selicon_field_path'])) {
                $this->messages[] = 'The TCA table \'' . $table . '\' defines '
                    . '[ctrl][selicon_field_path] which should be removed from TCA, '
                    . 'as it is not in use anymore.';
                unset($configuration['ctrl']['selicon_field_path']);
            }
        }
        return $tca;
    }

    /**
     * Removes $TCA[$mytable][ctrl][setToDefaultOnCopy]
     *
     * @param array $tca
     * @return array the modified TCA structure
     */
    protected function removeSetToDefaultOnCopy(array $tca): array
    {
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['ctrl']['setToDefaultOnCopy'])) {
                $this->messages[] = 'The TCA table \'' . $table . '\' defines '
                    . '[ctrl][setToDefaultOnCopy] which should be removed from TCA, '
                    . 'as it is not in use anymore.';
                unset($configuration['ctrl']['setToDefaultOnCopy']);
            }
        }
        return $tca;
    }

    /**
     * Ensures that system internal columns that are required for data integrity
     * (e.g. localize or copy a record) are available in case they have been defined
     * in $GLOBALS['TCA'][<table-name>]['ctrl'].
     *
     * The list of references to usages below is not necessarily complete.
     *
     * @param array $tca
     * @return array
     *
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::fillInFieldArray()
     */
    protected function sanitizeControlSectionIntegrity(array $tca): array
    {
        $defaultControlSectionColumnConfig = [
            'type' => 'passthrough',
            'default' => 0,
        ];
        $controlSectionNames = [
            'origUid' => $defaultControlSectionColumnConfig,
            'languageField' => [
                'type' => 'language',
            ],
            'transOrigPointerField' => $defaultControlSectionColumnConfig,
            'translationSource' => $defaultControlSectionColumnConfig,
        ];

        foreach ($tca as $tableName => &$configuration) {
            foreach ($controlSectionNames as $controlSectionName => $controlSectionColumnConfig) {
                $columnName = $configuration['ctrl'][$controlSectionName] ?? null;
                if (empty($columnName) || !empty($configuration['columns'][$columnName])) {
                    continue;
                }
                $configuration['columns'][$columnName] = [
                    'config' => $controlSectionColumnConfig,
                ];
            }
        }
        return $tca;
    }

    /**
     * Removes $TCA[$mytable][columns][_transOrigPointerField_][exclude] if defined
     *
     * @param array $tca
     *
     * @return array
     */
    protected function removeExcludeFieldForTransOrigPointerField(array $tca): array
    {
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['ctrl']['transOrigPointerField'],
                $configuration['columns'][$configuration['ctrl']['transOrigPointerField']]['exclude'])
            ) {
                $this->messages[] = 'The \'' . $table . '\' TCA tables transOrigPointerField '
                    . '\'' . $configuration['ctrl']['transOrigPointerField'] . '\' is defined '
                    . ' as excluded field which is no longer needed and should therefore be removed. ';
                unset($configuration['columns'][$configuration['ctrl']['transOrigPointerField']]['exclude']);
            }
        }

        return $tca;
    }

    /**
     * Removes $TCA[$mytable]['interface']['showRecordFieldList'] and also $TCA[$mytable]['interface']
     * if `showRecordFieldList` was the only key in the array.
     *
     * @param array $tca
     * @return array
     */
    protected function removeShowRecordFieldListField(array $tca): array
    {
        foreach ($tca as $table => &$configuration) {
            if (!isset($configuration['interface']['showRecordFieldList'])) {
                continue;
            }
            $this->messages[] = 'The \'' . $table . '\' TCA configuration \'showRecordFieldList\''
                . ' inside the section \'interface\' is not evaluated anymore and should therefore be removed.';
            unset($configuration['interface']['showRecordFieldList']);
            if ($configuration['interface'] === []) {
                unset($configuration['interface']);
            }
        }

        return $tca;
    }

    /**
     * Removes $TCA[$mytable][ctrl][shadowColumnsForMovePlaceholders]
     * and $TCA[$mytable][ctrl][shadowColumnsForNewPlaceholders]
     *
     * @param array $tca
     * @return array the modified TCA structure
     */
    protected function removeWorkspacePlaceholderShadowColumnsConfiguration(array $tca): array
    {
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['ctrl']['shadowColumnsForNewPlaceholders'])) {
                $this->messages[] = 'The TCA table \'' . $table . '\' defines '
                    . '[ctrl][shadowColumnsForNewPlaceholders] which should be removed from TCA, '
                    . 'as it is not in use anymore.';
                unset($configuration['ctrl']['shadowColumnsForNewPlaceholders']);
            }
            if (isset($configuration['ctrl']['shadowColumnsForMovePlaceholders'])) {
                $this->messages[] = 'The TCA table \'' . $table . '\' defines '
                    . '[ctrl][shadowColumnsForMovePlaceholders] which should be removed from TCA, '
                    . 'as it is not in use anymore.';
                unset($configuration['ctrl']['shadowColumnsForMovePlaceholders']);
            }
        }
        return $tca;
    }

    /**
     * Replaces $TCA[$mytable][columns][$TCA[$mytable][ctrl][languageField]][config] with
     * $TCA[$mytable][columns][$TCA[$mytable][ctrl][languageField]][config][type] = 'language'
     *
     * @param array $tca
     * @return array
     */
    protected function migrateLanguageFieldToTcaTypeLanguage(array $tca): array
    {
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['ctrl']['languageField'], $configuration['columns'][$configuration['ctrl']['languageField']])
                && ($configuration['columns'][$configuration['ctrl']['languageField']]['config']['type'] ?? '') !== 'language'
            ) {
                $this->messages[] = 'The TCA field \'' . $configuration['ctrl']['languageField'] . '\' '
                    . 'of table \'' . $table . '\' is defined as the \'languageField\' and should '
                    . 'therefore use the TCA type \'language\' instead of TCA type \'select\' with '
                    . '\'foreign_table=sys_language\' or \'special=languages\'.';
                $configuration['columns'][$configuration['ctrl']['languageField']]['config'] = [
                    'type' => 'language',
                ];
            }
        }

        return $tca;
    }

    /**
     * Replaces $TCA[$mytable][columns][field][config][special] = 'languages' with
     * $TCA[$mytable][columns][field][config][type] = 'language'
     *
     * @param array $tca
     * @return array
     */
    protected function migrateSpecialLanguagesToTcaTypeLanguage(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'select'
                    || (string)($fieldConfig['config']['special'] ?? '') !== 'languages'
                ) {
                    continue;
                }
                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' is '
                    . 'defined as type \'select\' with the \'special=languages\' option. This is not '
                    . 'evaluated anymore and should be replaced by the TCA type \'language\'.';
                $fieldConfig['config'] = [
                    'type' => 'language',
                ];
            }
        }

        return $tca;
    }

    protected function removeShowRemovedLocalizationRecords(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'inline'
                    || !isset($fieldConfig['config']['appearance']['showRemovedLocalizationRecords'])
                ) {
                    continue;
                }
                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' is '
                    . 'defined as type \'inline\' with the \'appearance.showRemovedLocalizationRecords\' option set. '
                    . 'As this option is not evaluated anymore and no replacement exists, it should be removed from TCA.';
                unset($fieldConfig['config']['appearance']['showRemovedLocalizationRecords']);
            }
        }

        return $tca;
    }

    /**
     * Moves the "fileFolder" configuration of TCA columns type=select
     * into sub array "fileFolderConfig", while renaming those options.
     *
     * @param array $tca
     * @return array
     */
    protected function migrateFileFolderConfiguration(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'select'
                    || !isset($fieldConfig['config']['fileFolder'])
                ) {
                    continue;
                }
                $fieldConfig['config']['fileFolderConfig'] = [
                    'folder' => $fieldConfig['config']['fileFolder'],
                ];
                unset($fieldConfig['config']['fileFolder']);
                if (isset($fieldConfig['config']['fileFolder_extList'])) {
                    $fieldConfig['config']['fileFolderConfig']['allowedExtensions'] = $fieldConfig['config']['fileFolder_extList'];
                    unset($fieldConfig['config']['fileFolder_extList']);
                }
                if (isset($fieldConfig['config']['fileFolder_recursions'])) {
                    $fieldConfig['config']['fileFolderConfig']['depth'] = $fieldConfig['config']['fileFolder_recursions'];
                    unset($fieldConfig['config']['fileFolder_recursions']);
                }
                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' is '
                    . 'defined as type \'select\' with the \'fileFolder\' configuration option set. To streamline '
                    . 'the configuration, all \'fileFolder\' related configuration options were moved into a '
                    . 'dedicated sub array \'fileFolderConfig\', while \'fileFolder\' is now just \'folder\' and '
                    . 'the other options have been renamed to \'allowedExtensions\' and \'depth\'. '
                    . 'The TCA configuration should be adjusted accordingly.';
            }
        }

        return $tca;
    }

    /**
     * The [appearance][levelLinksPosition] option can be used
     * to select the position of the level links. This option
     * was previously misused to disable all those links by
     * setting it to "none". Since all of those links can be
     * disabled by a dedicated option, e.g. showNewRecordLink,
     * this wizard sets those options to false and unsets the
     * invalid levelLinksPosition value.
     *
     * @param array $tca
     * @return array
     */
    protected function migrateLevelLinksPosition(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if ((string)($fieldConfig['config']['type'] ?? '') !== 'inline'
                    || (string)($fieldConfig['config']['appearance']['levelLinksPosition'] ?? '') !== 'none'
                ) {
                    continue;
                }
                // Unset levelLinksPosition and disable all level link buttons
                unset($fieldConfig['config']['appearance']['levelLinksPosition']);
                $fieldConfig['config']['appearance']['showAllLocalizationLink'] = false;
                $fieldConfig['config']['appearance']['showSynchronizationLink'] = false;
                $fieldConfig['config']['appearance']['showNewRecordLink'] = false;

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets '
                    . '[appearance][levelLinksPosition] to "none", while only "top", "bottom" and "both" are supported. '
                    . 'The TCA configuration should be adjusted accordingly. In case you want to disable all level links, '
                    . 'use the corresponding level link specific options, e.g. [appearance][showNewRecordLink], instead.';
            }
        }

        return $tca;
    }

    /**
     * If a column has [treeConfig][rootUid] defined, migrate to [treeConfig][startingPoints] on the same level.
     *
     * @param array $tca
     * @return array
     */
    protected function migrateRootUidToStartingPoints(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if ((int)($fieldConfig['config']['treeConfig']['rootUid'] ?? 0) === 0
                    || !in_array((string)($fieldConfig['config']['type'] ?? ''), ['select', 'category'], true)
                ) {
                    continue;
                }

                $fieldConfig['config']['treeConfig']['startingPoints'] = (string)(int)$fieldConfig['config']['treeConfig']['rootUid'];
                unset($fieldConfig['config']['treeConfig']['rootUid']);

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets '
                    . '[treeConfig][rootUid], which is superseded by [treeConfig][startingPoints].'
                    . 'The TCA configuration should be adjusted accordingly.';
            }
        }

        return $tca;
    }

    /**
     * If a column has authMode=individual and items with the corresponding key on position 5
     * defined, migrate the key to position 6, since position 5 is used for the description.
     *
     * @param array $tca
     * @return array
     */
    protected function migrateSelectAuthModeIndividualItemsKeywordToNewPosition(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'select' || ($fieldConfig['config']['authMode'] ?? '') !== 'individual') {
                    continue;
                }

                foreach ($fieldConfig['config']['items'] ?? [] as $index => $item) {
                    if (in_array($item[4] ?? '', ['EXPL_ALLOW', 'EXPL_DENY'], true)) {
                        $tca[$table]['columns'][$fieldName]['config']['items'][$index][5] = $item[4];
                        $tca[$table]['columns'][$fieldName]['config']['items'][$index][4] = '';

                        // This migration does not log any message
                    }
                }
            }
        }

        return $tca;
    }
}
