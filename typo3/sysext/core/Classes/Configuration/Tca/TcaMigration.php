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

namespace TYPO3\CMS\Core\Configuration\Tca;

use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Migrate TCA from old to new syntax.
 * Used in bootstrap and Flex Form Data Structures.
 * This is to *migrate* from "old" to "new" TCA syntax,
 * all methods must add a deprecation message if they
 * change something.
 *
 * @internal Class and API may change any time.
 */
class TcaMigration
{
    /**
     * Run some general TCA validations, then migrate old TCA to new TCA.
     *
     * This class is typically called within bootstrap with empty caches after all TCA
     * files from extensions have been loaded. The migration is then applied and
     * the migrated result is cached.
     * For flex form TCA, this class is called dynamically if opening a record in the backend.
     *
     * See unit tests for details.
     */
    public function migrate(array $tca): TcaProcessingResult
    {
        $this->validateTcaType($tca);

        $tcaProcessingResult = new TcaProcessingResult($tca);

        $tcaProcessingResult = $this->migrateColumnsConfig($tcaProcessingResult);
        $tcaProcessingResult = $this->migratePagesLanguageOverlayRemoval($tcaProcessingResult);
        $tcaProcessingResult = $this->removeSelIconFieldPath($tcaProcessingResult);
        $tcaProcessingResult = $this->removeSetToDefaultOnCopy($tcaProcessingResult);
        $tcaProcessingResult = $this->removeEnableMultiSelectFilterTextfieldConfiguration($tcaProcessingResult);
        $tcaProcessingResult = $this->removeExcludeFieldForTransOrigPointerField($tcaProcessingResult);
        $tcaProcessingResult = $this->removeShowRecordFieldListField($tcaProcessingResult);
        $tcaProcessingResult = $this->removeMaxDBListItems($tcaProcessingResult);
        $tcaProcessingResult = $this->removeWorkspacePlaceholderShadowColumnsConfiguration($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateLanguageFieldToTcaTypeLanguage($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateSpecialLanguagesToTcaTypeLanguage($tcaProcessingResult);
        $tcaProcessingResult = $this->removeShowRemovedLocalizationRecords($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateFileFolderConfiguration($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateLevelLinksPosition($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateRootUidToStartingPoints($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateInternalTypeFolderToTypeFolder($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateRequiredFlag($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateNullFlag($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateEmailFlagToEmailType($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateTypeNoneColsToSize($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateRenderTypeInputLinkToTypeLink($tcaProcessingResult);
        $tcaProcessingResult = $this->migratePasswordAndSaltedPasswordToPasswordType($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateRenderTypeInputDateTimeToTypeDatetime($tcaProcessingResult);
        $tcaProcessingResult = $this->removeAuthModeEnforce($tcaProcessingResult);
        $tcaProcessingResult = $this->removeSelectAuthModeIndividualItemsKeyword($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateAuthMode($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateRenderTypeColorpickerToTypeColor($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateEvalIntAndDouble2ToTypeNumber($tcaProcessingResult);
        $tcaProcessingResult = $this->removeAlwaysDescription($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateFalHandlingInInlineToTypeFile($tcaProcessingResult);
        $tcaProcessingResult = $this->removeCtrlCruserId($tcaProcessingResult);
        $tcaProcessingResult = $this->removeFalRelatedElementBrowserOptions($tcaProcessingResult);
        $tcaProcessingResult = $this->removeFalRelatedOptionsFromTypeInline($tcaProcessingResult);
        $tcaProcessingResult = $this->removePassContentFromTypeNone($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateItemsToAssociativeArray($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateItemsOfValuePickerToAssociativeArray($tcaProcessingResult);
        $tcaProcessingResult = $this->removeMmInsertFields($tcaProcessingResult);
        $tcaProcessingResult = $this->removeMmHasUidField($tcaProcessingResult);
        $tcaProcessingResult = $this->migrateT3EditorToCodeEditor($tcaProcessingResult);
        $tcaProcessingResult = $this->removeAllowLanguageSynchronizationFromColumnsOverrides($tcaProcessingResult);
        $tcaProcessingResult = $this->removeSubTypesConfiguration($tcaProcessingResult);
        $tcaProcessingResult = $this->addWorkspaceAwarenessToInlineChildren($tcaProcessingResult);
        $tcaProcessingResult = $this->removeEvalYearFlag($tcaProcessingResult);
        $tcaProcessingResult = $this->removeIsStaticControlOption($tcaProcessingResult);
        $tcaProcessingResult = $this->removeFieldSearchConfigOptions($tcaProcessingResult);
        $tcaProcessingResult = $this->removeSearchFieldsControlOption($tcaProcessingResult);

        return $tcaProcessingResult;
    }

    /**
     * Check for required TCA configuration
     */
    protected function validateTcaType(array $tca): void
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
     */
    protected function migrateColumnsConfig(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if ((!isset($fieldConfig['config']) || !is_array($fieldConfig['config'])) && !isset($fieldConfig['type'])) {
                    $fieldConfig['config'] = [
                        'type' => 'none',
                    ];
                    $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('TCA table "' . $table . '" columns field "' . $fieldName . '"'
                        . ' had no mandatory "config" section. This has been added with default type "none":'
                        . ' TCA "' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'type\'] = \'none\'"');
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes $TCA['pages_language_overlay'] if defined.
     */
    protected function migratePagesLanguageOverlayRemoval(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        if (isset($tca['pages_language_overlay'])) {
            $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA table \'pages_language_overlay\' is'
                . ' not used anymore and has been removed automatically in'
                . ' order to avoid negative side-effects.');
            unset($tca['pages_language_overlay']);
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes configuration removeEnableMultiSelectFilterTextfield
     */
    protected function removeEnableMultiSelectFilterTextfieldConfiguration(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (!isset($fieldConfig['config']['enableMultiSelectFilterTextfield'])) {
                    continue;
                }

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA setting \'enableMultiSelectFilterTextfield\' is deprecated '
                    . ' and should be removed from TCA for ' . $table . '[\'columns\']'
                    . '[\'' . $fieldName . '\'][\'config\'][\'enableMultiSelectFilterTextfield\']');
                unset($fieldConfig['config']['enableMultiSelectFilterTextfield']);
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes $TCA[$mytable][ctrl][selicon_field_path]
     */
    protected function removeSelIconFieldPath(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['ctrl']['selicon_field_path'])) {
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA table \'' . $table . '\' defines '
                    . '[ctrl][selicon_field_path] which should be removed from TCA, '
                    . 'as it is not in use anymore.');
                unset($configuration['ctrl']['selicon_field_path']);
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes $TCA[$mytable][ctrl][setToDefaultOnCopy]
     */
    protected function removeSetToDefaultOnCopy(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['ctrl']['setToDefaultOnCopy'])) {
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA table \'' . $table . '\' defines '
                    . '[ctrl][setToDefaultOnCopy] which should be removed from TCA, '
                    . 'as it is not in use anymore.');
                unset($configuration['ctrl']['setToDefaultOnCopy']);
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes $TCA[$mytable][columns][_transOrigPointerField_][exclude] if defined
     */
    protected function removeExcludeFieldForTransOrigPointerField(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['ctrl']['transOrigPointerField'],
                $configuration['columns'][$configuration['ctrl']['transOrigPointerField']]['exclude'])
            ) {
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The \'' . $table . '\' TCA tables transOrigPointerField '
                    . '\'' . $configuration['ctrl']['transOrigPointerField'] . '\' is defined '
                    . ' as excluded field which is no longer needed and should therefore be removed.');
                unset($configuration['columns'][$configuration['ctrl']['transOrigPointerField']]['exclude']);
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes $TCA[$mytable]['interface']['showRecordFieldList'] and also $TCA[$mytable]['interface']
     * if `showRecordFieldList` was the only key in the array.
     */
    protected function removeShowRecordFieldListField(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$configuration) {
            if (!isset($configuration['interface']['showRecordFieldList'])) {
                continue;
            }
            $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The \'' . $table . '\' TCA configuration \'showRecordFieldList\''
                . ' inside the section \'interface\' is not evaluated anymore and should therefore be removed.');
            unset($configuration['interface']['showRecordFieldList']);
            if ($configuration['interface'] === []) {
                unset($configuration['interface']);
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes $TCA[$mytable]['interface']['maxDBListItems'], and 'maxSingleDBListItems' and also $TCA[$mytable]['interface']
     * if `interface` is empty later-on.
     */
    protected function removeMaxDBListItems(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['interface']['maxDBListItems'])) {
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The \'' . $table . '\' TCA configuration \'maxDBListItems\''
                    . ' inside the section \'interface\' is not evaluated anymore and should therefore be removed.');
                unset($configuration['interface']['maxDBListItems']);
            }
            if (isset($configuration['interface']['maxSingleDBListItems'])) {
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The \'' . $table . '\' TCA configuration \'maxSingleDBListItems\''
                    . ' inside the section \'interface\' is not evaluated anymore and should therefore be removed.');
                unset($configuration['interface']['maxSingleDBListItems']);
            }
            if (isset($configuration['interface']) && $configuration['interface'] === []) {
                unset($configuration['interface']);
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes $TCA[$mytable][ctrl][shadowColumnsForMovePlaceholders]
     * and $TCA[$mytable][ctrl][shadowColumnsForNewPlaceholders]
     */
    protected function removeWorkspacePlaceholderShadowColumnsConfiguration(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['ctrl']['shadowColumnsForNewPlaceholders'])) {
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA table \'' . $table . '\' defines '
                    . '[ctrl][shadowColumnsForNewPlaceholders] which should be removed from TCA, '
                    . 'as it is not in use anymore.');
                unset($configuration['ctrl']['shadowColumnsForNewPlaceholders']);
            }
            if (isset($configuration['ctrl']['shadowColumnsForMovePlaceholders'])) {
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA table \'' . $table . '\' defines '
                    . '[ctrl][shadowColumnsForMovePlaceholders] which should be removed from TCA, '
                    . 'as it is not in use anymore.');
                unset($configuration['ctrl']['shadowColumnsForMovePlaceholders']);
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Replaces $TCA[$mytable][columns][$TCA[$mytable][ctrl][languageField]][config] with
     * $TCA[$mytable][columns][$TCA[$mytable][ctrl][languageField]][config][type] = 'language'
     */
    protected function migrateLanguageFieldToTcaTypeLanguage(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$configuration) {
            if (isset($configuration['ctrl']['languageField'], $configuration['columns'][$configuration['ctrl']['languageField']])
                && ($configuration['columns'][$configuration['ctrl']['languageField']]['config']['type'] ?? '') !== 'language'
            ) {
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $configuration['ctrl']['languageField'] . '\' '
                    . 'of table \'' . $table . '\' is defined as the \'languageField\' and should '
                    . 'therefore use the TCA type \'language\' instead of TCA type \'select\' with '
                    . '\'foreign_table=sys_language\' or \'special=languages\'.');
                $configuration['columns'][$configuration['ctrl']['languageField']]['config'] = [
                    'type' => 'language',
                ];
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Replaces $TCA[$mytable][columns][field][config][special] = 'languages' with
     * $TCA[$mytable][columns][field][config][type] = 'language'
     */
    protected function migrateSpecialLanguagesToTcaTypeLanguage(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' is '
                    . 'defined as type \'select\' with the \'special=languages\' option. This is not '
                    . 'evaluated anymore and should be replaced by the TCA type \'language\'.');
                $fieldConfig['config'] = [
                    'type' => 'language',
                ];
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    protected function removeShowRemovedLocalizationRecords(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' is '
                    . 'defined as type \'inline\' with the \'appearance.showRemovedLocalizationRecords\' option set. '
                    . 'As this option is not evaluated anymore and no replacement exists, it should be removed from TCA.');
                unset($fieldConfig['config']['appearance']['showRemovedLocalizationRecords']);
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Moves the "fileFolder" configuration of TCA columns type=select
     * into sub array "fileFolderConfig", while renaming those options.
     */
    protected function migrateFileFolderConfiguration(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' is '
                    . 'defined as type \'select\' with the \'fileFolder\' configuration option set. To streamline '
                    . 'the configuration, all \'fileFolder\' related configuration options were moved into a '
                    . 'dedicated sub array \'fileFolderConfig\', while \'fileFolder\' is now just \'folder\' and '
                    . 'the other options have been renamed to \'allowedExtensions\' and \'depth\'. '
                    . 'The TCA configuration should be adjusted accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
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
    protected function migrateLevelLinksPosition(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets '
                    . '[appearance][levelLinksPosition] to "none", while only "top", "bottom" and "both" are supported. '
                    . 'The TCA configuration should be adjusted accordingly. In case you want to disable all level links, '
                    . 'use the corresponding level link specific options, e.g. [appearance][showNewRecordLink], instead.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * If a column has [treeConfig][rootUid] defined, migrate to [treeConfig][startingPoints] on the same level.
     */
    protected function migrateRootUidToStartingPoints(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets '
                    . '[treeConfig][rootUid], which is superseded by [treeConfig][startingPoints].'
                    . 'The TCA configuration should be adjusted accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates [config][internal_type] = 'folder' to [config][type] = 'folder'.
     * Also removes [config][internal_type] completely, if present.
     */
    protected function migrateInternalTypeFolderToTypeFolder(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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
                    $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' has been migrated to '
                        . 'the TCA type \'folder\'. Please adjust your TCA accordingly.');
                } else {
                    $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The property \'internal_type\' of the TCA field \'' . $fieldName . '\' of table \''
                        . $table . '\' is obsolete and has been removed. You can remove it from your TCA as it is not evaluated anymore.');
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates [config][eval] = 'required' to [config][required] = true and removes 'required' from [config][eval].
     * If [config][eval] becomes empty, it will be removed completely.
     */
    protected function migrateRequiredFlag(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . '"required" in its "eval" list. This is not evaluated anymore and should be replaced '
                    . ' by `\'required\' => true`.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates [config][eval] = 'null' to [config][nullable] = true and removes 'null' from [config][eval].
     * If [config][eval] becomes empty, it will be removed completely.
     */
    protected function migrateNullFlag(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . '"null" in its "eval" list. This is not evaluated anymore and should be replaced '
                    . ' by `\'nullable\' => true`.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates [config][eval] = 'email' to [config][type] = 'email' and removes 'email' from [config][eval].
     * If [config][eval] contains 'trim', it will also be removed. If [config][eval] becomes empty, the option
     * will be removed completely.
     */
    protected function migrateEmailFlagToEmailType(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . '"email" in its "eval" list. The field has therefore been migrated to the TCA type \'email\'. '
                    . 'Please adjust your TCA accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates type => "none" [config][cols] to [config][size] and removes "cols".
     */
    protected function migrateTypeNoneColsToSize(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . '"cols" in its config. This value has been migrated to the option "size". Please adjust your TCA accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates [config][renderType] = 'inputLink' to [config][type] = 'link'.
     * Migrates the [config][fieldConfig][linkPopup] to type specific configuration.
     * Removes option [config][eval].
     * Removes option [config][max], if set.
     * Removes option [config][softref], if set to "typolink".
     */
    protected function migrateRenderTypeInputLinkToTypeLink(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'renderType="inputLink". The field has therefore been migrated to the TCA type \'link\'. '
                    . 'This includes corresponding configuration of the "linkPopup", as well as obsolete field '
                    . 'configurations, such as "max" and "softref". Please adjust your TCA accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates [config][eval] = 'password' and [config][eval] = 'saltedPassword' to [config][type] = 'password'
     * Sets option "hashed" to FALSE if "saltedPassword" is not set for "password"
     * Removes option [config][eval].
     * Removes option [config][max], if set.
     * Removes option [config][search], if set.
     */
    protected function migratePasswordAndSaltedPasswordToPasswordType(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . '"password" or "saltedPassword" in its "eval" list. The field has therefore been migrated to '
                    . 'the TCA type \'password\'. This also includes the removal of obsolete field configurations,'
                    . 'such as "max" and "search". Please adjust your TCA accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates [config][renderType] = 'inputDateTime' to [config][type] = 'datetime'.
     * Migrates "date", "time" and "timesec" from [config][eval] to [config][format].
     * Removes option [config][eval].
     * Removes option [config][max], if set.
     * Removes option [config][format], if set.
     * Removes option [config][default], if the default is the native "empty" value
     */
    protected function migrateRenderTypeInputDateTimeToTypeDatetime(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'renderType="inputDateTime". The field has therefore been migrated to the TCA type \'datetime\'. '
                    . 'This includes corresponding migration of the "eval" list, as well as obsolete field '
                    . 'configurations, such as "max". Please adjust your TCA accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates [config][renderType] = 'colorpicker' to [config][type] = 'color'.
     * Removes [config][eval].
     * Removes option [config][max], if set.
     */
    protected function migrateRenderTypeColorpickerToTypeColor(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'renderType="colorpicker". The field has therefore been migrated to the TCA type \'color\'. '
                    . 'This includes corresponding migration of the "eval" list, as well as obsolete field '
                    . 'configurations, such as "max". Please adjust your TCA accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Remove ['columns'][aField]['config']['authMode_enforce']
     */
    protected function removeAuthModeEnforce(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (array_key_exists('authMode_enforce', $fieldConfig['config'] ?? [])) {
                    unset($tca[$table]['columns'][$fieldName]['config']['authMode_enforce']);
                    $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                    . '\'authMode_enforce\'. This config key is obsolete and has been removed.'
                    . ' Please adjust your TCA accordingly.');
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * If a column has authMode=individual and items with the corresponding key on position 5
     * defined, or if EXPL_ALLOW or EXPL_DENY is set for position 6, migrate or remove them.
     */
    protected function removeSelectAuthModeIndividualItemsKeyword(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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
                        $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets ' . $item[4]
                            . ' at position 5 of the items array. This was used in combination with \'authMode=individual\' and'
                            . ' is obsolete since \'individual\' is no longer supported.');
                    }
                    if (isset($item[5])) {
                        unset($tca[$table]['columns'][$fieldName]['config']['items'][$index][5]);
                        $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets ' . $item[5]
                            . ' at position 6 of the items array. This was used in combination with \'authMode=individual\' and'
                            . ' is obsolete since \'individual\' is no longer supported.');
                    }
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * See if ['columns'][aField]['config']['authMode'] is not set to 'explicitAllow' and
     * set it to this value if needed.
     */
    protected function migrateAuthMode(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (array_key_exists('authMode', $fieldConfig['config'] ?? [])
                    && $fieldConfig['config']['authMode'] !== 'explicitAllow'
                ) {
                    $tca[$table]['columns'][$fieldName]['config']['authMode'] = 'explicitAllow';
                    $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' sets '
                        . '\'authMode\' to \'' . $fieldConfig['config']['authMode'] . '\'. The only allowed value is \'explicitAllow\','
                        . ' and that value has been set now. Please adjust your TCA accordingly. Note this has impact on'
                        . ' backend group access rights, these should be reviewed and new access right for this field should'
                        . ' be set. An upgrade wizard partially migrates this and reports be_groups rows that need manual attention.');
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates [config][eval] = 'int' and [config][eval] = 'double2' to [config][type] = 'number'.
     * The migration only applies to fields without a renderType defined.
     * Adds [config][format] = "decimal" if [config][eval] = double2
     * Removes [config][eval].
     * Removes option [config][max], if set.
     */
    protected function migrateEvalIntAndDouble2ToTypeNumber(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' in table \'' . $table . '\'" defines '
                    . 'eval="' . $numberType . '". The field has therefore been migrated to the TCA type \'number\'. '
                    . 'This includes corresponding migration of the "eval" list, as well as obsolete field '
                    . 'configurations, such as "max". Please adjust your TCA accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes ['interface']['always_description'] and also ['interface']
     * if `always_description` was the only key in the array.
     */
    protected function removeAlwaysDescription(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['interface']['always_description'])) {
                continue;
            }
            unset($tableDefinition['interface']['always_description']);
            if ($tableDefinition['interface'] === []) {
                unset($tableDefinition['interface']);
            }
            $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA property [\'interface\'][\'always_description\'] of table \'' . $table
                . '\'  is not evaluated anymore and has therefore been removed. Please adjust your TCA accordingly.');
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Remove ['ctrl']['cruser_id'].
     */
    protected function removeCtrlCruserId(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['ctrl']['cruser_id'])) {
                continue;
            }
            unset($tableDefinition['ctrl']['cruser_id']);
            $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA property [\'ctrl\'][\'cruser_id\'] of table \'' . $table
                . '\'  is not evaluated anymore and has therefore been removed. Please adjust your TCA accordingly.');
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Migrates type='inline' with foreign_table='sys_file_reference' to type='file'.
     * Removes table relation related options.
     * Removes no longer available appearance options.
     * Detects usage of "customControls" hook.
     * Migrates renamed appearance options.
     * Migrates allowed file extensions.
     */
    protected function migrateFalHandlingInInlineToTypeFile(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'type="inline" with foreign_table=sys_file_reference. The field has therefore been '
                    . 'migrated to the dedicated TCA type \'file\'' . $additionalInformation . ' '
                    . 'Please adjust your TCA accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes the [appearance][elementBrowserType] and [appearance][elementBrowserAllowed]
     * options from TCA type "group" fields.
     */
    protected function removeFalRelatedElementBrowserOptions(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'fal related element browser options, which are no longer needed and therefore removed. '
                    . 'Please adjust your TCA accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes the following options from TCA type "inline" fields:
     * - [appearance][headerThumbnail]
     * - [appearance][fileUploadAllowed]
     * - [appearance][fileByUrlAllowed]
     */
    protected function removeFalRelatedOptionsFromTypeInline(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines '
                    . 'fal related appearance options, which are no longer evaluated and therefore removed. '
                    . 'Please adjust your TCA accordingly.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes ['config']['pass_content'] from TCA type "none" fields
     */
    protected function removePassContentFromTypeNone(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') === 'none'
                    && array_key_exists('pass_content', $fieldConfig['config'] ?? [])
                ) {
                    unset($tca[$table]['columns'][$fieldName]['config']['pass_content']);
                    $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                        . '\'pass_content\'. This config key is obsolete and has been removed. '
                        . 'Please adjust your TCA accordingly.');
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
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
    protected function migrateItemsToAssociativeArray(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
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
                        $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                            . 'the legacy way of defining \'items\'. Please switch to associated array keys: '
                            . 'label, value, icon, group, description.');
                    }
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Converts the item list of valuePicker to an associated array.
     *
     * // From:
     * [
     *     0 => 'A label',
     *     1 => 'value',
     * ]
     *
     * // To:
     * [
     *     'label' => 'A label',
     *     'value' => 'value',
     * ]
     */
    protected function migrateItemsOfValuePickerToAssociativeArray(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (is_array($fieldConfig['config']['valuePicker']['items'] ?? false)) {
                    $hasLegacyItemConfiguration = false;
                    $items = $fieldConfig['config']['valuePicker']['items'];
                    foreach ($items as $key => $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        if (array_key_exists(0, $item)) {
                            $hasLegacyItemConfiguration = true;
                            $items[$key]['label'] = $item[0];
                            unset($items[$key][0]);
                        }
                        if (array_key_exists(1, $item)) {
                            $hasLegacyItemConfiguration = true;
                            $items[$key]['value'] = $item[1];
                            unset($items[$key][1]);
                        }
                    }
                    if ($hasLegacyItemConfiguration) {
                        $tca[$table]['columns'][$fieldName]['config']['valuePicker']['items'] = $items;
                        $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                            . 'the legacy way of defining \'items\' for the \'valuePicker\'. Please switch to associated array keys: '
                            . 'label, value.');
                    }
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    protected function removeMmInsertFields(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['config']['MM_insert_fields'])) {
                    unset($tca[$table]['columns'][$fieldName]['config']['MM_insert_fields']);
                    $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                        . '\'MM_insert_fields\'. This config key is obsolete and should be removed. '
                        . 'Please adjust your TCA accordingly.');
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    protected function removeMmHasUidField(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => $tableDefinition) {
            if (!is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['config']['MM_hasUidField'])) {
                    unset($tca[$table]['columns'][$fieldName]['config']['MM_hasUidField']);
                    $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                        . '\'MM_hasUidField\'. This config key is obsolete and should be removed. '
                        . 'Please adjust your TCA accordingly.');
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    protected function migrateT3EditorToCodeEditor(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => $tableDefinition) {
            if (!is_array($tableDefinition['columns'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (($fieldConfig['config']['renderType'] ?? '') === 't3editor') {
                    $tca[$table]['columns'][$fieldName]['config']['renderType'] = 'codeEditor';
                    $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' uses '
                        . '\'renderType\' with the value \'t3editor\', which has been migrated to \'codeEditor\'. '
                        . 'Please adjust your TCA accordingly.');
                }
            }

            foreach ($tableDefinition['types'] ?? [] as $typeName => $typeConfig) {
                foreach ($typeConfig['columnsOverrides'] ?? [] as $columnOverride => $columnOverrideConfig) {
                    if (($columnOverrideConfig['config']['renderType'] ?? '') === 't3editor') {
                        $tca[$table]['types'][$typeName]['columnsOverrides'][$columnOverride]['config']['renderType'] = 'codeEditor';
                        $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA column override \'' . $columnOverride . '\' of table \'' . $table . '\' uses '
                            . '\'renderType\' with the value \'t3editor\', which has been migrated to \'codeEditor\'. '
                            . 'Please adjust your TCA accordingly.');
                    }
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Setting "allowLanguageSynchronization" for columns via columnsOverride is currently not supported
     * see Localization\State and therefore leads to an exception in the LocalizationStateSelector wizard.
     * Therefore, the setting is removed for now and the integrator is informed accordingly.
     */
    protected function removeAllowLanguageSynchronizationFromColumnsOverrides(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => $tableDefinition) {
            if (!is_array($tableDefinition['types'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['types'] ?? [] as $typeName => $typeConfig) {
                foreach ($typeConfig['columnsOverrides'] ?? [] as $columnOverride => $columnOverrideConfig) {
                    if (isset($columnOverrideConfig['config']['behaviour']['allowLanguageSynchronization'])) {
                        unset($tca[$table]['types'][$typeName]['columnsOverrides'][$columnOverride]['config']['behaviour']['allowLanguageSynchronization']);
                        $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA columns override of column \'' . $columnOverride . '\' for type \'' . $typeName . '\' '
                            . 'of table  \'' . $table . '\' sets \'[behaviour][allowLanguageSynchronization]\'. Setting '
                            . 'this option in \'columnsOverrides\' is currently not supported. Please adjust your TCA accordingly.');
                    }
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes the following sub types configuration options:
     *
     * - subtype_value_field
     * - subtypes_addlist
     * - subtypes_excludelist
     */
    protected function removeSubTypesConfiguration(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => $tableDefinition) {
            if (!is_array($tableDefinition['types'] ?? false)) {
                continue;
            }
            foreach ($tableDefinition['types'] ?? [] as $typeName => $typeConfig) {
                if (!isset($typeConfig['subtype_value_field'])
                    && !isset($typeConfig['subtypes_addlist'])
                    && !isset($typeConfig['subtypes_excludelist'])
                ) {
                    continue;
                }
                unset(
                    $tca[$table]['types'][$typeName]['subtype_value_field'],
                    $tca[$table]['types'][$typeName]['subtypes_addlist'],
                    $tca[$table]['types'][$typeName]['subtypes_excludelist'],
                );
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA record type \'' . $typeName . '\' of table \'' . $table . '\' makes '
                    . 'use of the removed "sub types" functionality. The options \'subtype_value_field\', '
                    . '\'subtypes_addlist\' and \'subtypes_excludelist\' are not evaluated anymore. Please adjust your '
                    . 'TCA accordingly by migrating those sub types to dedicated record types.');
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Inline foreign_table relations with a parent being workspace aware and
     * a child not being workspace aware are not supported. The method detects
     * this scenario in parent columns (not in flex forms) and enforces workspace
     * awareness of child tables.
     */
    protected function addWorkspaceAwarenessToInlineChildren(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $parentTable => $parentTableDefinition) {
            if (!($parentTableDefinition['ctrl']['versioningWS'] ?? false)
                || !is_array($parentTableDefinition['columns'] ?? null)
            ) {
                continue;
            }
            foreach ($parentTableDefinition['columns'] as $parentFieldName => $parentFieldConfig) {
                if (($parentFieldConfig['config']['type'] ?? '') === 'inline') {
                    if (empty($parentFieldConfig['config']['foreign_table'] ?? '')) {
                        continue;
                    }
                    $foreignTable = $parentFieldConfig['config']['foreign_table'];
                    if ((bool)($tca[$foreignTable]['ctrl']['versioningWS'] ?? false) === false) {
                        $tca[$foreignTable]['ctrl']['versioningWS'] = true;
                        $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA table \'' . $foreignTable . '\' has been declared workspace aware because it is'
                            . ' used as an inline child in TCA table field \'' . $parentTable . '\':\'' . $parentFieldName . '\','
                            . ' and that table is workspace aware. Please adjust your TCA accordingly by adding'
                            . ' "\'versioningWS\' => true;" to the \'ctrl\' section of \'' . $foreignTable . '\'.');
                    }
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes [config][eval] = 'year'.
     * If [config][eval] becomes empty, it will be removed completely.
     */
    protected function removeEvalYearFlag(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (!GeneralUtility::inList($fieldConfig['config']['eval'] ?? '', 'year')) {
                    continue;
                }

                $evalList = GeneralUtility::trimExplode(',', $fieldConfig['config']['eval'], true);
                // Remove "year" from $evalList
                $evalList = array_filter($evalList, static fn(string $eval): bool => $eval !== 'year');
                if ($evalList !== []) {
                    // Write back filtered 'eval'
                    $tca[$table]['columns'][$fieldName]['config']['eval'] = implode(',', $evalList);
                } else {
                    // 'eval' is empty, remove whole configuration
                    unset($tca[$table]['columns'][$fieldName]['config']['eval']);
                }

                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines'
                    . ' "year" in its "eval" list. This is not evaluated anymore and is therefore removed.'
                    . ' Please adjust your TCA accordingly.');
            }
        }

        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes $TCA[$mytable]['ctrl']['is_static']
     */
    protected function removeIsStaticControlOption(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$configuration) {
            if (!isset($configuration['ctrl']['is_static'])) {
                continue;
            }
            $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages('The \'' . $table . '\' TCA configuration \'is_static\''
                . ' inside the \'ctrl\' section is not evaluated anymore and is therefore removed.'
                . ' Please adjust your TCA accordingly.');
            unset($configuration['ctrl']['is_static']);
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes $[config][search]
     */
    protected function removeFieldSearchConfigOptions(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (!isset($fieldConfig['config']['search'])) {
                    continue;
                }
                unset($fieldConfig['config']['search']);
                $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages(
                    'The TCA field \'' . $fieldName . '\' of table \'' . $table . '\' defines'
                    . ' "search" config options. Those are not evaluated anymore and are therefore removed.'
                    . ' Please adjust your TCA accordingly.'
                );
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }

    /**
     * Removes $TCA[$mytable]['ctrl']['searchFields']
     */
    protected function removeSearchFieldsControlOption(TcaProcessingResult $tcaProcessingResult): TcaProcessingResult
    {
        $tca = $tcaProcessingResult->getTca();
        foreach ($tca as $table => &$configuration) {
            if (!isset($configuration['ctrl']['searchFields'])) {
                continue;
            }
            $searchFields = GeneralUtility::trimExplode(',', (string)$configuration['ctrl']['searchFields'], true);
            $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages(
                'The \'' . $table . '\' TCA configuration \'searchFields\''
                . ' inside the \'ctrl\' section is not evaluated anymore and is therefore removed.'
                . ' Suitable field types, e.g. \'input\' are now automatically considered, while using the'
                . ' \'searchable\' field config for specific exclusion can be used. Please adjust your TCA accordingly.'
            );
            unset($configuration['ctrl']['searchFields']);

            if (!isset($configuration['columns']) || !is_array($configuration['columns'])) {
                continue;
            }
            foreach ($configuration['columns'] as $fieldName => &$fieldConfig) {
                $type = (string)($fieldConfig['config']['type'] ?? '');
                if ($type !== ''
                    && !isset($fieldConfig['config']['searchable'])
                    && !in_array($fieldName, $searchFields, true)
                    && (
                        in_array($type, ['color', 'email', 'flex', 'input', 'json', 'link', 'slug', 'text', 'uuid'], true)
                        || ($type === 'datetime' && !in_array($fieldConfig['config']['dbType'] ?? null, QueryHelper::getDateTimeTypes(), true))
                    )
                ) {
                    $fieldConfig['config']['searchable'] = false;
                    $tcaProcessingResult = $tcaProcessingResult->withAdditionalMessages(
                        'Because the field \'' . $fieldName . '\' of \'' . $table . '\' is considered'
                        . ' searchable based on it\'s TCA type \'' . $type . '\' but is not included in still existing'
                        . ' but no longer evaluated \'searchFields\' TCA \'ctrl\' option, it was automatically set to'
                        . ' searchable => false, to be excluded in searches. Please consider this when adjusting your'
                        . ' TCA towards proper usage of searchable fields.'
                    );
                }
            }
        }
        return $tcaProcessingResult->withTca($tca);
    }
}
