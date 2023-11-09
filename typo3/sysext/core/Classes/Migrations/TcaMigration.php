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

use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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
        $tca = $this->migrateInternalTypeFolderToTypeFolder($tca);
        $tca = $this->migrateRequiredFlag($tca);
        $tca = $this->migrateNullFlag($tca);
        $tca = $this->migrateEmailFlagToEmailType($tca);
        $tca = $this->migrateTypeNoneColsToSize($tca);
        $tca = $this->migrateRenderTypeInputLinkToTypeLink($tca);
        $tca = $this->migratePasswordAndSaltedPasswordToPasswordType($tca);
        $tca = $this->migrateRenderTypeInputDateTimeToTypeDatetime($tca);
        $tca = $this->removeAuthModeEnforce($tca);
        $tca = $this->removeSelectAuthModeIndividualItemsKeyword($tca);
        $tca = $this->migrateAuthMode($tca);
        $tca = $this->migrateRenderTypeColorpickerToTypeColor($tca);
        $tca = $this->migrateEvalIntAndDouble2ToTypeNumber($tca);
        $tca = $this->removeAlwaysDescription($tca);
        $tca = $this->migrateFalHandlingInInlineToTypeFile($tca);
        $tca = $this->removeCtrlCruserId($tca);
        $tca = $this->removeFalRelatedElementBrowserOptions($tca);
        $tca = $this->removeFalRelatedOptionsFromTypeInline($tca);
        $tca = $this->removePassContentFromTypeNone($tca);
        $tca = $this->migrateItemsToAssociativeArray($tca);
        $tca = $this->removeMmInsertFields($tca);

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
     * Migrates [config][eval] = 'null' to [config][nullable] = true and removes 'null' from [config][eval].
     * If [config][eval] becomes empty, it will be removed completely.
     */
    protected function migrateNullFlag(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (!GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'null')) {
                    continue;
                }

                $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);
                // Remove "null" from $evalList
                $evalList = array_filter($evalList, static function (string $eval) {
                    return $eval !== 'null';
                });
                if ($evalList !== []) {
                    // Write back filtered 'eval'
                    $tca[$table]['columns'][$fieldName]['config']['eval'] = implode(',', $evalList);
                } else {
                    // 'eval' is empty, remove whole configuration
                    unset($tca[$table]['columns'][$fieldName]['config']['eval']);
                }

                $tca[$table]['columns'][$fieldName]['config']['nullable'] = true;
                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . '"null" in its "eval" list. This is not evaluated anymore and should be replaced '
                    . ' by `\'nullable\' => true`.';
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

                // Unset "max"
                unset($tca[$table]['columns'][$fieldName]['config']['max']);

                $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);
                $evalList = array_filter($evalList, static function (string $eval) {
                    // Remove anything except "unique" and "uniqueInPid" from eval
                    return in_array($eval, ['unique', 'uniqueInPid'], true);
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
     * Removes option [config][eval].
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

                // Unset "renderType", "max" and "eval"
                unset(
                    $tca[$table]['columns'][$fieldName]['config']['max'],
                    $tca[$table]['columns'][$fieldName]['config']['renderType'],
                    $tca[$table]['columns'][$fieldName]['config']['eval'],
                );

                // Unset "softref" if set to "typolink"
                if (($fieldConfig['config']['softref'] ?? '') === 'typolink') {
                    unset($tca[$table]['columns'][$fieldName]['config']['softref']);
                }

                // Migrate the linkPopup configuration
                if (is_array($fieldConfig['config']['fieldControl']['linkPopup'] ?? false)) {
                    $linkPopupConfig = $fieldConfig['config']['fieldControl']['linkPopup'];
                    if ($linkPopupConfig['options']['blindLinkOptions'] ?? false) {
                        $availableTypes = $GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler'] ?? [];
                        if ($availableTypes !== []) {
                            $availableTypes = array_keys($availableTypes);
                        } else {
                            // Fallback to a static list, in case linkHandler configuration is not available at this point
                            $availableTypes = ['page', 'file', 'folder', 'url', 'email', 'record', 'telephone'];
                        }
                        $tca[$table]['columns'][$fieldName]['config']['allowedTypes'] = array_values(array_diff(
                            $availableTypes,
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

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'renderType="inputLink". The field has therefore been migrated to the TCA type \'link\'. '
                    . 'This includes corresponding configuration of the "linkPopup", as well as obsolete field '
                    . 'configurations, such as "max" and "softref". Please adjust your TCA accordingly.';
            }
        }

        return $tca;
    }

    /**
     * Migrates [config][eval] = 'password' and [config][eval] = 'saltedPassword' to [config][type] = 'password'
     * Sets option "hashed" to FALSE if "saltedPassword" is not set for "password"
     * Removes option [config][eval].
     * Removes option [config][max], if set.
     * Removes option [config][search], if set.
     */
    protected function migratePasswordAndSaltedPasswordToPasswordType(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'input'
                    || (!GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'password')
                        && !GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'saltedPassword'))
                ) {
                    // Early return in case column is not of type=input or does not define eval=passowrd
                    continue;
                }

                // Set the TCA type to "password"
                $tca[$table]['columns'][$fieldName]['config']['type'] = 'password';

                // Unset "max", "search" and "eval"
                unset(
                    $tca[$table]['columns'][$fieldName]['config']['max'],
                    $tca[$table]['columns'][$fieldName]['config']['search'],
                    $tca[$table]['columns'][$fieldName]['config']['eval'],
                );

                $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);

                // Disable password hashing, if eval=password is used standalone
                if (in_array('password', $evalList, true) && !in_array('saltedPassword', $evalList, true)) {
                    $tca[$table]['columns'][$fieldName]['config']['hashed'] = false;
                }

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . '"password" or "saltedPassword" in its "eval" list. The field has therefore been migrated to '
                    . 'the TCA type \'password\'. This also includes the removal of obsolete field configurations,'
                    . 'such as "max" and "search". Please adjust your TCA accordingly.';
            }
        }

        return $tca;
    }

    /**
     * Migrates [config][renderType] = 'inputDateTime' to [config][type] = 'datetime'.
     * Migrates "date", "time" and "timesec" from [config][eval] to [config][format].
     * Removes option [config][eval].
     * Removes option [config][max], if set.
     * Removes option [config][format], if set.
     * Removes option [config][default], if the default is the native "empty" value
     */
    protected function migrateRenderTypeInputDateTimeToTypeDatetime(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'input'
                    || ($fieldConfig['config']['renderType'] ?? '') !== 'inputDateTime'
                ) {
                    // Early return in case column is not of type=input with renderType=inputDateTime
                    continue;
                }

                // Set the TCA type to "datetime"
                $tca[$table]['columns'][$fieldName]['config']['type'] = 'datetime';

                // Unset "renderType", "max" and "eval"
                // Note: Also unset "format". This option had been documented but was actually
                //       never used in the FormEngine element. This migration will set it according
                //       to the corresponding "eval" value.
                unset(
                    $tca[$table]['columns'][$fieldName]['config']['max'],
                    $tca[$table]['columns'][$fieldName]['config']['renderType'],
                    $tca[$table]['columns'][$fieldName]['config']['format'],
                    $tca[$table]['columns'][$fieldName]['config']['eval'],
                );

                $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'] ?? '', true);

                // Set the "format" based on "eval". If set to "datetime",
                // no migration is done since this is the default format.
                if (in_array('date', $evalList, true)) {
                    $tca[$table]['columns'][$fieldName]['config']['format'] = 'date';
                } elseif (in_array('time', $evalList, true)) {
                    $tca[$table]['columns'][$fieldName]['config']['format'] = 'time';
                } elseif (in_array('timesec', $evalList, true)) {
                    $tca[$table]['columns'][$fieldName]['config']['format'] = 'timesec';
                }

                if (isset($fieldConfig['config']['default'])) {
                    if (in_array($fieldConfig['config']['dbType'] ?? '', QueryHelper::getDateTimeTypes(), true)) {
                        if ($fieldConfig['config']['default'] === QueryHelper::getDateTimeFormats()[$fieldConfig['config']['dbType']]['empty']) {
                            // Unset default for native datetime fields if the default is the native "empty" value
                            unset($tca[$table]['columns'][$fieldName]['config']['default']);
                        }
                    } elseif (!is_int($fieldConfig['config']['default'])) {
                        if ($fieldConfig['config']['default'] === '') {
                            // Always use int as default (string values are no longer supported for "datetime")
                            $tca[$table]['columns'][$fieldName]['config']['default'] = 0;
                        } elseif (MathUtility::canBeInterpretedAsInteger($fieldConfig['config']['default'])) {
                            // Cast default to int, in case it can be interpreted as integer
                            $tca[$table]['columns'][$fieldName]['config']['default'] = (int)$fieldConfig['config']['default'];
                        } else {
                            // Unset default in case it's a no longer supported string
                            unset($tca[$table]['columns'][$fieldName]['config']['default']);
                        }
                    }
                }

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'renderType="inputDateTime". The field has therefore been migrated to the TCA type \'datetime\'. '
                    . 'This includes corresponding migration of the "eval" list, as well as obsolete field '
                    . 'configurations, such as "max". Please adjust your TCA accordingly.';
            }
        }

        return $tca;
    }

    /**
     * Migrates [config][renderType] = 'colorpicker' to [config][type] = 'color'.
     * Removes [config][eval].
     * Removes option [config][max], if set.
     */
    protected function migrateRenderTypeColorpickerToTypeColor(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'input'
                    || ($fieldConfig['config']['renderType'] ?? '') !== 'colorpicker'
                ) {
                    // Early return in case column is not of type=input with renderType=colorpicker
                    continue;
                }

                // Set the TCA type to "color"
                $tca[$table]['columns'][$fieldName]['config']['type'] = 'color';

                // Unset "renderType", "max" and "eval"
                unset(
                    $tca[$table]['columns'][$fieldName]['config']['max'],
                    $tca[$table]['columns'][$fieldName]['config']['renderType'],
                    $tca[$table]['columns'][$fieldName]['config']['eval'],
                );

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'renderType="colorpicker". The field has therefore been migrated to the TCA type \'color\'. '
                    . 'This includes corresponding migration of the "eval" list, as well as obsolete field '
                    . 'configurations, such as "max". Please adjust your TCA accordingly.';
            }
        }

        return $tca;
    }

    /**
     * Remove ['columns'][aField]['config']['authMode_enforce']
     */
    protected function removeAuthModeEnforce(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (array_key_exists('authMode_enforce', $fieldConfig['config'] ?? [])) {
                    unset($tca[$table]['columns'][$fieldName]['config']['authMode_enforce']);
                    $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                    . '\'authMode_enforce\'. This config key is obsolete and has been removed.'
                    . ' Please adjust your TCA accordingly.';
                }
            }
        }
        return $tca;
    }

    /**
     * If a column has authMode=individual and items with the corresponding key on position 5
     * defined, or if EXPL_ALLOW or EXPL_DENY is set for position 6, migrate or remove them.
     */
    protected function removeSelectAuthModeIndividualItemsKeyword(array $tca): array
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
                        $tca[$table]['columns'][$fieldName]['config']['items'][$index][4] = '';
                        $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets ' . $item[4]
                            . ' at position 5 of the items array. This was used in combination with \'authMode=individual\' and'
                            . ' is obsolete since \'individual\' is no longer supported.';
                    }
                    if (isset($item[5])) {
                        unset($tca[$table]['columns'][$fieldName]['config']['items'][$index][5]);
                        $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets ' . $item[5]
                            . ' at position 6 of the items array. This was used in combination with \'authMode=individual\' and'
                            . ' is obsolete since \'individual\' is no longer supported.';
                    }
                }
            }
        }
        return $tca;
    }

    /**
     * See if ['columns'][aField]['config']['authMode'] is not set to 'explicitAllow' and
     * set it to this value if needed.
     */
    protected function migrateAuthMode(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (array_key_exists('authMode', $fieldConfig['config'] ?? [])
                    && $fieldConfig['config']['authMode'] !== 'explicitAllow'
                ) {
                    $tca[$table]['columns'][$fieldName]['config']['authMode'] = 'explicitAllow';
                    $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets '
                        . '\'authMode\' to \'' . $fieldConfig['config']['authMode'] . '\'. The only allowed value is \'explicitAllow\','
                        . ' and that value has been set now. Please adjust your TCA accordingly. Note this has impact on'
                        . ' backend group access rights, these should be reviewed and new access right for this field should'
                        . ' be set. An upgrade wizard partially migrates this and reports be_groups rows that need manual attention.';
                }
            }
        }
        return $tca;
    }

    /**
     * Migrates [config][eval] = 'int' and [config][eval] = 'double2' to [config][type] = 'number'.
     * The migration only applies to fields without a renderType defined.
     * Adds [config][format] = "decimal" if [config][eval] = double2
     * Removes [config][eval].
     * Removes option [config][max], if set.
     */
    protected function migrateEvalIntAndDouble2ToTypeNumber(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                // Return early, if not TCA type "input" or a renderType is set
                // or neither eval=int nor eval=double2 are set.
                if (
                    ($fieldConfig['config']['type'] ?? '') !== 'input'
                    || ($fieldConfig['config']['renderType'] ?? '') !== ''
                    || (
                        !GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'int')
                        && !GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'double2')
                    )
                ) {
                    continue;
                }

                // Set the TCA type to "number"
                $tca[$table]['columns'][$fieldName]['config']['type'] = 'number';

                // Unset "max" and "eval"
                unset(
                    $tca[$table]['columns'][$fieldName]['config']['max'],
                    $tca[$table]['columns'][$fieldName]['config']['eval'],
                );

                $numberType = '';
                $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);

                // Convert eval "double2" to format = "decimal" and store the "number type" for the deprecation log
                if (in_array('double2', $evalList, true)) {
                    $numberType = 'double2';
                    $tca[$table]['columns'][$fieldName]['config']['format'] = 'decimal';
                } elseif (in_array('int', $evalList, true)) {
                    $numberType = 'int';
                }

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' in table \'' . $table . '\'" defines '
                    . 'eval="' . $numberType . '". The field has therefore been migrated to the TCA type \'number\'. '
                    . 'This includes corresponding migration of the "eval" list, as well as obsolete field '
                    . 'configurations, such as "max". Please adjust your TCA accordingly.';
            }
        }
        return $tca;
    }

    /**
     * Removes ['interface']['always_description'] and also ['interface']
     * if `always_description` was the only key in the array.
     */
    protected function removeAlwaysDescription(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['interface']['always_description'])) {
                continue;
            }
            unset($tableDefinition['interface']['always_description']);
            if ($tableDefinition['interface'] === []) {
                unset($tableDefinition['interface']);
            }
            $this->messages[] = 'The TCA property [\'interface\'][\'always_description\'] of table \'' . $table
                . '\'  is not evaluated anymore and has therefore been removed. Please adjust your TCA accordingly.';
        }
        return $tca;
    }

    /**
     * Remove ['ctrl']['cruser_id'].
     */
    protected function removeCtrlCruserId(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['ctrl']['cruser_id'])) {
                continue;
            }
            unset($tableDefinition['ctrl']['cruser_id']);
            $this->messages[] = 'The TCA property [\'ctrl\'][\'cruser_id\'] of table \'' . $table
                . '\'  is not evaluated anymore and has therefore been removed. Please adjust your TCA accordingly.';
        }
        return $tca;
    }

    /**
     * Migrates type='inline' with foreign_table='sys_file_reference' to type='file'.
     * Removes table relation related options.
     * Removes no longer available appearance options.
     * Detects usage of "customControls" hook.
     * Migrates renamed appearance options.
     * Migrates allowed file extensions.
     */
    protected function migrateFalHandlingInInlineToTypeFile(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'inline'
                    || ($fieldConfig['config']['foreign_table'] ?? '') !== 'sys_file_reference'
                ) {
                    // Early return in case column is not of type=inline with foreign_table=sys_file_reference
                    continue;
                }

                // Place to add additional information, which will later be appended to the deprecation message
                $additionalInformation = '';

                // Set the TCA type to "file"
                $fieldConfig['config']['type'] = 'file';

                // Remove table relation related options, since they are
                // either not needed anymore or set by TcaPreperation automatically.
                unset(
                    $fieldConfig['config']['foreign_table'],
                    $fieldConfig['config']['foreign_field'],
                    $fieldConfig['config']['foreign_sortby'],
                    $fieldConfig['config']['foreign_table_field'],
                    $fieldConfig['config']['foreign_match_fields'],
                    $fieldConfig['config']['foreign_label'],
                    $fieldConfig['config']['foreign_selector'],
                    $fieldConfig['config']['foreign_unique'],
                );

                // "new" control is not supported for this type so remove it altogether for cleaner TCA
                unset($fieldConfig['config']['appearance']['enabledControls']['new']);

                // [appearance][headerThumbnail][field] is not needed anymore
                unset($fieldConfig['config']['appearance']['headerThumbnail']['field']);

                // A couple of further appearance options are not supported by type "file", unset them as well
                unset(
                    $fieldConfig['config']['appearance']['showNewRecordLink'],
                    $fieldConfig['config']['appearance']['newRecordLinkAddTitle'],
                    $fieldConfig['config']['appearance']['newRecordLinkTitle'],
                    $fieldConfig['config']['appearance']['levelLinksPosition'],
                    $fieldConfig['config']['appearance']['useCombination'],
                    $fieldConfig['config']['appearance']['suppressCombinationWarning']
                );

                // Migrate [appearance][showPossibleRecordsSelector] to [appearance][showFileSelectors]
                if (isset($fieldConfig['config']['appearance']['showPossibleRecordsSelector'])) {
                    $fieldConfig['config']['appearance']['showFileSelectors'] = $fieldConfig['config']['appearance']['showPossibleRecordsSelector'];
                    unset($fieldConfig['config']['appearance']['showPossibleRecordsSelector']);
                }

                // "customControls" hook has been replaced by the CustomFileControlsEvent
                if (isset($fieldConfig['config']['customControls'])) {
                    $additionalInformation .= ' The \'customControls\' option is not evaluated anymore and has '
                        . 'to be replaced with the PSR-14 \'CustomFileControlsEvent\'.';
                    unset($fieldConfig['config']['customControls']);
                }

                // Migrate element browser related settings
                if (!empty($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance'])) {
                    if (!empty($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserAllowed'])) {
                        // Migrate "allowed" file extensions from appearance
                        $fieldConfig['config']['allowed'] = $fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserAllowed'];
                    }
                    unset(
                        $fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserType'],
                        $fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserAllowed']
                    );
                    if (empty($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance'])) {
                        unset($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']['appearance']);
                        if (empty($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config'])) {
                            unset($fieldConfig['config']['overrideChildTca']['columns']['uid_local']['config']);
                            if (empty($fieldConfig['config']['overrideChildTca']['columns']['uid_local'])) {
                                unset($fieldConfig['config']['overrideChildTca']['columns']['uid_local']);
                                if (empty($fieldConfig['config']['overrideChildTca']['columns'])) {
                                    unset($fieldConfig['config']['overrideChildTca']['columns']);
                                    if (empty($fieldConfig['config']['overrideChildTca'])) {
                                        unset($fieldConfig['config']['overrideChildTca']);
                                    }
                                }
                            }
                        }
                    }
                }

                // Migrate file extension filter
                if (!empty($fieldConfig['config']['filter'])) {
                    foreach ($fieldConfig['config']['filter'] as $key => $filter) {
                        if (($filter['userFunc'] ?? '') === (FileExtensionFilter::class . '->filterInlineChildren')) {
                            $allowedFileExtensions = (string)($filter['parameters']['allowedFileExtensions'] ?? '');
                            // Note: Allowed file extensions in the filter take precedence over possible
                            // extensions defined for the element browser. This is due to filters are evaluated
                            // by the DataHandler while element browser is only applied in FormEngine UI.
                            if ($allowedFileExtensions !== '') {
                                $fieldConfig['config']['allowed'] = $allowedFileExtensions;
                            }
                            $disallowedFileExtensions = (string)($filter['parameters']['disallowedFileExtensions'] ?? '');
                            if ($disallowedFileExtensions !== '') {
                                $fieldConfig['config']['disallowed'] = $disallowedFileExtensions;
                            }
                            unset($fieldConfig['config']['filter'][$key]);
                        }
                    }
                    // Remove filter if it got empty
                    if (empty($fieldConfig['config']['filter'])) {
                        unset($fieldConfig['config']['filter']);
                    }
                }

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'type="inline" with foreign_table=sys_file_reference. The field has therefore been '
                    . 'migrated to the dedicated TCA type \'file\'. This includes corresponding migration of '
                    . 'the table mapping fields and filters, which were usually added to the field using the '
                    . 'ExtensionManagementUtility::getFileFieldTCAConfig().' . $additionalInformation . ' '
                    . 'Please adjust your TCA accordingly.';
            }
        }

        return $tca;
    }

    /**
     * Removes the [appearance][elementBrowserType] and [appearance][elementBrowserAllowed]
     * options from TCA type "group" fields.
     */
    protected function removeFalRelatedElementBrowserOptions(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'group'
                    || (
                        !isset($fieldConfig['config']['appearance']['elementBrowserType'])
                        && !isset($fieldConfig['config']['appearance']['elementBrowserAllowed'])
                    )
                ) {
                    // Early return in case column is not of type=group or does not define the options in question
                    continue;
                }

                unset(
                    $fieldConfig['config']['appearance']['elementBrowserType'],
                    $fieldConfig['config']['appearance']['elementBrowserAllowed']
                );

                // Also unset "appearance" if empty
                if (empty($fieldConfig['config']['appearance'])) {
                    unset($fieldConfig['config']['appearance']);
                }

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'fal related element browser options, which are no longer needed and therefore removed. '
                    . 'Please adjust your TCA accordingly.';
            }
        }

        return $tca;
    }

    /**
     * Removes the following options from TCA type "inline" fields:
     * - [appearance][headerThumbnail]
     * - [appearance][fileUploadAllowed]
     * - [appearance][fileByUrlAllowed]
     */
    protected function removeFalRelatedOptionsFromTypeInline(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'inline'
                    || (
                        !isset($fieldConfig['config']['appearance']['headerThumbnail'])
                        && !isset($fieldConfig['config']['appearance']['fileUploadAllowed'])
                        && !isset($fieldConfig['config']['appearance']['fileByUrlAllowed'])
                    )
                ) {
                    // Early return in case column is not of type=inline or does not define the options in question
                    continue;
                }

                unset(
                    $fieldConfig['config']['appearance']['headerThumbnail'],
                    $fieldConfig['config']['appearance']['fileUploadAllowed'],
                    $fieldConfig['config']['appearance']['fileByUrlAllowed']
                );

                // Also unset "appearance" if empty
                if (empty($fieldConfig['config']['appearance'])) {
                    unset($fieldConfig['config']['appearance']);
                }

                $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'fal related appearance options, which are no longer evaluated and therefore removed. '
                    . 'Please adjust your TCA accordingly.';
            }
        }

        return $tca;
    }

    /**
     * Removes ['config']['pass_content'] from TCA type "none" fields
     */
    protected function removePassContentFromTypeNone(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') === 'none'
                    && array_key_exists('pass_content', $fieldConfig['config'] ?? [])
                ) {
                    unset($tca[$table]['columns'][$fieldName]['config']['pass_content']);
                    $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                        . '\'pass_content\'. This config key is obsolete and has been removed. '
                        . 'Please adjust your TCA accordingly.';
                }
            }
        }
        return $tca;
    }

    /**
     * Converts the item list of type "select", "radio" and "check" to an associated array.
     *
     * // From:
     * [
     *     0 => 'A label',
     *     1 => 'value',
     *     2 => 'icon-identifier',
     *     3 => 'group1',
     *     4 => 'a custom description'
     * ]
     *
     * // To:
     * [
     *     'label' => 'A label',
     *     'value' => 'value',
     *     'icon' => 'icon-identifier',
     *     'group' => 'group1',
     *     'description' => 'a custom description'
     * ]
     */
    protected function migrateItemsToAssociativeArray(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (
                    array_key_exists('items', $fieldConfig['config'] ?? [])
                    && in_array(($fieldConfig['config']['type'] ?? ''), ['select', 'radio', 'check'], true)
                ) {
                    $hasLegacyItemConfiguration = false;
                    $items = $fieldConfig['config']['items'];
                    foreach ($items as $key => $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        if (array_key_exists(0, $item)) {
                            $hasLegacyItemConfiguration = true;
                            $items[$key]['label'] = $item[0];
                            unset($items[$key][0]);
                        }
                        if (($fieldConfig['config']['type'] !== 'check') && array_key_exists(1, $item)) {
                            $hasLegacyItemConfiguration = true;
                            $items[$key]['value'] = $item[1];
                            unset($items[$key][1]);
                        }
                        if ($fieldConfig['config']['type'] === 'select') {
                            if (array_key_exists(2, $item)) {
                                $hasLegacyItemConfiguration = true;
                                $items[$key]['icon'] = $item[2];
                                unset($items[$key][2]);
                            }
                            if (array_key_exists(3, $item)) {
                                $hasLegacyItemConfiguration = true;
                                $items[$key]['group'] = $item[3];
                                unset($items[$key][3]);
                            }
                            if (array_key_exists(4, $item)) {
                                $hasLegacyItemConfiguration = true;
                                $items[$key]['description'] = $item[4];
                                unset($items[$key][4]);
                            }
                        }
                    }
                    if ($hasLegacyItemConfiguration) {
                        $tca[$table]['columns'][$fieldName]['config']['items'] = $items;
                        $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                            . 'the legacy way of defining \'items\'. Please switch to associated array keys: '
                            . 'label, value, icon, group, description.';
                    }
                }
            }
        }
        return $tca;
    }

    protected function removeMmInsertFields(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['config']['MM_insert_fields'])) {
                    // @deprecated since v12.
                    //             *Enable* the commented unset line in v13 when removing MM_insert_fields deprecations.
                    //             *Enable* the disabled unit test set.
                    // unset($tca[$table]['columns'][$fieldName]['config']['MM_insert_fields']);
                    $this->messages[] = 'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                        . '\'MM_insert_fields\'. This config key is obsolete and should be removed. '
                        . 'Please adjust your TCA accordingly.';
                }
            }
        }
        return $tca;
    }
}
