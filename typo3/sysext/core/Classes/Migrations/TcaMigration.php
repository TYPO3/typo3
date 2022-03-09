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

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $tca = $this->migrateInternalTypeFolderToTypeFolder($tca);
        $tca = $this->migrateRequiredFlag($tca);
        $tca = $this->migrateEmailFlagToEmailType($tca);
        $tca = $this->migrateTypeNoneColsToSize($tca);
        $tca = $this->migrateRenderTypeInputLinkToTypeLink($tca);

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

                        $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets ' . $item[4]
                            . ' at position 5 of the items array. This option has been shifted to position 6 and should be adjusted accordingly.';
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrates [config][internal_type] = 'folder' to [config][type] = 'folder'.
     * Also removes [config][internal_type] completely, if present.
     */
    protected function migrateInternalTypeFolderToTypeFolder(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'group' || !isset($fieldConfig['config']['internal_type'])) {
                    continue;
                }
                unset($tca[$table]['columns'][$fieldName]['config']['internal_type']);

                if ($fieldConfig['config']['internal_type'] === 'folder') {
                    $tca[$table]['columns'][$fieldName]['config']['type'] = 'folder';
                    $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' has been migrated to '
                        . 'the TCA type \'folder\'. Please adjust your TCA accordingly.';
                } else {
                    $this->messages[] = 'The property \'internal_type\' of the TCA field \'' . $fieldName . '\' of table \''
                        . $table . '\' is obsolete and has been removed. You can remove it from your TCA as it is not evaluated anymore.';
                }
            }
        }

        return $tca;
    }

    /**
     * Migrates [config][eval] = 'required' to [config][required] = true and removes 'required' from [config][eval].
     * If [config][eval] becomes empty, it will be removed completely.
     */
    protected function migrateRequiredFlag(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (!GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'required')) {
                    continue;
                }

                $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);
                // Remove "required" from $evalList
                $evalList = array_filter($evalList, static function (string $eval) {
                    return $eval !== 'required';
                });
                if ($evalList !== []) {
                    // Write back filtered 'eval'
                    $tca[$table]['columns'][$fieldName]['config']['eval'] = implode(',', $evalList);
                } else {
                    // 'eval' is empty, remove whole configuration
                    unset($tca[$table]['columns'][$fieldName]['config']['eval']);
                }

                $tca[$table]['columns'][$fieldName]['config']['required'] = true;
                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . '"required" in its "eval" list. This is not evaluated anymore and should be replaced '
                    . ' by `\'required\' => true`.';
            }
        }

        return $tca;
    }

    /**
     * Migrates [config][eval] = 'email' to [config][type] = 'email' and removes 'email' from [config][eval].
     * If [config][eval] contains 'trim', it will also be removed. If [config][eval] becomes empty, the option
     * will be removed completely.
     */
    protected function migrateEmailFlagToEmailType(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'input'
                    || !GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'email')
                ) {
                    // Early return in case column is not of type=input or does not define eval=email
                    continue;
                }

                // Set the TCA type to "email"
                $tca[$table]['columns'][$fieldName]['config']['type'] = 'email';

                $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);
                $evalList = array_filter($evalList, static function (string $eval) {
                    // Remove "email" and "trim" from $evalList
                    return $eval !== 'email' && $eval !== 'trim';
                });

                if ($evalList !== []) {
                    // Write back filtered 'eval'
                    $tca[$table]['columns'][$fieldName]['config']['eval'] = implode(',', $evalList);
                } else {
                    // 'eval' is empty, remove whole configuration
                    unset($tca[$table]['columns'][$fieldName]['config']['eval']);
                }

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . '"email" in its "eval" list. The field has therefore been migrated to the TCA type \'email\'. '
                    . 'Please adjust your TCA accordingly.';
            }
        }

        return $tca;
    }

    /**
     * Migrates type => "none" [config][cols] to [config][size] and removes "cols".
     */
    protected function migrateTypeNoneColsToSize(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'none' || !array_key_exists('cols', $fieldConfig['config'])) {
                    continue;
                }

                $tca[$table]['columns'][$fieldName]['config']['size'] = $fieldConfig['config']['cols'];
                unset($tca[$table]['columns'][$fieldName]['config']['cols']);

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . '"cols" in its config. This value has been migrated to the option "size". Please adjust your TCA accordingly.';
            }
        }

        return $tca;
    }

    /**
     * Migrates [config][renderType] = 'inputLink' to [config][type] = 'link'.
     * Migrates the [config][fieldConfig][linkPopup] to type specific configuration.
     * Removes anything except for "null" from [config][eval].
     * Removes option [config][max], if set.
     * Removes option [config][softref], if set to "typolink".
     */
    protected function migrateRenderTypeInputLinkToTypeLink(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'input'
                    || ($fieldConfig['config']['renderType'] ?? '') !== 'inputLink'
                ) {
                    // Early return in case column is not of type=input with renderType=inputLink
                    continue;
                }

                // Set the TCA type to "link"
                $tca[$table]['columns'][$fieldName]['config']['type'] = 'link';

                // Unset "renderType" and "max"
                unset(
                    $tca[$table]['columns'][$fieldName]['config']['max'],
                    $tca[$table]['columns'][$fieldName]['config']['renderType']
                );

                // Unset "softref" if set to "typolink"
                if (($fieldConfig['config']['softref'] ?? '') === 'typolink') {
                    unset($tca[$table]['columns'][$fieldName]['config']['softref']);
                }

                // Migrate the linkPopup configuration
                if (is_array($fieldConfig['config']['fieldControl']['linkPopup'] ?? false)) {
                    $linkPopupConfig = $fieldConfig['config']['fieldControl']['linkPopup'];
                    if ($linkPopupConfig['options']['blindLinkOptions'] ?? false) {
                        $availaleTypes = $GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler'] ?? [];
                        if ($availaleTypes !== []) {
                            $availaleTypes = array_keys($availaleTypes);
                        } else {
                            // Fallback to a static list, in case linkHandler configuration is not available at this point
                            $availaleTypes = ['page', 'file', 'folder', 'url', 'email', 'record', 'telephone'];
                        }
                        $tca[$table]['columns'][$fieldName]['config']['allowedTypes'] = array_values(array_diff(
                            $availaleTypes,
                            GeneralUtility::trimExplode(',', str_replace('mail', 'email', (string)$linkPopupConfig['options']['blindLinkOptions']), true)
                        ));
                    }
                    if ($linkPopupConfig['disabled'] ?? false) {
                        $tca[$table]['columns'][$fieldName]['config']['appearance']['enableBrowser'] = false;
                    }
                    if ($linkPopupConfig['options']['title'] ?? false) {
                        $tca[$table]['columns'][$fieldName]['config']['appearance']['browserTitle'] = (string)$linkPopupConfig['options']['title'];
                    }
                    if ($linkPopupConfig['options']['blindLinkFields'] ?? false) {
                        $tca[$table]['columns'][$fieldName]['config']['appearance']['allowedOptions'] = array_values(array_diff(
                            ['target', 'title', 'class', 'params', 'rel'],
                            GeneralUtility::trimExplode(',', (string)$linkPopupConfig['options']['blindLinkFields'], true)
                        ));
                    }
                    if ($linkPopupConfig['options']['allowedExtensions'] ?? false) {
                        $tca[$table]['columns'][$fieldName]['config']['appearance']['allowedFileExtensions'] = GeneralUtility::trimExplode(
                            ',',
                            (string)$linkPopupConfig['options']['allowedExtensions'],
                            true
                        );
                    }
                }

                // Unset ['fieldControl']['linkPopup'] - Note: We do this here to ensure
                // also an invalid (e.g. not an array) field control configuration is removed.
                unset($tca[$table]['columns'][$fieldName]['config']['fieldControl']['linkPopup']);

                // In case "linkPopup" has been the only configured fieldControl, unset ['fieldControl'], too.
                if (empty($tca[$table]['columns'][$fieldName]['config']['fieldControl'])) {
                    unset($tca[$table]['columns'][$fieldName]['config']['fieldControl']);
                }

                if (GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'null')) {
                    // Set "eval" to "null", since it's currently defined and the only allowed "eval" for type=link
                    $tca[$table]['columns'][$fieldName]['config']['eval'] = 'null';
                } else {
                    unset($tca[$table]['columns'][$fieldName]['config']['eval']);
                }

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'renderType="inputLink". The field has therefore been migrated to the TCA type \'link\'. '
                    . 'This includes corresponding configuration of the "linkPopup", as well as obsolete field '
                    . 'configurations, such as "max" and "softref". Please adjust your TCA accordingly.';
            }
        }

        return $tca;
    }
}
