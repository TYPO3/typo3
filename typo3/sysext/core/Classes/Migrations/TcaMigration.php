<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Migrations;

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

use TYPO3\CMS\Core\Configuration\Features;
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
        $tca = $this->migrateT3editorWizardToRenderTypeT3editorIfNotEnabledByTypeConfig($tca);
        $tca = $this->migrateSpecialConfigurationAndRemoveShowItemStylePointerConfig($tca);
        $tca = $this->migrateT3editorWizardWithEnabledByTypeConfigToColumnsOverrides($tca);
        $tca = $this->migrateShowItemAdditionalPaletteToOwnPalette($tca);
        $tca = $this->migrateIconsForFormFieldWizardToNewLocation($tca);
        $tca = $this->migrateExtAndSysextPathToEXTPath($tca);
        $tca = $this->migrateIconsInOptionTags($tca);
        $tca = $this->migrateIconfileRelativePathOrFilenameOnlyToExtReference($tca);
        $tca = $this->migrateSelectFieldRenderType($tca);
        $tca = $this->migrateSelectFieldIconTable($tca);
        $tca = $this->migrateElementBrowserWizardToLinkHandler($tca);
        $tca = $this->migrateDefaultExtrasRteTransFormOptions($tca);
        $tca = $this->migrateSelectTreeOptions($tca);
        $tca = $this->migrateTSconfigSoftReferences($tca);
        $tca = $this->migrateShowIfRteOption($tca);
        $tca = $this->migrateWorkspacesOptions($tca);
        $tca = $this->migrateTranslationTable($tca);
        $tca = $this->migrateL10nModeDefinitions($tca);
        $tca = $this->migratePageLocalizationDefinitions($tca);
        $tca = $this->migrateInlineLocalizationMode($tca);
        $tca = $this->migrateRequestUpdate($tca);
        $tca = $this->migrateInputDateTimeToRenderType($tca);
        $tca = $this->migrateWizardEnableByTypeConfigToColumnsOverrides($tca);
        $tca = $this->migrateColorPickerWizardToRenderType($tca);
        $tca = $this->migrateSelectWizardToValuePicker($tca);
        $tca = $this->migrateSliderWizardToSliderConfiguration($tca);
        $tca = $this->migrateLinkWizardToRenderTypeAndFieldControl($tca);
        $tca = $this->migrateEditWizardToFieldControl($tca);
        $tca = $this->migrateAddWizardToFieldControl($tca);
        $tca = $this->migrateListWizardToFieldControl($tca);
        $tca = $this->migrateLastPiecesOfDefaultExtras($tca);
        $tca = $this->migrateTableWizardToRenderType($tca);
        $tca = $this->migrateFullScreenRichtextToFieldControl($tca);
        $tca = $this->migrateSuggestWizardTypeGroup($tca);
        $tca = $this->migrateOptionsOfTypeGroup($tca);
        $tca = $this->migrateSelectShowIconTable($tca);
        $tca = $this->migrateImageManipulationConfig($tca);
        $tca = $this->migrateinputDateTimeMax($tca);
        $tca = $this->migrateInlineOverrideChildTca($tca);
        $tca = $this->migrateLocalizeChildrenAtParentLocalization($tca);
        $tca = $this->migratePagesLanguageOverlayRemoval($tca);
        $tca = $this->deprecateTypeGroupInternalTypeFile($tca);
        $tca = $this->sanitizeControlSectionIntegrity($tca);

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
     * Migrate type=text field with t3editor wizard to renderType=t3editor without this wizard
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateT3editorWizardToRenderTypeT3editorIfNotEnabledByTypeConfig(array $tca): array
    {
        $newTca = $tca;
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (
                    !empty($fieldConfig['config']['type']) // type is set
                    && trim($fieldConfig['config']['type']) === 'text' // to "text"
                    && isset($fieldConfig['config']['wizards'])
                    && is_array($fieldConfig['config']['wizards']) // and there are wizards
                ) {
                    foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                        if (
                            !empty($wizardConfig['userFunc']) // a userFunc is defined
                            && trim($wizardConfig['userFunc']) === 'TYPO3\\CMS\\T3editor\\FormWizard->main' // and set to FormWizard
                            && (
                                !isset($wizardConfig['enableByTypeConfig']) // and enableByTypeConfig is not set
                                || (isset($wizardConfig['enableByTypeConfig']) && !$wizardConfig['enableByTypeConfig'])  // or set, but not enabled
                            )
                        ) {
                            // Set renderType from text to t3editor
                            $newTca[$table]['columns'][$fieldName]['config']['renderType'] = 't3editor';
                            // Unset this wizard definition
                            unset($newTca[$table]['columns'][$fieldName]['config']['wizards'][$wizardName]);
                            // Move format parameter
                            if (!empty($wizardConfig['params']['format'])) {
                                $newTca[$table]['columns'][$fieldName]['config']['format'] = $wizardConfig['params']['format'];
                            }
                            $this->messages[] = 'The t3editor wizard using \'type\' = \'text\' has been migrated to a \'renderType\' = \'t3editor\' definition.'
                            . 'It has been migrated from TCA "' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'t3editor\']"'
                            . 'to "' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'renderType\'] = \'t3editor\'"';
                        }
                    }
                    // If no wizard is left after migration, unset the whole sub array
                    if (empty($newTca[$table]['columns'][$fieldName]['config']['wizards'])) {
                        unset($newTca[$table]['columns'][$fieldName]['config']['wizards']);
                    }
                }
            }
        }
        return $newTca;
    }

    /**
     * Remove "style pointer", the 5th parameter from "types" "showitem" configuration.
     * Move "specConf", 4th parameter from "types" "showitem" to "types" "columnsOverrides".
     *
     * @param array $tca Incoming TCA
     * @return array Modified TCA
     */
    protected function migrateSpecialConfigurationAndRemoveShowItemStylePointerConfig(array $tca): array
    {
        $newTca = $tca;
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['types']) || !is_array($tableDefinition['types'])) {
                continue;
            }
            foreach ($tableDefinition['types'] as $typeName => $typeArray) {
                if (!isset($typeArray['showitem']) || !is_string($typeArray['showitem']) || strpos($typeArray['showitem'], ';') === false) {
                    // Continue directly if no semicolon is found
                    continue;
                }
                $itemList = GeneralUtility::trimExplode(',', $typeArray['showitem'], true);
                $newFieldStrings = [];
                foreach ($itemList as $fieldString) {
                    $fieldString = rtrim($fieldString, ';');
                    // Unpack the field definition, migrate and remove as much as possible
                    // Keep empty parameters in trimExplode here (third parameter FALSE), so position is not changed
                    $fieldArray = GeneralUtility::trimExplode(';', $fieldString);
                    $fieldArray = [
                        'fieldName' => $fieldArray[0] ?? '',
                        'fieldLabel' => $fieldArray[1] ?? null,
                        'paletteName' => $fieldArray[2] ?? null,
                        'fieldExtra' => $fieldArray[3] ?? null,
                    ];
                    if (!empty($fieldArray['fieldExtra'])) {
                        // Move fieldExtra "specConf" to columnsOverrides "defaultExtras"
                        if (!isset($newTca[$table]['types'][$typeName]['columnsOverrides'])) {
                            $newTca[$table]['types'][$typeName]['columnsOverrides'] = [];
                        }
                        if (!isset($newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldArray['fieldName']])) {
                            $newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldArray['fieldName']] = [];
                        }
                        // Merge with given defaultExtras from columns.
                        // They will be the first part of the string, so if "specConf" from types changes the same settings,
                        // those will override settings from defaultExtras of columns
                        $newDefaultExtras = [];
                        if (!empty($tca[$table]['columns'][$fieldArray['fieldName']]['defaultExtras'])) {
                            $newDefaultExtras[] = $tca[$table]['columns'][$fieldArray['fieldName']]['defaultExtras'];
                        }
                        $newDefaultExtras[] = $fieldArray['fieldExtra'];
                        $newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldArray['fieldName']]['defaultExtras'] = implode(':', $newDefaultExtras);
                    }
                    unset($fieldArray['fieldExtra']);
                    if (count($fieldArray) === 3 && empty($fieldArray['paletteName'])) {
                        unset($fieldArray['paletteName']);
                    }
                    if (count($fieldArray) === 2 && empty($fieldArray['fieldLabel'])) {
                        unset($fieldArray['fieldLabel']);
                    }
                    $newFieldString = implode(';', $fieldArray);
                    if ($newFieldString !== $fieldString) {
                        $this->messages[] = 'The 4th parameter \'specConf\' of the field \'showitem\' with fieldName = \'' . $fieldArray['fieldName'] . '\' has been migrated, from TCA table "'
                            . $table . '[\'types\'][\'' . $typeName . '\'][\'showitem\']"' . 'to "'
                            . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldArray['fieldName'] . '\'][\'defaultExtras\']".';
                    }
                    if (count($fieldArray) === 1 && empty($fieldArray['fieldName'])) {
                        // The field may vanish if nothing is left
                        unset($fieldArray['fieldName']);
                    }
                    if (!empty($newFieldString)) {
                        $newFieldStrings[] = $newFieldString;
                    }
                }
                $newTca[$table]['types'][$typeName]['showitem'] = implode(',', $newFieldStrings);
            }
        }
        return $newTca;
    }

    /**
     * Migrate type=text field with t3editor wizard that is "enableByTypeConfig" to columnsOverrides
     * with renderType=t3editor
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateT3editorWizardWithEnabledByTypeConfigToColumnsOverrides(array $tca): array
    {
        $newTca = $tca;
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (
                    !empty($fieldConfig['config']['type']) // type is set
                    && trim($fieldConfig['config']['type']) === 'text' // to "text"
                    && isset($fieldConfig['config']['wizards'])
                    && is_array($fieldConfig['config']['wizards']) // and there are wizards
                ) {
                    foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                        if (
                            !empty($wizardConfig['userFunc']) // a userFunc is defined
                            && trim($wizardConfig['userFunc']) === 'TYPO3\CMS\T3editor\FormWizard->main' // and set to FormWizard
                            && !empty($wizardConfig['enableByTypeConfig']) // and enableByTypeConfig is enabled
                        ) {
                            // Remove this wizard
                            unset($newTca[$table]['columns'][$fieldName]['config']['wizards'][$wizardName]);
                            // Find configured types that use this wizard
                            if (!isset($tableDefinition['types']) || !is_array($tableDefinition['types'])) {
                                // No type definition at all ... continue directly
                                continue;
                            }
                            foreach ($tableDefinition['types'] as $typeName => $typeArray) {
                                if (
                                    empty($typeArray['columnsOverrides'][$fieldName]['defaultExtras'])
                                    || strpos($typeArray['columnsOverrides'][$fieldName]['defaultExtras'], $wizardName) === false
                                ) {
                                    // Continue directly if this wizard is not enabled for given type
                                    continue;
                                }
                                $defaultExtras = $typeArray['columnsOverrides'][$fieldName]['defaultExtras'];
                                $defaultExtrasArray = GeneralUtility::trimExplode(':', $defaultExtras, true);
                                $newDefaultExtrasArray = [];
                                foreach ($defaultExtrasArray as $fieldExtraField) {
                                    // There might be multiple enabled wizards separated by | ... split them
                                    if (strpos($fieldExtraField, 'wizards[') === 0) {
                                        $enabledWizards = substr($fieldExtraField, 8, strlen($fieldExtraField) - 8); // Cut off "wizards[
                                        $enabledWizards = substr($enabledWizards, 0, strlen($enabledWizards) - 1);
                                        $enabledWizardsArray = GeneralUtility::trimExplode('|', $enabledWizards, true);
                                        $newEnabledWizardsArray = [];
                                        foreach ($enabledWizardsArray as $enabledWizardName) {
                                            if ($enabledWizardName === $wizardName) {
                                                // Found a columnsOverrides configuration that has this wizard enabled
                                                // Force renderType = t3editor
                                                $newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['config']['renderType'] = 't3editor';
                                                // Transfer format option if given
                                                if (!empty($wizardConfig['params']['format'])) {
                                                    $newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['config']['format'] = $wizardConfig['params']['format'];
                                                }
                                                $this->messages[] = 'The t3editor wizard using \'type\' = \'text\', with the "enableByTypeConfig" wizard set to 1,'
                                                . 'has been migrated to the \'renderType\' = \'t3editor\' definition.'
                                                . 'It has been migrated from TCA "' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'t3editor\']"'
                                                . 'to "' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'renderType\'] = \'t3editor\'"';
                                            } else {
                                                // Some other enabled wizard
                                                $newEnabledWizardsArray[] = $enabledWizardName;
                                            }
                                        }
                                        if (!empty($newEnabledWizardsArray)) {
                                            $newDefaultExtrasArray[] = 'wizards[' . implode('|', $newEnabledWizardsArray) . ']';
                                        }
                                    } else {
                                        $newDefaultExtrasArray[] = $fieldExtraField;
                                    }
                                }
                                if (!empty($newDefaultExtrasArray)) {
                                    $newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['defaultExtras'] = implode(':', $newDefaultExtrasArray);
                                } else {
                                    unset($newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['defaultExtras']);
                                }
                            }
                        }
                    }
                    // If no wizard is left after migration, unset the whole sub array
                    if (empty($newTca[$table]['columns'][$fieldName]['config']['wizards'])) {
                        unset($newTca[$table]['columns'][$fieldName]['config']['wizards']);
                    }
                }
            }
        }
        return $newTca;
    }

    /**
     * Migrate types showitem 'aField;aLabel;aPalette' to 'afield;aLabel, --palette--;;aPalette'
     *
     * Old showitem can have a syntax like:
     * fieldName;aLabel;aPalette
     * This way, the palette with name "aPalette" is rendered after fieldName.
     * The migration parses this to a syntax like:
     * fieldName;aLabel, --palette--;;paletteName
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateShowItemAdditionalPaletteToOwnPalette(array $tca): array
    {
        $newTca = $tca;
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['types']) || !is_array($tableDefinition['types'])) {
                continue;
            }
            foreach ($tableDefinition['types'] as $typeName => $typeArray) {
                if (
                    !isset($typeArray['showitem'])
                    || !is_string($typeArray['showitem'])
                    || strpos($typeArray['showitem'], ';') === false // no field parameters
                ) {
                    continue;
                }
                $itemList = GeneralUtility::trimExplode(',', $typeArray['showitem'], true);
                $newFieldStrings = [];
                foreach ($itemList as $fieldString) {
                    $fieldArray = GeneralUtility::trimExplode(';', $fieldString);
                    $fieldArray = [
                        'fieldName' => $fieldArray[0] ?? '',
                        'fieldLabel' => $fieldArray[1] ?? null,
                        'paletteName' => $fieldArray[2] ?? null,
                    ];
                    if ($fieldArray['fieldName'] !== '--palette--' && $fieldArray['paletteName'] !== null) {
                        if ($fieldArray['fieldLabel']) {
                            $fieldString = $fieldArray['fieldName'] . ';' . $fieldArray['fieldLabel'];
                        } else {
                            $fieldString = $fieldArray['fieldName'];
                        }
                        $paletteString = '--palette--;;' . $fieldArray['paletteName'];
                        $this->messages[] = 'Migrated \'showitem\' field from TCA table '
                            . $table . '[\'types\'][\'' . $typeName . '\']" : Moved additional palette'
                            . ' with name "' . $table . '[\'types\'][\'' . $typeName . '\'][\'' . $fieldArray['paletteName'] . '\']" as 3rd argument of field "'
                            . $table . '[\'types\'][\'' . $typeName . '\'][\'' . $fieldArray['fieldName'] . '\']"'
                            . 'to an own palette. The result of this part is: "' . $fieldString . ', ' . $paletteString . '"';
                        $newFieldStrings[] = $fieldString;
                        $newFieldStrings[] = $paletteString;
                    } else {
                        $newFieldStrings[] = $fieldString;
                    }
                }
                $newTca[$table]['types'][$typeName]['showitem'] = implode(',', $newFieldStrings);
            }
        }
        return $newTca;
    }

    /**
     * Migrate core icons for form field wizard to new location
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateIconsForFormFieldWizardToNewLocation(array $tca): array
    {
        $newTca = $tca;

        $newFileLocations = [
            'add.gif' => 'actions-add',
            'link_popup.gif' => 'actions-wizard-link',
            'wizard_rte2.gif' => 'actions-wizard-rte',
            'wizard_table.gif' => 'content-table',
            'edit2.gif' => 'actions-open',
            'list.gif' => 'actions-system-list-open',
            'wizard_forms.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_forms.gif',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_add.gif' => 'actions-add',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif' => 'content-table',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_edit.gif' => 'actions-open',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_list.gif' => 'actions-system-list-open',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif' => 'actions-wizard-link',
            'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_rte.gif' => 'actions-wizard-rte'
        ];
        $oldFileNames = array_keys($newFileLocations);

        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (
                    isset($fieldConfig['config']['wizards'])
                    && is_array($fieldConfig['config']['wizards']) // and there are wizards
                ) {
                    foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                        if (!is_array($wizardConfig)) {
                            continue;
                        }

                        foreach ($wizardConfig as $option => $value) {
                            if ($option === 'icon' && in_array($value, $oldFileNames, true)) {
                                $newTca[$table]['columns'][$fieldName]['config']['wizards'][$wizardName]['icon'] = $newFileLocations[$value];
                                $this->messages[] = 'The icon path of wizard "' . $wizardName
                                    . '" from TCA table "'
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\'][\'icon\']"'
                                    . 'has been migrated to '
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\'][\'icon\']" = \'' . $newFileLocations[$value] . '\'.';
                            }
                        }
                    }
                }
            }
        }

        return $newTca;
    }

    /**
     * Migrate file reference which starts with ext/ or sysext/ to EXT:
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateExtAndSysextPathToEXTPath(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (
                    !empty($fieldConfig['config']['type']) // type is set
                    && trim($fieldConfig['config']['type']) === 'select' // to "select"
                    && isset($fieldConfig['config']['items'])
                    && is_array($fieldConfig['config']['items']) // and there are items
                ) {
                    foreach ($fieldConfig['config']['items'] as &$itemConfig) {
                        // more then two values? then the third entry is the image path
                        if (!empty($itemConfig[2])) {
                            $tcaPath = implode('.', [$table, 'columns', $fieldName, 'config', 'items']);
                            $pathParts = GeneralUtility::trimExplode('/', $itemConfig[2]);
                            // remove first element (ext or sysext)
                            array_shift($pathParts);
                            $path = implode('/', $pathParts);
                            // If the path starts with ext/ or sysext/ migrate it
                            if (
                                strpos($itemConfig[2], 'ext/') === 0
                                || strpos($itemConfig[2], 'sysext/') === 0
                            ) {
                                $this->messages[] = '[' . $tcaPath . '] ext/ or sysext/ within the path (' . $path . ') in items array is deprecated, use EXT: reference';
                                $itemConfig[2] = 'EXT:' . $path;
                            } elseif (strpos($itemConfig[2], 'i/') === 0) {
                                $this->messages[] = '[' . $tcaPath . '] i/ within the path (' . $path . ') in items array is deprecated, use EXT: reference';
                                $itemConfig[2] = 'EXT:backend/Resources/Public/Images/' . substr($itemConfig[2], 2);
                            }
                        }
                    }
                }
            }
        }
        return $tca;
    }

    /**
     * Migrate "iconsInOptionTags" for "select" TCA fields
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateIconsInOptionTags(array $tca): array
    {
        $newTca = $tca;

        foreach ($newTca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (isset($fieldConfig['config']['iconsInOptionTags'])) {
                    unset($fieldConfig['config']['iconsInOptionTags']);
                    $this->messages[] = 'Configuration option \'iconsInOptionTags\' was removed from field "' . $fieldName . '" in TCA table "' . $table . '[\'config\']"';
                }
            }
        }

        return $newTca;
    }

    /**
     * Migrate "iconfile" references which starts with ../ to EXT: and consisting of filename only to absolute paths in EXT:t3skin
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateIconfileRelativePathOrFilenameOnlyToExtReference(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['ctrl']) || !is_array($tableDefinition['ctrl'])) {
                continue;
            }
            if (!isset($tableDefinition['ctrl']['iconfile'])) {
                continue;
            }
            if (strpos($tableDefinition['ctrl']['iconfile'], '../typo3conf/ext/') === 0) {
                $tableDefinition['ctrl']['iconfile'] = str_replace('../typo3conf/ext/', 'EXT:', $tableDefinition['ctrl']['iconfile']);
                $tcaPath = implode('.', [$table, 'ctrl', 'iconfile']);
                $this->messages[] = '[' . $tcaPath . '] relative path to ../typo3conf/ext/ is deprecated, use EXT: instead';
            } elseif (strpos($tableDefinition['ctrl']['iconfile'], '/') === false) {
                $tableDefinition['ctrl']['iconfile'] = 'EXT:backend/Resources/Public/Images/' . $tableDefinition['ctrl']['iconfile'];
                $tcaPath = implode('.', [$table, 'ctrl', 'iconfile']);
                $this->messages[] = '[' . $tcaPath . '] filename only is deprecated, use EXT: or absolute reference instead';
            }
        }
        return $tca;
    }

    /**
     * Migrate "type=select" with "renderMode=[tree|singlebox|checkbox]" to "renderType=[selectTree|selectSingleBox|selectCheckBox]".
     * This migration also take care of "maxitems" settings and set "renderType=[selectSingle|selectMultipleSideBySide]" if no other
     * renderType is already set.
     *
     * @param array $tca
     * @return array
     */
    public function migrateSelectFieldRenderType(array $tca): array
    {
        $newTca = $tca;

        foreach ($newTca as $table => &$tableDefinition) {
            if (empty($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $columnName => &$columnDefinition) {
                // Only handle select fields.
                if (empty($columnDefinition['config']['type']) || $columnDefinition['config']['type'] !== 'select') {
                    continue;
                }
                // Do not handle field where the render type is set.
                if (!empty($columnDefinition['config']['renderType'])) {
                    continue;
                }

                $tableColumnInfo = 'table "' . $table . '[\'columns\'][\'' . $columnName . '\']"';
                $this->messages[] = 'Using the \'type\' = \'select\' field in "' . $table . '[\'columns\'][\'' . $columnName . '\'][\'config\'][\'type\'] = \'select\'" without the "renderType" setting in "'
                    . $table . '[\'columns\'][\'' . $columnName . '\'][\'config\'][\'renderType\']" is deprecated.';

                $columnConfig = &$columnDefinition['config'];
                if (!empty($columnConfig['renderMode'])) {
                    $this->messages[] = 'The "renderMode" setting for select fields is deprecated. Please use "renderType" instead in ' . $tableColumnInfo;
                    switch ($columnConfig['renderMode']) {
                        case 'tree':
                            $columnConfig['renderType'] = 'selectTree';
                            break;
                        case 'singlebox':
                            $columnConfig['renderType'] = 'selectSingleBox';
                            break;
                        case 'checkbox':
                            $columnConfig['renderType'] = 'selectCheckBox';
                            break;
                        default:
                            $this->messages[] = 'The render mode ' . $columnConfig['renderMode'] . ' is invalid for the select field in ' . $tableColumnInfo;
                    }
                    continue;
                }

                $maxItems = !empty($columnConfig['maxitems']) ? (int)$columnConfig['maxitems'] : 1;
                if ($maxItems <= 1) {
                    $columnConfig['renderType'] = 'selectSingle';
                } else {
                    $columnConfig['renderType'] = 'selectMultipleSideBySide';
                }
            }
        }

        return $newTca;
    }

    /**
     * Migrate the visibility of the icon table for fields with "renderType=selectSingle"
     *
     * @param array $tca
     * @return array Migrated TCA
     */
    public function migrateSelectFieldIconTable(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (empty($fieldConfig['config']['renderType']) || $fieldConfig['config']['renderType'] !== 'selectSingle') {
                    continue;
                }
                if (array_key_exists('noIconsBelowSelect', $fieldConfig['config'])) {
                    $this->messages[] = 'The "noIconsBelowSelect" setting for select fields in table "'
                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']" was removed. Please define the setting "showIconTable" in table "'
                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'showIconTable\']"';
                    if (!$fieldConfig['config']['noIconsBelowSelect']) {
                        // If old setting was explicitly false, enable icon table if not defined yet
                        if (!array_key_exists('showIconTable', $fieldConfig['config'])) {
                            $fieldConfig['config']['showIconTable'] = true;
                        }
                    }
                    unset($fieldConfig['config']['noIconsBelowSelect']);
                }
                if (array_key_exists('suppress_icons', $fieldConfig['config'])) {
                    $this->messages[] = 'The "suppress_icons" setting for select fields in table "'
                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']" was removed. Please define the setting "showIconTable" for table "'
                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'showIconTable\']"';
                    unset($fieldConfig['config']['suppress_icons']);
                }
                if (array_key_exists('foreign_table_loadIcons', $fieldConfig['config'])) {
                    $this->messages[] = 'The "foreign_table_loadIcons" setting for select fields in table "'
                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']" was removed. Please define the setting "showIconTable" for table "'
                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'showIconTable\']"';
                    unset($fieldConfig['config']['foreign_table_loadIcons']);
                }
            }
        }
        return $tca;
    }

    /**
     * Migrate wizard "wizard_element_browser" used in mode "wizard" to use the "wizard_link" instead
     *
     * @param array $tca
     * @return array Migrated TCA
     */
    protected function migrateElementBrowserWizardToLinkHandler(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (
                    isset($fieldConfig['config']['wizards']['link']['module']['name']) && $fieldConfig['config']['wizards']['link']['module']['name'] === 'wizard_element_browser'
                    && isset($fieldConfig['config']['wizards']['link']['module']['urlParameters']['mode']) && $fieldConfig['config']['wizards']['link']['module']['urlParameters']['mode'] === 'wizard'
                ) {
                    $fieldConfig['config']['wizards']['link']['module']['name'] = 'wizard_link';
                    unset($fieldConfig['config']['wizards']['link']['module']['urlParameters']['mode']);
                    if (empty($fieldConfig['config']['wizards']['link']['module']['urlParameters'])) {
                        unset($fieldConfig['config']['wizards']['link']['module']['urlParameters']);
                    }
                    $this->messages[] = 'Reference to "wizard_element_browser" was migrated from "'
                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'link\'][\'module\'][\'name\'] === \'wizard_element_browser\'"'
                        . ' to new "wizard_link", "'
                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'link\'][\'module\'][\'name\'] = \'wizard_link\'"';
                }
            }
        }
        return $tca;
    }

    /**
     * Migrate defaultExtras "richtext:rte_transform[mode=ts_css]" and similar stuff like
     * "richtext:rte_transform[mode=ts_css]" to "richtext:rte_transform"
     *
     * @param array $tca
     * @return array Migrated TCA
     */
    protected function migrateDefaultExtrasRteTransFormOptions(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (isset($fieldConfig['defaultExtras'])) {
                    $originalValue = $fieldConfig['defaultExtras'];
                    $defaultExtrasArray = GeneralUtility::trimExplode(':', $originalValue, true);
                    $isRichtextField = false;
                    foreach ($defaultExtrasArray as $defaultExtrasField) {
                        if (strpos($defaultExtrasField, 'richtext') === 0) {
                            $isRichtextField = true;
                            $fieldConfig['config']['enableRichtext'] = true;
                            $fieldConfig['config']['richtextConfiguration'] = 'default';
                        }
                    }
                    if ($isRichtextField) {
                        unset($fieldConfig['defaultExtras']);
                        $this->messages[] = 'RTE configuration via \'defaultExtras\' options are deprecated. String "' . $originalValue . '" in TCA'
                            . ' ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'defaultExtras\'] was changed to'
                            . ' options in ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']';
                    }
                }
            }
        }

        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['types']) || !is_array($tableDefinition['types'])) {
                continue;
            }
            foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                if (!isset($typeArray['columnsOverrides']) || !is_array($typeArray['columnsOverrides'])) {
                    continue;
                }
                foreach ($typeArray['columnsOverrides'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['defaultExtras'])) {
                        $originalValue = $fieldConfig['defaultExtras'];
                        $defaultExtrasArray = GeneralUtility::trimExplode(':', $originalValue, true);
                        $isRichtextField = false;
                        foreach ($defaultExtrasArray as $defaultExtrasField) {
                            if (strpos($defaultExtrasField, 'richtext') === 0) {
                                $isRichtextField = true;
                                $fieldConfig['config']['enableRichtext'] = true;
                                $fieldConfig['config']['richtextConfiguration'] = 'default';
                            }
                        }
                        if ($isRichtextField) {
                            unset($fieldConfig['defaultExtras']);
                            $this->messages[] = 'RTE configuration via \'defaultExtras\' options are deprecated. String "' . $originalValue . '" in TCA'
                                . ' ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'defaultExtras\']' .
                                ' was changed to options in ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']';
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrates selectTree fields deprecated options
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateSelectTreeOptions(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (isset($fieldConfig['config']['renderType']) && $fieldConfig['config']['renderType'] === 'selectTree') {
                    if (isset($fieldConfig['config']['treeConfig']['appearance']['width'])) {
                        $this->messages[] = 'The selectTree field [\'treeConfig\'][\'appearance\'][\'width\'] setting is deprecated'
                                    . ' and was removed in TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                    . '[\'treeConfig\'][\'appearance\'][\'width\'] ';
                        unset($fieldConfig['config']['treeConfig']['appearance']['width']);
                    }

                    if (isset($fieldConfig['config']['treeConfig']['appearance']['allowRecursiveMode'])) {
                        $this->messages[] = 'The selectTree field [\'treeConfig\'][\'appearance\'][\'allowRecursiveMode\'] setting is deprecated'
                                    . ' and was removed in TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                    . '[\'treeConfig\'][\'appearance\'][\'allowRecursiveMode\'] ';
                        unset($fieldConfig['config']['treeConfig']['appearance']['allowRecursiveMode']);
                    }

                    if (isset($fieldConfig['config']['autoSizeMax'])) {
                        $this->messages[] = 'The selectTree field [\'autoSizeMax\'] setting is deprecated'
                                    . ' and was removed in TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'autoSizeMax\'].'
                                    . ' The \'size\' value was adapted to the previous autoSizeMax value';
                        $fieldConfig['config']['size'] = $fieldConfig['config']['autoSizeMax'];
                        unset($fieldConfig['config']['autoSizeMax']);
                    }
                }
            }
        }
        return $tca;
    }

    /**
     * Migrates selectTree fields deprecated options
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateTSconfigSoftReferences(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (isset($fieldConfig['config'])) {
                    if (isset($fieldConfig['config']['softref'])) {
                        $softReferences = array_flip(GeneralUtility::trimExplode(',', $fieldConfig['config']['softref']));
                        $changed = false;
                        if (isset($softReferences['TSconfig'])) {
                            $changed = true;
                            unset($softReferences['TSconfig']);
                        }
                        if (isset($softReferences['TStemplate'])) {
                            $changed = true;
                            unset($softReferences['TStemplate']);
                        }
                        if ($changed) {
                            if (!empty($softReferences)) {
                                $softReferences = array_flip($softReferences);
                                $fieldConfig['config']['softref'] = implode(',', $softReferences);
                            } else {
                                unset($fieldConfig['config']['softref']);
                            }
                            $this->messages[] = 'The soft reference setting using \'TSconfig\' and '
                                . '\'TStemplate\' was removed in TCA ' . $table . '[\'columns\']'
                                . '[\'' . $fieldName . '\'][\'config\'][\'softref\']';
                        }
                    }
                }
            }
        }
        return $tca;
    }

    /**
     * Removes the option "showIfRTE" for TCA type "check"
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateShowIfRteOption(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (isset($fieldConfig['config']) && $fieldConfig['config']['type'] === 'check') {
                    if (isset($fieldConfig['config']['showIfRTE'])) {
                        unset($fieldConfig['config']['showIfRTE']);
                        $this->messages[] = 'The TCA setting \'showIfRTE\' was removed '
                            . 'in TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']';
                    }
                }
            }
        }
        return $tca;
    }

    /**
     * Casts "versioningWS" to bool, and removes "versioning_followPages"
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateWorkspacesOptions(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['ctrl']['versioningWS']) && !is_bool($tableDefinition['ctrl']['versioningWS'])) {
                $tableDefinition['ctrl']['versioningWS'] = (bool)$tableDefinition['ctrl']['versioningWS'];
                $this->messages[] = 'The TCA setting \'versioningWS\' was set to a boolean value '
                    . 'in TCA ' . $table . '[\'ctrl\'][\'versioningWS\']';
            }
            if (isset($tableDefinition['ctrl']['versioning_followPages']) && !empty($tableDefinition['ctrl']['versioning_followPages'])) {
                unset($tableDefinition['ctrl']['versioning_followPages']);
                $this->messages[] = 'The TCA setting \'versioning_followPages\' was removed as it is unused '
                    . 'in TCA ' . $table . '[\'ctrl\'][\'versioning_followPages\']';
            }
        }
        return $tca;
    }

    /**
     * Removes "transForeignTable" and "transOrigPointerTable" which has been
     * used for tables "pages" and "pages_languages_overlay" in the core only.
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateTranslationTable(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!empty($tableDefinition['ctrl']['transForeignTable'])) {
                unset($tableDefinition['ctrl']['transForeignTable']);
                $this->messages[] = 'The TCA setting \'transForeignTable\' was removed '
                    . 'in TCA ' . $table . '[\'ctrl\'][\'transForeignTable\']';
            }
            if (!empty($tableDefinition['ctrl']['transOrigPointerTable'])) {
                unset($tableDefinition['ctrl']['transOrigPointerTable']);
                $this->messages[] = 'The TCA setting \'transOrigPointerTable\' was removed '
                    . 'in TCA ' . $table . '[\'ctrl\'][\'transOrigPointerTable\']';
            }
        }
        return $tca;
    }

    /**
     * Removes "noCopy" from possible settings for "l10n_mode" for each column.
     *
     * @param array $tca
     * @return array Migrated TCA
     */
    protected function migrateL10nModeDefinitions(array $tca)
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (empty($fieldConfig['l10n_mode'])) {
                    continue;
                }
                if ($fieldConfig['l10n_mode'] === 'noCopy') {
                    unset($fieldConfig['l10n_mode']);
                    $this->messages[] = 'The TCA setting \'noCopy\' was removed '
                        . 'in TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'l10n_mode\']';
                }
                if (!empty($fieldConfig['l10n_mode']) && $fieldConfig['l10n_mode'] === 'mergeIfNotBlank') {
                    unset($fieldConfig['l10n_mode']);
                    if (empty($fieldConfig['config']['behaviour']['allowLanguageSynchronization'])) {
                        $fieldConfig['config']['behaviour']['allowLanguageSynchronization'] = true;
                    }
                    $this->messages[] = 'The TCA setting \'mergeIfNotBlank\' was removed '
                        . 'in TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'l10n_mode\']'
                        . ' and changed to ' . $table . '[\'columns\'][\'' . $fieldName . '\']'
                        . '[\'config\'][\'behaviour\'][\'allowLanguageSynchronization\'] = true';
                }
            }
        }
        return $tca;
    }

    /**
     * Migrates localization definitions such as "allowLanguageSynchronization"
     * or "l10n_mode" for tables pages and pages_language_overlay.
     *
     * @param array $tca
     * @return array Migrated TCA
     */
    protected function migratePageLocalizationDefinitions(array $tca)
    {
        if (
            empty($tca['pages_language_overlay']['columns'])
        ) {
            return $tca;
        }

        // ensure, that localization settings are defined for
        // pages and not for pages_language_overlay
        foreach ($tca['pages_language_overlay']['columns'] as $fieldName => &$fieldConfig) {
            $l10nMode = $fieldConfig['l10n_mode'] ?? null;
            $allowLanguageSynchronization = $fieldConfig['config']['behaviour']['allowLanguageSynchronization'] ?? null;

            $oppositeFieldConfig = $tca['pages']['columns'][$fieldName] ?? [];
            $oppositeL10nMode = $oppositeFieldConfig['l10n_mode'] ?? null;
            $oppositeAllowLanguageSynchronization = $oppositeFieldConfig['config']['behaviour']['allowLanguageSynchronization'] ?? null;

            if ($l10nMode !== null) {
                if (!empty($oppositeFieldConfig) && $oppositeL10nMode !== 'exclude') {
                    $tca['pages']['columns'][$fieldName]['l10n_mode'] = $l10nMode;
                    $this->messages[] = 'The TCA setting \'l10n_mode\' was migrated '
                        . 'to TCA pages[\'columns\'][\'' . $fieldName . '\'][\'l10n_mode\'] '
                        . 'from TCA pages_language_overlay[\'columns\'][\'' . $fieldName . '\'][\'l10n_mode\']';
                }
            }

            if (!empty($allowLanguageSynchronization) && empty($oppositeAllowLanguageSynchronization)) {
                $tca['pages']['columns'][$fieldName]['config']['behaviour']['allowLanguageSynchronization'] = (bool)$allowLanguageSynchronization;
                $this->messages[] = 'The TCA setting \'allowLanguageSynchronization\' was migrated '
                    . 'to TCA pages[\'columns\'][\'' . $fieldName . '\']'
                    . '[\'config\'][\'behaviour\'][\'allowLanguageSynchronization\'] '
                    . 'from TCA pages_language_overlay[\'columns\'][\'' . $fieldName . '\']'
                    . '[\'config\'][\'behaviour\'][\'allowLanguageSynchronization\']';
            }
        }

        return $tca;
    }

    /**
     * Removes "localizationMode" set to "keep" if used in combination with
     * "allowLanguageSynchronization" - in general "localizationMode" is
     * deprecated since TYPO3 CMS 8 and will be removed in TYPO3 v9.
     *
     * @param array $tca
     * @return array
     */
    protected function migrateInlineLocalizationMode(array $tca)
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? null) !== 'inline') {
                    continue;
                }

                $inlineLocalizationMode = ($fieldConfig['config']['behaviour']['localizationMode'] ?? null);
                if ($inlineLocalizationMode === null) {
                    continue;
                }

                $allowLanguageSynchronization = ($fieldConfig['config']['behaviour']['allowLanguageSynchronization'] ?? null);
                if ($inlineLocalizationMode === 'keep' && $allowLanguageSynchronization) {
                    unset($fieldConfig['config']['behaviour']['localizationMode']);
                    $this->messages[] = 'The TCA setting \'localizationMode\' is counter-productive '
                        . ' if being used in combination with \'allowLanguageSynchronization\' and '
                        . ' thus has been removed from TCA for ' . $table . '[\'columns\']'
                        . '[\'' . $fieldName . '\'][\'config\'][\'behaviour\'][\'localizationMode\']';
                } else {
                    $this->messages[] = 'The TCA setting \'localizationMode\' is deprecated '
                        . ' and should be removed from TCA for ' . $table . '[\'columns\']'
                        . '[\'' . $fieldName . '\'][\'config\'][\'behaviour\'][\'localizationMode\']';
                }
            }
        }
        return $tca;
    }

    /**
     * Move ['ctrl']['requestUpdate'] to 'onChange => "reload"' of single fields
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateRequestUpdate(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!empty($tableDefinition['ctrl']['requestUpdate'])) {
                $fields = GeneralUtility::trimExplode(',', $tableDefinition['ctrl']['requestUpdate']);
                foreach ($fields as $field) {
                    if (isset($tableDefinition['columns'][$field])) {
                        $tableDefinition['columns'][$field]['onChange'] = 'reload';
                    }
                }
                $this->messages[] = 'The TCA setting [\'ctrl\'][\'requestUpdate\'] was removed from '
                    . ' table ' . $table . '. The column field(s) "' . implode('" and "', $fields) . '" were updated'
                    . ' and contain option "\'onChange\' => \'reload\'" parallel to \'config\' and \'label\' section.';
                unset($tableDefinition['ctrl']['requestUpdate']);
            }
        }
        return $tca;
    }

    /**
     * Move all type=input with eval=date/time configuration to an own renderType
     *
     * @param array $tca
     * @return array Migrated TCA
     */
    protected function migrateInputDateTimeToRenderType(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (isset($fieldConfig['config']['type']) && $fieldConfig['config']['type'] !== 'input') {
                    continue;
                }
                $eval = $fieldConfig['config']['eval'] ?? '';
                $eval = GeneralUtility::trimExplode(',', $eval, true);
                if (in_array('date', $eval, true)
                    || in_array('datetime', $eval, true)
                    || in_array('time', $eval, true)
                    || in_array('timesec', $eval, true)
                ) {
                    if (!isset($fieldConfig['config']['renderType'])) {
                        $fieldConfig['config']['renderType'] = 'inputDateTime';
                        $this->messages[] = 'The TCA setting \'renderType\' => \'inputDateTime\' was added '
                            . 'in TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']';
                    }
                }
            }
        }
        return $tca;
    }

    /**
     * Wizards configuration may hold "enableByTypeConfig" and are then enabled
     * for certain types via "defaultExtras".
     * Find wizards configured like that and migrate them to "columnsOverrides"
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    public function migrateWizardEnableByTypeConfigToColumnsOverrides(array $tca): array
    {
        $newTca = $tca;
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (
                    !empty($fieldConfig['config']['type']) // type is set
                    && isset($fieldConfig['config']['wizards'])
                    && is_array($fieldConfig['config']['wizards']) // and there are wizards
                ) {
                    foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                        if (isset($wizardConfig['enableByTypeConfig']) // and enableByTypeConfig is given
                            && $wizardConfig['enableByTypeConfig'] // and enabled
                        ) { // and enableByTypeConfig is enabled
                            // Remove this "enableByTypeConfig" wizard from columns
                            unset($newTca[$table]['columns'][$fieldName]['config']['wizards'][$wizardName]);
                            if (!isset($tableDefinition['types']) || !is_array($tableDefinition['types'])) {
                                // No type definition at all ... continue directly
                                continue;
                            }
                            foreach ($tableDefinition['types'] as $typeName => $typeArray) {
                                if (empty($typeArray['columnsOverrides'][$fieldName]['defaultExtras'])
                                    || strpos($typeArray['columnsOverrides'][$fieldName]['defaultExtras'], $wizardName) === false
                                ) {
                                    // Continue directly if this wizard is not enabled for given type
                                    continue;
                                }
                                $defaultExtras = $typeArray['columnsOverrides'][$fieldName]['defaultExtras'];
                                $defaultExtrasArray = GeneralUtility::trimExplode(':', $defaultExtras, true);
                                $newDefaultExtrasArray = [];
                                foreach ($defaultExtrasArray as $fieldExtraField) {
                                    if (strpos($fieldExtraField, 'wizards[') === 0) {
                                        $enabledWizards = substr($fieldExtraField, 8, strlen($fieldExtraField) - 8); // Cut off "wizards[
                                        $enabledWizards = substr($enabledWizards, 0, strlen($enabledWizards) - 1);
                                        $enabledWizardsArray = GeneralUtility::trimExplode('|', $enabledWizards, true);
                                        foreach ($enabledWizardsArray as $enabledWizardName) {
                                            if ($enabledWizardName === $wizardName) {
                                                // Fill new array
                                                unset($newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['defaultExtras']);
                                                $newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['config']['wizards'][$enabledWizardName] = $wizardConfig;
                                                unset($newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['config']['wizards'][$enabledWizardName]['enableByTypeConfig']);
                                                $this->messages[] = 'The wizard with "enableByTypeConfig" set to 1 has been migrated to \'columnsOverrides\'.'
                                                    . ' It has been migrated from "'
                                                . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']" to '
                                                . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\'].';
                                            }
                                        }
                                    } else {
                                        $newDefaultExtrasArray[] = $fieldExtraField;
                                    }
                                }
                                if ($defaultExtrasArray !== $newDefaultExtrasArray
                                    && !empty($newDefaultExtrasArray)
                                ) {
                                    $newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['defaultExtras'] = implode(':', $newDefaultExtrasArray);
                                }
                            }
                        } elseif (isset($wizardConfig['enableByTypeConfig'])) {
                            // enableByTypeConfig is set, but not true or 1 or '1', just delete it.
                            unset($newTca[$table]['columns'][$fieldName]['config']['wizards'][$wizardName]['enableByTypeConfig']);
                        }
                    }
                }
            }
        }
        return $newTca;
    }

    /**
     * Migrates fields having a colorpicker wizard to a color field
     *
     * @param array $tca Incoming TCA
     * @return array Migrated TCA
     */
    protected function migrateColorPickerWizardToRenderType(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config'])) {
                        // Do not handle field where the render type is set.
                        if (!empty($fieldConfig['config']['renderType'])) {
                            continue;
                        }
                        if ($fieldConfig['config']['type'] === 'input') {
                            if (isset($fieldConfig['config']['wizards']) && is_array($fieldConfig['config']['wizards'])) {
                                foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizard) {
                                    if (isset($wizard['type']) && ($wizard['type'] === 'colorbox')) {
                                        unset($fieldConfig['config']['wizards'][$wizardName]);
                                        if (empty($fieldConfig['config']['wizards'])) {
                                            unset($fieldConfig['config']['wizards']);
                                        }
                                        $fieldConfig['config']['renderType'] = 'colorpicker';
                                        $this->messages[] = 'The color-picker wizard using \'colorbox\' is deprecated'
                                            . ' in TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                            . '[\'wizards\'][\'' . $wizardName . '\'] and was changed to ' . $table
                                            . '[\'columns\'][\'' . $fieldName . '\'][\'config\'] = \'colorpicker\'';
                                    }
                                }
                            }
                            if (empty($fieldConfig['config']['wizards'])) {
                                unset($fieldConfig['config']['wizards']);
                            }
                            if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                                foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                    if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                        if (isset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                            && is_array($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                        ) {
                                            foreach ($typeArray['columnsOverrides'][$fieldName]['config']['wizards'] as $wizardName => $wizard) {
                                                if (isset($wizard['type']) && ($wizard['type'] === 'colorbox')) {
                                                    unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'][$wizardName]);
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['type'] = 'input';
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['renderType'] = 'colorpicker';
                                                    $this->messages[] = 'The color-picker wizard in columnsOverrides using \'colorbox\' has been migrated to a \'rendertype\' = \'colorpicker\'.'
                                                        . ' It has been migrated from TCA "' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                        . '[\'wizards\'][\'' . $wizardName . '\'][\'type\'] = \'colorbox\'"" to "' . $table
                                                        . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'renderType\'] = \'colorpicker\'"';
                                                }
                                            }
                                            if (empty($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])) {
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards']);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Move type=input with select wizard to config['valuePicker']
     *
     * @param array $tca
     * @return array Migrated TCA
     */
    protected function migrateSelectWizardToValuePicker(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && ($fieldConfig['config']['type'] === 'input' || $fieldConfig['config']['type'] === 'text')) {
                        if (isset($fieldConfig['config']['wizards']) && is_array($fieldConfig['config']['wizards'])) {
                            foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                                if (isset($wizardConfig['type'])
                                    && $wizardConfig['type'] === 'select'
                                    && isset($wizardConfig['items'])
                                    && is_array($wizardConfig['items'])
                                ) {
                                    $fieldConfig['config']['valuePicker']['items'] = $wizardConfig['items'];
                                    if (isset($wizardConfig['mode'])
                                        && is_string($wizardConfig['mode'])
                                        && in_array($wizardConfig['mode'], ['append', 'prepend', ''])
                                    ) {
                                        $fieldConfig['config']['valuePicker']['mode'] = $wizardConfig['mode'];
                                    }
                                    unset($fieldConfig['config']['wizards'][$wizardName]);
                                    $this->messages[] = 'The select wizard in TCA '
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']'
                                        . ' has been migrated to '
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'valuePicker\'].';
                                }
                            }
                        }
                        if (empty($fieldConfig['config']['wizards'])) {
                            unset($fieldConfig['config']['wizards']);
                        }
                        if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                            foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                    if (isset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                        && is_array($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                    ) {
                                        foreach ($typeArray['columnsOverrides'][$fieldName]['config']['wizards'] as $wizardName => $wizard) {
                                            if (isset($wizard['type'])
                                                && ($wizard['type'] === 'select')
                                                && isset($wizard['items'])
                                                && is_array($wizard['items'])
                                            ) {
                                                $typeArray['columnsOverrides'][$fieldName]['config']['valuePicker'] = $wizard;
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'][$wizardName]);
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['valuePicker']['type']);
                                                $this->messages[] = 'The select wizard in columnsOverrides using \'type\' = \'select\' has been migrated'
                                                    . ' from TCA ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                    . '[\'wizards\'][\'' . $wizardName . '\'] to ' . $table
                                                    . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'valuePicker\']';
                                            }
                                        }
                                        if (empty($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])) {
                                            unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $tca;
    }

    /**
     * Move type=input with select wizard to config['valuePicker']
     *
     * @param array $tca
     * @return array Migrated TCA
     */
    protected function migrateSliderWizardToSliderConfiguration(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && $fieldConfig['config']['type'] === 'input') {
                        if (isset($fieldConfig['config']['wizards'])
                            && is_array($fieldConfig['config']['wizards'])) {
                            foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                                if (isset($wizardConfig['type']) && $wizardConfig['type'] === 'slider') {
                                    $fieldConfig['config']['slider'] = [];
                                    if (isset($wizardConfig['width'])) {
                                        $fieldConfig['config']['slider']['width'] = $wizardConfig['width'];
                                    }
                                    if (isset($wizardConfig['step'])) {
                                        $fieldConfig['config']['slider']['step'] = $wizardConfig['step'];
                                    }
                                    unset($fieldConfig['config']['wizards'][$wizardName]);
                                    $this->messages[] = 'The slider wizard in TCA '
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']'
                                        . ' has been migrated to '
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'slider\'].';
                                }
                            }
                        }
                        if (empty($fieldConfig['config']['wizards'])) {
                            unset($fieldConfig['config']['wizards']);
                        }
                        if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                            foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                    if (isset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                        && is_array($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                    ) {
                                        foreach ($typeArray['columnsOverrides'][$fieldName]['config']['wizards'] as $wizardName => $wizard) {
                                            if (isset($wizard['type']) && ($wizard['type'] === 'slider')) {
                                                $typeArray['columnsOverrides'][$fieldName]['config']['slider'] = $wizard;
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'][$wizardName]);
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['slider']['type']);
                                                $this->messages[] = 'The slider wizard in columnsOverrides using \'type\' = \'slider\' has been migrated'
                                                    . ' from TCA ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                    . '[\'wizards\'][\'' . $wizardName . '\'] to ' . $table
                                                    . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'slider\']';
                                            }
                                        }
                                        if (empty($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])) {
                                            unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $tca;
    }

    /**
     * Move type=input fields that have a "link" wizard to an own renderType with fieldControl
     *
     * @param array $tca
     * @return array Modified TCA
     */
    protected function migrateLinkWizardToRenderTypeAndFieldControl(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && $fieldConfig['config']['type'] === 'input'
                        && !isset($fieldConfig['config']['renderType'])
                    ) {
                        if (isset($fieldConfig['config']['wizards'])
                            && is_array($fieldConfig['config']['wizards'])) {
                            foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                                if (isset($wizardConfig['type'])
                                    && $wizardConfig['type'] === 'popup'
                                    && isset($wizardConfig['module']['name'])
                                    && $wizardConfig['module']['name'] === 'wizard_link'
                                ) {
                                    $fieldConfig['config']['renderType'] = 'inputLink';
                                    if (isset($wizardConfig['title'])) {
                                        $fieldConfig['config']['fieldControl']['linkPopup']['options']['title'] = $wizardConfig['title'];
                                    }
                                    if (isset($wizardConfig['JSopenParams'])) {
                                        $fieldConfig['config']['fieldControl']['linkPopup']['options']['windowOpenParameters']
                                            = $wizardConfig['JSopenParams'];
                                    }
                                    if (isset($wizardConfig['params']['blindLinkOptions'])) {
                                        $fieldConfig['config']['fieldControl']['linkPopup']['options']['blindLinkOptions']
                                            = $wizardConfig['params']['blindLinkOptions'];
                                    }
                                    if (isset($wizardConfig['params']['blindLinkFields'])) {
                                        $fieldConfig['config']['fieldControl']['linkPopup']['options']['blindLinkFields']
                                            = $wizardConfig['params']['blindLinkFields'];
                                    }
                                    if (isset($wizardConfig['params']['allowedExtensions'])) {
                                        $fieldConfig['config']['fieldControl']['linkPopup']['options']['allowedExtensions']
                                            = $wizardConfig['params']['allowedExtensions'];
                                    }
                                    unset($fieldConfig['config']['wizards'][$wizardName]);
                                    $this->messages[] = 'The link wizard has been migrated to a \'renderType\' => \'inputLink \'. '
                                        . 'It has been migrated from TCA table "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']" to "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'renderType\'] = \'inputLink\'".';
                                }
                            }
                        }
                        if (empty($fieldConfig['config']['wizards'])) {
                            unset($fieldConfig['config']['wizards']);
                        }
                        if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                            foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                    if (isset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                        && is_array($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                    ) {
                                        foreach ($typeArray['columnsOverrides'][$fieldName]['config']['wizards'] as $wizardName => $wizard) {
                                            if (isset($wizard['type'])
                                                && $wizard['type'] === 'popup'
                                                && isset($wizard['module']['name'])
                                                && $wizard['module']['name'] === 'wizard_link'
                                            ) {
                                                $typeArray['columnsOverrides'][$fieldName]['config']['renderType'] = 'inputLink';
                                                if (isset($wizard['title'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['linkPopup']['options']['title'] = $wizard['title'];
                                                }
                                                if (isset($wizard['JSopenParams'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['linkPopup']['options']['windowOpenParameters']
                                                        = $wizard['JSopenParams'];
                                                }
                                                if (isset($wizard['params']['blindLinkOptions'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['linkPopup']['options']['blindLinkOptions']
                                                        = $wizard['params']['blindLinkOptions'];
                                                }
                                                if (isset($wizard['params']['blindLinkFields'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['linkPopup']['options']['blindLinkFields']
                                                        = $wizard['params']['blindLinkFields'];
                                                }
                                                if (isset($wizard['params']['allowedExtensions'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['linkPopup']['options']['allowedExtensions']
                                                        = $wizard['params']['allowedExtensions'];
                                                }
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'][$wizardName]);
                                                $this->messages[] = 'The link wizard in columnsOverrides using \'type\' = \'popup\' has been migrated to a \'renderType\' = \'inputLink\'.'
                                                    . ' It has been migrated from TCA "' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                    . '[\'wizards\'][\'' . $wizardName . '\']" to "' . $table
                                                    . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'renderType\'] = \'inputLink\'"';
                                            }
                                        }
                                        if (empty($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])) {
                                            unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Find select and group fields with enabled edit wizard and migrate to "fieldControl"
     *
     * @param array $tca
     * @return array
     */
    protected function migrateEditWizardToFieldControl(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && ($fieldConfig['config']['type'] === 'group'
                        || $fieldConfig['config']['type'] === 'select')
                    ) {
                        if (isset($fieldConfig['config']['wizards'])
                            && is_array($fieldConfig['config']['wizards'])
                        ) {
                            foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                                if (isset($wizardConfig['type'])
                                    && $wizardConfig['type'] === 'popup'
                                    && isset($wizardConfig['module']['name'])
                                    && $wizardConfig['module']['name'] === 'wizard_edit'
                                    && !isset($fieldConfig['config']['fieldControl']['editPopup'])
                                ) {
                                    $fieldConfig['config']['fieldControl']['editPopup']['disabled'] = false;
                                    if (isset($wizardConfig['title'])) {
                                        $fieldConfig['config']['fieldControl']['editPopup']['options']['title'] = $wizardConfig['title'];
                                    }
                                    if (isset($wizardConfig['JSopenParams'])) {
                                        $fieldConfig['config']['fieldControl']['editPopup']['options']['windowOpenParameters']
                                            = $wizardConfig['JSopenParams'];
                                    }
                                    unset($fieldConfig['config']['wizards'][$wizardName]);
                                    $this->messages[] = 'The edit wizard in TCA  has been migrated to a \'fieldControl\' = \'editPopup\' element.'
                                        . ' It has been migrated from TCA "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']" to "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'fieldControl\']=\'editPopup\'".';
                                }
                            }
                        }
                        if (empty($fieldConfig['config']['wizards'])) {
                            unset($fieldConfig['config']['wizards']);
                        }
                        if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                            foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                    if (isset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                        && is_array($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                    ) {
                                        foreach ($typeArray['columnsOverrides'][$fieldName]['config']['wizards'] as $wizardName => $wizard) {
                                            if (isset($wizard['type'])
                                                && $wizard['type'] === 'popup'
                                                && isset($wizard['module']['name'])
                                                && $wizard['module']['name'] === 'wizard_edit'
                                            ) {
                                                $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['editPopup']['disabled'] = false;
                                                if (isset($wizard['title'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['editPopup']['options']['title']
                                                        = $wizard['title'];
                                                }
                                                if (isset($wizard['JSopenParams'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['editPopup']['options']['windowOpenParameters']
                                                        = $wizard['JSopenParams'];
                                                }
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'][$wizardName]);
                                                $this->messages[] = 'The edit wizard in columnsOverrides using \'type\' = \'popup\' has been migrated to a \'fieldControl\' = \'editPopup\' element.'
                                                    . ' It has been migrated from TCA "'
                                                    . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                    . '[\'wizards\'][\'' . $wizardName . '\']" , to "'
                                                    . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'fieldControl\']=\'editPopup\'".';
                                            }
                                        }
                                        if (empty($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])) {
                                            unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Find select and group fields with enabled add wizard and migrate to "fieldControl"
     *
     * @param array $tca
     * @return array
     */
    protected function migrateAddWizardToFieldControl(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && ($fieldConfig['config']['type'] === 'group'
                        || $fieldConfig['config']['type'] === 'select')
                    ) {
                        if (isset($fieldConfig['config']['wizards'])
                            && is_array($fieldConfig['config']['wizards'])
                        ) {
                            foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                                if (isset($wizardConfig['type'])
                                    && $wizardConfig['type'] === 'script'
                                    && isset($wizardConfig['module']['name'])
                                    && $wizardConfig['module']['name'] === 'wizard_add'
                                    && !isset($fieldConfig['config']['fieldControl']['addRecord'])
                                ) {
                                    $fieldConfig['config']['fieldControl']['addRecord']['disabled'] = false;
                                    if (isset($wizardConfig['title'])) {
                                        $fieldConfig['config']['fieldControl']['addRecord']['options']['title'] = $wizardConfig['title'];
                                    }
                                    if (isset($wizardConfig['params']['table'])) {
                                        $fieldConfig['config']['fieldControl']['addRecord']['options']['table']
                                            = $wizardConfig['params']['table'];
                                    }
                                    if (isset($wizardConfig['params']['pid'])) {
                                        $fieldConfig['config']['fieldControl']['addRecord']['options']['pid']
                                            = $wizardConfig['params']['pid'];
                                    }
                                    if (isset($wizardConfig['params']['setValue'])) {
                                        $fieldConfig['config']['fieldControl']['addRecord']['options']['setValue']
                                            = $wizardConfig['params']['setValue'];
                                    }
                                    unset($fieldConfig['config']['wizards'][$wizardName]);
                                    $this->messages[] = 'The add wizard in TCA has been migrated to a \'fieldControl\' = \'addRecord\' element.'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']'
                                        . ' It has been migrated from TCA "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'fieldControl\']=\'addRecord\'.';
                                }
                            }
                        }
                        if (empty($fieldConfig['config']['wizards'])) {
                            unset($fieldConfig['config']['wizards']);
                        }
                        if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                            foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                    if (isset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                        && is_array($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                    ) {
                                        foreach ($typeArray['columnsOverrides'][$fieldName]['config']['wizards'] as $wizardName => $wizard) {
                                            if (isset($wizard['type'])
                                                && $wizard['type'] === 'script'
                                                && isset($wizard['module']['name'])
                                                && $wizard['module']['name'] === 'wizard_add'
                                            ) {
                                                $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['addRecord']['disabled'] = false;
                                                if (isset($wizard['title'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['addRecord']['options']['title']
                                                        = $wizard['title'];
                                                }
                                                if (isset($wizard['params']['table'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['addRecord']['options']['table']
                                                        = $wizard['params']['table'];
                                                }
                                                if (isset($wizard['params']['pid'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['addRecord']['options']['pid']
                                                        = $wizard['params']['pid'];
                                                }
                                                if (isset($wizard['params']['setValue'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['addRecord']['options']['setValue']
                                                        = $wizard['params']['setValue'];
                                                }
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'][$wizardName]);
                                                $this->messages[] = 'The add wizard in columnsOverrides using \'type\' = \'script\' has been migrated to a \'fieldControl\' = \'addRecord\' element.'
                                                    . ' It has been migrated from TCA "'
                                                    . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                    . '[\'wizards\'][\'' . $wizardName . '\']"  to "'
                                                    . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'fieldControl\'=\'addRecord\'".';
                                            }
                                        }
                                        if (empty($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])) {
                                            unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Find select and group fields with enabled list wizard and migrate to "fieldControl"
     *
     * @param array $tca
     * @return array
     */
    protected function migrateListWizardToFieldControl(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && ($fieldConfig['config']['type'] === 'group'
                        || $fieldConfig['config']['type'] === 'select')
                    ) {
                        if (isset($fieldConfig['config']['wizards'])
                            && is_array($fieldConfig['config']['wizards'])
                        ) {
                            foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                                if (isset($wizardConfig['type'])
                                    && $wizardConfig['type'] === 'script'
                                    && isset($wizardConfig['module']['name'])
                                    && $wizardConfig['module']['name'] === 'wizard_list'
                                    && !isset($fieldConfig['config']['fieldControl']['listModule'])
                                ) {
                                    $fieldConfig['config']['fieldControl']['listModule']['disabled'] = false;
                                    if (isset($wizardConfig['title'])) {
                                        $fieldConfig['config']['fieldControl']['listModule']['options']['title'] = $wizardConfig['title'];
                                    }
                                    if (isset($wizardConfig['params']['table'])) {
                                        $fieldConfig['config']['fieldControl']['listModule']['options']['table']
                                            = $wizardConfig['params']['table'];
                                    }
                                    if (isset($wizardConfig['params']['pid'])) {
                                        $fieldConfig['config']['fieldControl']['listModule']['options']['pid']
                                            = $wizardConfig['params']['pid'];
                                    }
                                    unset($fieldConfig['config']['wizards'][$wizardName]);
                                    $this->messages[] = 'The list wizard in TCA has been migrated to a \'fieldControl\' = \'listModule\' element.'
                                        . ' It has been migrated from TCA "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']'
                                        . '" to "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'fieldControl\'=\'listModule\'".';
                                }
                            }
                        }
                        if (empty($fieldConfig['config']['wizards'])) {
                            unset($fieldConfig['config']['wizards']);
                        }
                        if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                            foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                    if (isset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                        && is_array($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                    ) {
                                        foreach ($typeArray['columnsOverrides'][$fieldName]['config']['wizards'] as $wizardName => $wizard) {
                                            if (isset($wizard['type'])
                                                && $wizard['type'] === 'script'
                                                && isset($wizard['module']['name'])
                                                && $wizard['module']['name'] === 'wizard_list'
                                            ) {
                                                $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['listModule']['disabled'] = false;
                                                if (isset($wizard['title'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['listModule']['options']['title']
                                                        = $wizard['title'];
                                                }
                                                if (isset($wizard['params']['table'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['listModule']['options']['table']
                                                        = $wizard['params']['table'];
                                                }
                                                if (isset($wizard['params']['pid'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['listModule']['options']['pid']
                                                        = $wizard['params']['pid'];
                                                }
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'][$wizardName]);
                                                $this->messages[] = 'The list wizard in columnsOverrides using \'type\' = \'script\' has been migrated to a \'fieldControl\' = \'listModule\' element.'
                                                    . ' It has been migrated from TCA "'
                                                    . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                    . '[\'wizards\'][\'' . $wizardName . '\']" to "'
                                                    . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'fieldControl\'=\'listModule\'".';
                                            }
                                        }
                                        if (empty($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])) {
                                            unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrate defaultExtras "nowrap", "enable-tab", "fixed-font". Then drop all
     * remaining "defaultExtras", there shouldn't exist anymore.
     *
     * @param array $tca
     * @return array
     */
    protected function migrateLastPiecesOfDefaultExtras(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['defaultExtras'])) {
                        $defaultExtrasArray = GeneralUtility::trimExplode(':', $fieldConfig['defaultExtras'], true);
                        foreach ($defaultExtrasArray as $defaultExtrasSetting) {
                            if ($defaultExtrasSetting === 'rte_only') {
                                $this->messages[] = 'The defaultExtras setting \'rte_only\' in TCA table '
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'] has been dropped, the setting'
                                    . ' is no longer supported';
                                continue;
                            }
                            if ($defaultExtrasSetting === 'nowrap') {
                                $fieldConfig['config']['wrap'] = 'off';
                                $this->messages[] = 'The defaultExtras setting \'nowrap\' in TCA table '
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'] has been migrated to TCA table '
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wrap\'] = \'off\'';
                            } elseif ($defaultExtrasSetting === 'enable-tab') {
                                $fieldConfig['config']['enableTabulator'] = true;
                                $this->messages[] = 'The defaultExtras setting \'enable-tab\' in TCA table '
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'] has been migrated to TCA table '
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'enableTabulator\'] = true';
                            } elseif ($defaultExtrasSetting === 'fixed-font') {
                                $fieldConfig['config']['fixedFont'] = true;
                                $this->messages[] = 'The defaultExtras setting \'fixed-font\' in TCA table '
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'] has been migrated to TCA table '
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'fixedFont\'] = true';
                            } else {
                                $this->messages[] = 'The defaultExtras setting \'' . $defaultExtrasSetting . '\' in TCA table '
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'] is unknown and has been dropped.';
                            }
                        }
                        unset($fieldConfig['defaultExtras']);
                    }
                }
            }
            if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                    if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                        foreach ($typeArray['columnsOverrides'] as $fieldName => &$overrideConfig) {
                            if (!isset($overrideConfig['defaultExtras'])) {
                                continue;
                            }
                            $defaultExtrasArray = GeneralUtility::trimExplode(':', $overrideConfig['defaultExtras'], true);
                            foreach ($defaultExtrasArray as $defaultExtrasSetting) {
                                if ($defaultExtrasSetting === 'rte_only') {
                                    $this->messages[] = 'The defaultExtras setting \'rte_only\' in TCA table '
                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\']'
                                        . ' has been dropped, the setting is no longer supported';
                                    continue;
                                }
                                if ($defaultExtrasSetting === 'nowrap') {
                                    $overrideConfig['config']['wrap'] = 'off';
                                    $this->messages[] = 'The defaultExtras setting \'nowrap\' in TCA table '
                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\']'
                                        . ' has been migrated to TCA table '
                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'config\'][\'wrap\'] = \'off\'';
                                } elseif ($defaultExtrasSetting === 'enable-tab') {
                                    $overrideConfig['config']['enableTabulator'] = true;
                                    $this->messages[] = 'The defaultExtras setting \'enable-tab\' in TCA table '
                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\']'
                                        . ' has been migrated to TCA table '
                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'config\'][\'enableTabulator\'] = true';
                                } elseif ($defaultExtrasSetting === 'fixed-font') {
                                    $overrideConfig['config']['fixedFont'] = true;
                                    $this->messages[] = 'The defaultExtras setting \'fixed-font\' in TCA table '
                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\']'
                                        . ' has been migrated to TCA table '
                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'config\'][\'fixedFont\'] = true';
                                } else {
                                    $this->messages[] = 'The defaultExtras setting \'' . $defaultExtrasSetting . '\' in TCA table '
                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\']'
                                        . ' is unknown and has been dropped.';
                                }
                            }
                            unset($overrideConfig['defaultExtras']);
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrate wizard_table script to renderType="textTable" with options in fieldControl
     *
     * @param array $tca
     * @return array
     */
    protected function migrateTableWizardToRenderType(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && $fieldConfig['config']['type'] === 'text') {
                        if (isset($fieldConfig['config']['wizards'])
                            && is_array($fieldConfig['config']['wizards'])
                        ) {
                            foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                                if (isset($wizardConfig['type'])
                                    && $wizardConfig['type'] === 'script'
                                    && isset($wizardConfig['module']['name'])
                                    && $wizardConfig['module']['name'] === 'wizard_table'
                                    && !isset($fieldConfig['config']['fieldControl']['tableWizard'])
                                    && !isset($fieldConfig['config']['renderType'])
                                ) {
                                    $fieldConfig['config']['renderType'] = 'textTable';
                                    if (isset($wizardConfig['title'])) {
                                        $fieldConfig['config']['fieldControl']['tableWizard']['options']['title'] = $wizardConfig['title'];
                                    }
                                    if (isset($wizardConfig['params']['xmlOutput']) && (int)$wizardConfig['params']['xmlOutput'] !== 0) {
                                        $fieldConfig['config']['fieldControl']['tableWizard']['options']['xmlOutput']
                                            = (int)$wizardConfig['params']['xmlOutput'];
                                    }
                                    if (isset($wizardConfig['params']['numNewRows'])) {
                                        $fieldConfig['config']['fieldControl']['tableWizard']['options']['numNewRows']
                                            = $wizardConfig['params']['numNewRows'];
                                    }
                                    unset($fieldConfig['config']['wizards'][$wizardName]);
                                    $this->messages[] = 'The table wizard in TCA has been migrated to a \'renderType\' = \'textTable\'.'
                                        . ' It has been migrated from TCA "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']'
                                        . '" to "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'renderType\'] = \'textTable\'".';
                                }
                            }
                        }
                        if (empty($fieldConfig['config']['wizards'])) {
                            unset($fieldConfig['config']['wizards']);
                        }
                        if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                            foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                    if (isset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                        && is_array($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                    ) {
                                        foreach ($typeArray['columnsOverrides'][$fieldName]['config']['wizards'] as $wizardName => $wizard) {
                                            if (isset($wizard['type'])
                                                && $wizard['type'] === 'script'
                                                && isset($wizard['module']['name'])
                                                && $wizard['module']['name'] === 'wizard_table'
                                                && !isset($typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['tableWizard'])
                                                && !isset($typeArray['columnsOverrides'][$fieldName]['config']['renderType'])
                                            ) {
                                                $typeArray['columnsOverrides'][$fieldName]['config']['renderType'] = 'textTable';
                                                if (isset($wizard['title'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['tableWizard']['options']['title']
                                                        = $wizard['title'];
                                                }
                                                if (isset($wizard['params']['xmlOutput']) && (int)$wizard['params']['xmlOutput'] !== 0) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['tableWizard']['options']['xmlOutput']
                                                        = (int)$wizard['params']['xmlOutput'];
                                                }
                                                if (isset($wizard['params']['numNewRows'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['tableWizard']['options']['numNewRows']
                                                        = $wizard['params']['numNewRows'];
                                                }
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'][$wizardName]);
                                                $this->messages[] = 'The table wizard in columnsOverrides using \'type\' = \'script\' has been migrated to a \'renderType\' = \'textTable\'.'
                                                    . ' It has been migrated from TCA "'
                                                    . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                    . '[\'wizards\'][\'' . $wizardName . '\']"  to "'
                                                    . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'renderType\'] = \'textTable\'".';
                                            }
                                        }
                                        if (empty($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])) {
                                            unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrate "wizard_rte" wizards to rtehtmlarea fieldControl
     *
     * @param array $tca
     * @return array
     */
    protected function migrateFullScreenRichtextToFieldControl(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && $fieldConfig['config']['type'] === 'text') {
                        if (isset($fieldConfig['config']['wizards'])
                            && is_array($fieldConfig['config']['wizards'])
                        ) {
                            foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                                if (isset($wizardConfig['type'])
                                    && $wizardConfig['type'] === 'script'
                                    && isset($wizardConfig['module']['name'])
                                    && $wizardConfig['module']['name'] === 'wizard_rte'
                                    && !isset($fieldConfig['config']['fieldControl']['fullScreenRichtext'])
                                    && isset($fieldConfig['config']['enableRichtext'])
                                    && (bool)$fieldConfig['config']['enableRichtext'] === true
                                ) {
                                    // Field is configured for richtext, so enable the full screen wizard
                                    $fieldConfig['config']['fieldControl']['fullScreenRichtext']['disabled'] = false;
                                    if (isset($wizardConfig['title'])) {
                                        $fieldConfig['config']['fieldControl']['fullScreenRichtext']['options']['title'] = $wizardConfig['title'];
                                    }
                                    unset($fieldConfig['config']['wizards'][$wizardName]);
                                    $this->messages[] = 'The RTE fullscreen wizard in TCA has been migrated to a \'fieldControl\' = \'fullScreenRichtext\'.'
                                        . ' It has been migrated from TCA "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']'
                                        . '" to "'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'fieldControl\']=\'fullScreenRichtext\'".';
                                } elseif (isset($wizardConfig['type'])
                                    && $wizardConfig['type'] === 'script'
                                    && isset($wizardConfig['module']['name'])
                                    && $wizardConfig['module']['name'] === 'wizard_rte'
                                    && !isset($fieldConfig['config']['fieldControl']['fullScreenRichtext'])
                                    && (
                                        !isset($fieldConfig['config']['enableRichtext'])
                                        || isset($fieldConfig['config']['enableRichtext']) && (bool)$fieldConfig['config']['enableRichtext'] === false
                                    )
                                ) {
                                    // Wizard is given, but field is not configured for richtext
                                    // Find types that enableRichtext and enable full screen for those types
                                    if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                                        foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                            if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                                if (isset($typeArray['columnsOverrides'][$fieldName]['config']['enableRichtext'])
                                                    && (bool)$typeArray['columnsOverrides'][$fieldName]['config']['enableRichtext'] === true
                                                ) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['fullScreenRichtext']['disabled'] = false;
                                                    if (isset($wizardConfig['title'])) {
                                                        $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['fullScreenRichtext']['options']['title']
                                                            = $wizardConfig['title'];
                                                    }
                                                    $this->messages[] = 'The RTE fullscreen wizard in TCA has been migrated to a \'fieldControl\' = \'fullScreenRichtext\'.'
                                                        . ' It has been migrated from TCA "'
                                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']'
                                                        . '" to "'
                                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'fieldControl\']=\'fullScreenRichtext\'';
                                                }
                                            }
                                        }
                                    }
                                    unset($fieldConfig['config']['wizards'][$wizardName]);
                                }
                            }
                        }
                        if (empty($fieldConfig['config']['wizards'])) {
                            unset($fieldConfig['config']['wizards']);
                        }
                        if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                            foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                    if (isset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                        && is_array($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                    ) {
                                        foreach ($typeArray['columnsOverrides'][$fieldName]['config']['wizards'] as $wizardName => $wizard) {
                                            if (isset($wizard['type'])
                                                && $wizard['type'] === 'script'
                                                && isset($wizard['module']['name'])
                                                && $wizard['module']['name'] === 'wizard_rte'
                                                && !isset($typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['fullScreenRichtext'])
                                            ) {
                                                $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['fullScreenRichtext']['disabled'] = false;
                                                if (isset($wizard['title'])) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['fieldControl']['fullScreenRichtext']['options']['title']
                                                        = $wizard['title'];
                                                }
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'][$wizardName]);
                                                $this->messages[] = 'The RTE fullscreen wizard in columnsOverrides using \'type\' = \'script\' has been migrated to a \'fieldControl\' = \'fullScreenRichtext\'.'
                                                    . ' It has been migrated from TCA "'
                                                    . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                    . '[\'wizards\'][\'' . $wizardName . '\']" to "'
                                                    . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'fieldControl\']=\'fullScreenRichtext\'".';
                                            }
                                        }
                                        if (empty($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])) {
                                            unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrate the "suggest" wizard in type=group to "hideSuggest" and "suggestOptions"
     *
     * @param array $tca Given TCA
     * @return array Modified TCA
     */
    protected function migrateSuggestWizardTypeGroup(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && ($fieldConfig['config']['type'] === 'group'
                        && isset($fieldConfig['config']['internal_type'])
                        && $fieldConfig['config']['internal_type'] === 'db')
                    ) {
                        if (isset($fieldConfig['config']['hideSuggest'])) {
                            continue;
                        }
                        if (isset($fieldConfig['config']['wizards']) && is_array($fieldConfig['config']['wizards'])) {
                            foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
                                if (isset($wizardConfig['type']) && $wizardConfig['type'] === 'suggest') {
                                    unset($wizardConfig['type']);
                                    if (!empty($wizardConfig)) {
                                        $fieldConfig['config']['suggestOptions'] = $wizardConfig;
                                        $this->messages[] = 'The suggest wizard options in TCA '
                                            . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']'
                                            . ' have been migrated to '
                                            . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'suggestOptions\'].';
                                    } else {
                                        $this->messages[] = 'The suggest wizard in TCA '
                                            . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'wizards\'][\'' . $wizardName . '\']'
                                            . ' is enabled by default and has been removed.';
                                    }
                                    unset($fieldConfig['config']['wizards'][$wizardName]);
                                }
                            }
                        }
                        if (empty($fieldConfig['config']['wizards'])) {
                            unset($fieldConfig['config']['wizards']);
                        }
                        if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                            foreach ($tableDefinition['types'] as $typeName => &$typeArray) {
                                if (isset($typeArray['columnsOverrides']) && is_array($typeArray['columnsOverrides'])) {
                                    if (isset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                        && is_array($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])
                                    ) {
                                        foreach ($typeArray['columnsOverrides'][$fieldName]['config']['wizards'] as $wizardName => $wizard) {
                                            if (isset($wizard['type']) && $wizard['type'] === 'suggest'
                                            ) {
                                                unset($wizard['type']);
                                                $fieldConfig['config']['hideSuggest'] = true;
                                                $typeArray['columnsOverrides'][$fieldName]['config']['hideSuggest'] = false;
                                                if (!empty($wizard)) {
                                                    $typeArray['columnsOverrides'][$fieldName]['config']['suggestOptions'] = $wizard;
                                                    $this->messages[] = 'The suggest wizard options in columnsOverrides have been migrated'
                                                        . ' from TCA ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                        . '[\'wizards\'][\'' . $wizardName . '\'] to \'suggestOptions\' in '
                                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']';
                                                } else {
                                                    $this->messages[] = 'The suggest wizard in columnsOverrides has been migrated'
                                                        . ' from TCA ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\']'
                                                        . '[\'wizards\'][\'' . $wizardName . '\'] to \'hideSuggest\' => false in '
                                                        . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'config\'][\'hideSuggest\']';
                                                }
                                                unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards'][$wizardName]);
                                            }
                                        }
                                        if (empty($typeArray['columnsOverrides'][$fieldName]['config']['wizards'])) {
                                            unset($typeArray['columnsOverrides'][$fieldName]['config']['wizards']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrate some detail options of type=group config
     *
     * @param array $tca Given TCA
     * @return array Modified TCA
     */
    protected function migrateOptionsOfTypeGroup(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && $fieldConfig['config']['type'] === 'group') {
                        if (isset($fieldConfig['config']['selectedListStyle'])) {
                            unset($fieldConfig['config']['selectedListStyle']);
                            $this->messages[] = 'The \'type\' = \'group\' option \'selectedListStyle\' is obsolete and has been dropped'
                                . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']';
                        }
                        if (isset($fieldConfig['config']['show_thumbs'])) {
                            if ((bool)$fieldConfig['config']['show_thumbs'] === false && $fieldConfig['config']['internal_type'] === 'db') {
                                $fieldConfig['config']['fieldWizard']['recordsOverview']['disabled'] = true;
                                $this->messages[] = 'The \'type\' = \'group\' option \'show_thumbs\' = false is obsolete'
                                    . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                    . ' and has been migrated to'
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'fieldWizard\'][\'recordsOverview\'][\'disabled\'] = true';
                            } elseif ((bool)$fieldConfig['config']['show_thumbs'] === false && $fieldConfig['config']['internal_type'] === 'file') {
                                $fieldConfig['config']['fieldWizard']['fileThumbnails']['disabled'] = true;
                                $this->messages[] = 'The \'type\' = \'group\' option \'show_thumbs\' = false is obsolete'
                                    . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                    . ' and has been migrated to'
                                    . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'fieldWizard\'][\'fileThumbnails\'][\'disabled\'] = true';
                            } else {
                                $this->messages[] = 'The \'type\' = \'group\' option \'show_thumbs\' is obsolete and has been dropped'
                                    . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']';
                            }
                            unset($fieldConfig['config']['show_thumbs']);
                        }
                        if (isset($fieldConfig['config']['disable_controls']) && is_string($fieldConfig['config']['disable_controls'])) {
                            $controls = GeneralUtility::trimExplode(',', $fieldConfig['config']['disable_controls'], true);
                            foreach ($controls as $control) {
                                if ($control === 'browser') {
                                    $fieldConfig['config']['fieldControl']['elementBrowser']['disabled'] = true;
                                    $this->messages[] = 'The \'type\' = \'group\' option \'disable_controls\' = \'browser\''
                                        . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                        . ' and has been migrated to'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'fieldControl\'][\'elementBrowser\'][\'disabled\'] = true';
                                } elseif ($control === 'delete') {
                                    $this->messages[] = 'The \'type\' = \'group\' option \'disable_controls\' = \'delete\''
                                        . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                        . ' and has been migrated to'
                                        . $table . '[\'columns\'][\' . $fieldName . \'][\'config\'][\'hideDeleteIcon\'] = true';
                                    $fieldConfig['config']['hideDeleteIcon'] = true;
                                } elseif ($control === 'allowedTables') {
                                    $fieldConfig['config']['fieldWizard']['tableList']['disabled'] = true;
                                    $this->messages[] = 'The \'type\' = \'group\' option \'disable_controls\' = \'allowedTables\''
                                        . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                        . ' and has been migrated to'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'fieldWizard\'][\'tableList\'][\'disabled\'] = true';
                                } elseif ($control === 'upload') {
                                    $fieldConfig['config']['fieldWizard']['fileUpload']['disabled'] = true;
                                    $this->messages[] = 'The \'type\' = \'group\' option \'disable_controls\' = \'upload\''
                                        . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                        . ' and has been migrated to'
                                        . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'fieldWizard\'][\'fileUpload\'][\'disabled\'] = true';
                                }
                            }
                            unset($fieldConfig['config']['disable_controls']);
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrate "showIconTable" to a field wizard, drop selicon_cols
     *
     * @param array $tca Given TCA
     * @return array Modified TCA
     */
    protected function migrateSelectShowIconTable(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && ($fieldConfig['config']['type'] === 'select'
                        && isset($fieldConfig['config']['renderType'])
                        && $fieldConfig['config']['renderType'] === 'selectSingle')
                    ) {
                        if (isset($fieldConfig['config']['showIconTable'])) {
                            if ((bool)$fieldConfig['config']['showIconTable'] === true) {
                                $fieldConfig['config']['fieldWizard']['selectIcons']['disabled'] = false;
                                $this->messages[] = 'The \'type\' = \'select\' option \'showIconTable\' = true is obsolete'
                                    . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                    . ' and has been migrated to'
                                    . ' [\'config\'][\'fieldWizard\'][\'selectIcons\'][\'disabled\'] = false';
                            } else {
                                $this->messages[] = 'The \'type\' = \'group\' option \'showIconTable\' = false is obsolete'
                                    . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                    . ' and has been removed.';
                            }
                            unset($fieldConfig['config']['showIconTable']);
                        }
                        if (isset($fieldConfig['config']['selicon_cols'])) {
                            unset($fieldConfig['config']['selicon_cols']);
                            $this->messages[] = 'The \'type\' = \'group\' option \'selicon_cols\' = false is obsolete'
                                . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']'
                                . ' and has been removed.';
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrate imageManipulation "ratio" config to new "cropVariant" config
     *
     * @param array $tca
     * @return array
     */
    protected function migrateImageManipulationConfig(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && $fieldConfig['config']['type'] === 'imageManipulation') {
                        if (isset($fieldConfig['config']['enableZoom'])) {
                            unset($fieldConfig['config']['enableZoom']);
                            $this->messages[] = sprintf(
                                'The config option "enableZoom" has been removed from TCA type "imageManipulation" in table "%s" and field "%s"',
                                $table,
                                $fieldName
                            );
                        }
                        if (isset($fieldConfig['config']['ratios'])) {
                            $legacyRatios = $fieldConfig['config']['ratios'];
                            unset($fieldConfig['config']['ratios']);
                            if (isset($fieldConfig['config']['cropVariants'])) {
                                $this->messages[] = sprintf(
                                    'The config option "ratios" has been deprecated and cannot be used together with the option "cropVariants" in table "%s" and field "%s"',
                                    $table,
                                    $fieldName
                                );
                                continue;
                            }
                            $fieldConfig['config']['cropVariants']['default'] = [
                                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default',
                                'allowedAspectRatios' => [],
                                'cropArea' => [
                                    'x' => 0.0,
                                    'y' => 0.0,
                                    'width' => 1.0,
                                    'height' => 1.0,
                                ],
                            ];
                            foreach ($legacyRatios as $ratio => $ratioLabel) {
                                $ratio = (float)$ratio;
                                $ratioId = number_format($ratio, 2);
                                $fieldConfig['config']['cropVariants']['default']['allowedAspectRatios'][$ratioId] = [
                                    'title' => $ratioLabel,
                                    'value' => $ratio,
                                ];
                            }
                            $this->messages[] = sprintf(
                                'Migrated config option "ratios" of type "imageManipulation" to option "cropVariants" in table "%s" and field "%s"',
                                $table,
                                $fieldName
                            );
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrate 'max' for renderType='inputDateTime'
     *
     * @param array $tca
     * @return array
     */
    protected function migrateinputDateTimeMax(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['renderType']) && $fieldConfig['config']['renderType'] === 'inputDateTime') {
                        if (isset($fieldConfig['config']['max'])) {
                            unset($fieldConfig['config']['max']);
                            $this->messages[] = 'The config option \'max\' has been removed from the TCA for renderType=\'inputDateTime\' in ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'max\']';
                        }
                    }
                }
            }
        }

        return $tca;
    }

    /**
     * Migrate type='inline' properties 'foreign_types', 'foreign_selector_fieldTcaOverride'
     * and 'foreign_record_defaults' to 'overrideChildTca'
     *
     * @param array $tca
     * @return array
     */
    protected function migrateInlineOverrideChildTca(array $tca): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (isset($tableDefinition['types']) && is_array($tableDefinition['types'])) {
                foreach ($tableDefinition['types'] as $typeName => &$typeConfig) {
                    if (!isset($typeConfig['columnsOverrides']) || !is_array($typeConfig['columnsOverrides'])) {
                        continue;
                    }
                    foreach ($typeConfig['columnsOverrides'] as $fieldName => &$fieldConfig) {
                        if (isset($fieldConfig['config']['overrideChildTca'])
                            || (isset($fieldConfig['config']['type']) && $fieldConfig['config']['type'] !== 'inline')
                            || (!isset($fieldConfig['config']['type']) && (empty($tca[$table]['columns'][$fieldName]['config']['type']) || $tca[$table]['columns'][$fieldName]['config']['type'] !== 'inline'))
                        ) {
                            // The new config is either set intentionally for compatibility
                            // or accidentally. In any case we keep the new config and skip the migration.
                            continue;
                        }
                        if (isset($fieldConfig['config']['foreign_types']) && is_array($fieldConfig['config']['foreign_types'])) {
                            $fieldConfig['config']['overrideChildTca']['types'] = $fieldConfig['config']['foreign_types'];
                            unset($fieldConfig['config']['foreign_types']);
                            $this->messages[] = 'The \'foreign_types\' property from TCA ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'config\']  has been migrated to ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'config\'][\'overrideChildTca\'][\'types\']';
                        }
                        if (isset($fieldConfig['config']['foreign_selector_fieldTcaOverride']) && is_array($fieldConfig['config']['foreign_selector_fieldTcaOverride'])) {
                            $foreignSelectorFieldName = '';
                            if (isset($fieldConfig['config']['foreign_selector']) && is_string($fieldConfig['config']['foreign_selector'])) {
                                $foreignSelectorFieldName = $fieldConfig['config']['foreign_selector'];
                            } elseif (isset($tca[$table]['columns'][$fieldName]['config']['foreign_selector']) && is_string($tca[$table]['columns'][$fieldName]['config']['foreign_selector'])) {
                                $foreignSelectorFieldName = $tca[$table]['columns'][$fieldName]['config']['foreign_selector'];
                            }
                            if ($foreignSelectorFieldName) {
                                $fieldConfig['config']['overrideChildTca']['columns'][$foreignSelectorFieldName] = $fieldConfig['config']['foreign_selector_fieldTcaOverride'];
                                unset($fieldConfig['config']['foreign_selector_fieldTcaOverride']);
                                $this->messages[] = 'The \'foreign_selector_fieldTcaOverride\' property from TCA ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'config\']  and has been migrated to ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'config\'][\'overrideChildTca\'][\'columns\'][\'' . $foreignSelectorFieldName . '\']';
                            }
                        }
                        if (isset($fieldConfig['config']['foreign_record_defaults']) && is_array($fieldConfig['config']['foreign_record_defaults'])) {
                            foreach ($fieldConfig['config']['foreign_record_defaults'] as $childFieldName => $defaultValue) {
                                $fieldConfig['config']['overrideChildTca']['columns'][$childFieldName]['config']['default'] = $defaultValue;
                                $this->messages[] = 'The \'foreign_record_defaults\' property from TCA ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'config\'][\'' . $childFieldName . '\']  and has been migrated to ' . $table . '[\'types\'][\'' . $typeName . '\'][\'columnsOverrides\'][\'' . $fieldName . '\'][\'config\'][\'overrideChildTca\'][\'columns\'][\'' . $childFieldName . '\'][\'config\'][\'default\']';
                            }
                            unset($fieldConfig['config']['foreign_record_defaults']);
                        }
                    }
                    unset($fieldConfig);
                }
                unset($typeConfig);
            }
            if (isset($tableDefinition['columns']) && is_array($tableDefinition['columns'])) {
                foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                    if (isset($fieldConfig['config']['type']) && $fieldConfig['config']['type'] !== 'inline') {
                        continue;
                    }
                    if (isset($fieldConfig['config']['foreign_types']) && is_array($fieldConfig['config']['foreign_types'])) {
                        if (isset($fieldConfig['config']['overrideChildTca']['types'])
                            && is_array($fieldConfig['config']['overrideChildTca']['types'])
                        ) {
                            $fieldConfig['config']['overrideChildTca']['types'] = array_replace_recursive(
                                $fieldConfig['config']['foreign_types'],
                                $fieldConfig['config']['overrideChildTca']['types']
                            );
                        } else {
                            $fieldConfig['config']['overrideChildTca']['types'] = $fieldConfig['config']['foreign_types'];
                        }
                        unset($fieldConfig['config']['foreign_types']);
                        $this->messages[] = 'The \'foreign_types\' property from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']  and has been migrated to ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'overrideChildTca\'][\'types\']';
                    }
                    if (isset($fieldConfig['config']['foreign_selector'], $fieldConfig['config']['foreign_selector_fieldTcaOverride']) && is_string($fieldConfig['config']['foreign_selector']) && is_array($fieldConfig['config']['foreign_selector_fieldTcaOverride'])) {
                        $foreignSelectorFieldName = $fieldConfig['config']['foreign_selector'];
                        if (isset($fieldConfig['config']['overrideChildTca']['columns'][$foreignSelectorFieldName])
                            && is_array($fieldConfig['config']['overrideChildTca']['columns'][$foreignSelectorFieldName])
                        ) {
                            $fieldConfig['config']['overrideChildTca']['columns'][$foreignSelectorFieldName] = array_replace_recursive(
                                $fieldConfig['config']['foreign_selector_fieldTcaOverride'],
                                $fieldConfig['config']['overrideChildTca']['columns'][$foreignSelectorFieldName]
                            );
                        } else {
                            $fieldConfig['config']['overrideChildTca']['columns'][$foreignSelectorFieldName] = $fieldConfig['config']['foreign_selector_fieldTcaOverride'];
                        }
                        unset($fieldConfig['config']['foreign_selector_fieldTcaOverride']);
                        $this->messages[] = 'The \'foreign_selector_fieldTcaOverride\' property from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\']  and has been migrated to ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'overrideChildTca\'][\'columns\'][\'' . $foreignSelectorFieldName . '\']';
                    }
                    if (isset($fieldConfig['config']['foreign_record_defaults']) && is_array($fieldConfig['config']['foreign_record_defaults'])) {
                        foreach ($fieldConfig['config']['foreign_record_defaults'] as $childFieldName => $defaultValue) {
                            if (!isset($fieldConfig['config']['overrideChildTca']['columns'][$childFieldName]['config']['default'])) {
                                $fieldConfig['config']['overrideChildTca']['columns'][$childFieldName]['config']['default'] = $defaultValue;
                                $this->messages[] = 'The \'foreign_record_defaults\' property from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'' . $childFieldName . '\']  and has been migrated to ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'][\'overrideChildTca\'][\'columns\'][\'' . $childFieldName . '\'][\'config\'][\'default\']';
                            }
                        }
                        unset($fieldConfig['config']['foreign_record_defaults']);
                    }
                }
                unset($fieldConfig);
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
            // If the feature is not enabled, a deprecation log entry is thrown
            if (!GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('unifiedPageTranslationHandling')) {
                $this->messages[] = 'The TCA table \'pages_language_overlay\' is'
                    . ' not used anymore and has been removed automatically in'
                    . ' order to avoid negative side-effects.';
            }
            unset($tca['pages_language_overlay']);
        }
        return $tca;
    }

    /**
     * type=group with internal_type=file and internal_type=file_reference have
     * been deprecated in TYPO3 v9 and will be removed in TYPO3 v10.0. This method scans
     * for usages. This methods does not modify TCA.
     *
     * @param array $tca
     * @return array
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function deprecateTypeGroupInternalTypeFile(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['config']['type'], $fieldConfig['config']['internal_type'])
                    && $fieldConfig['config']['type'] === 'group'
                    && ($fieldConfig['config']['internal_type'] === 'file' || $fieldConfig['config']['internal_type'] === 'file_reference')
                ) {
                    $this->messages[] = 'The \'internal_type\' = \'' . $fieldConfig['config']['internal_type'] . '\' property value'
                        . ' from TCA ' . $table . '[\'columns\'][\'' . $fieldName . '\'][\'config\'] is deprecated. It will continue'
                        . ' to work is TYPO3 v9, but the functionality will be removed in TYPO3 v10.0. Switch to inline FAL instead.';
                }
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
        $controlSectionNames = [
            'origUid',
            'languageField',
            'transOrigPointerField',
            'translationSource'
        ];
        foreach ($tca as $tableName => &$configuration) {
            foreach ($controlSectionNames as $controlSectionName) {
                $columnName = $configuration['ctrl'][$controlSectionName] ?? null;
                if (empty($columnName) || !empty($configuration['columns'][$columnName])) {
                    continue;
                }
                $configuration['columns'][$columnName] = [
                    'config' => [
                        'type' => 'passthrough',
                        'default' => 0,
                    ],
                ];
            }
        }
        return $tca;
    }
}
