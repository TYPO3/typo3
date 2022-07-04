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

namespace TYPO3\CMS\Core\Hooks;

use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Various items processor functions, mainly used for special select fields in `be_users` and `be_groups`
 *
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
class TcaItemsProcessorFunctions
{
    protected IconFactory $iconFactory;
    protected IconRegistry $iconRegistry;

    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
    }

    public function populateAvailableTables(array &$fieldDefinition): void
    {
        foreach ($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
            if ($tableConfiguration['ctrl']['adminOnly'] ?? false) {
                // Hide "admin only" tables
                continue;
            }
            $label = ($tableConfiguration['ctrl']['title'] ?? '') ?: '';
            $icon = $this->iconFactory->mapRecordTypeToIconIdentifier($tableName, []);
            $this->getLanguageService()->loadSingleTableDescription($tableName);
            $helpText = (string)($GLOBALS['TCA_DESCR'][$tableName]['columns']['']['description'] ?? '');
            $fieldDefinition['items'][] = [$label, $tableName, $icon, null, $helpText];
        }
    }

    public function populateAvailablePageTypes(array &$fieldDefinition): void
    {
        $pageTypes = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] ?? [];
        if (is_array($pageTypes) && $pageTypes !== []) {
            foreach ($pageTypes as $pageType) {
                if (!is_array($pageType) || !isset($pageType[1]) || $pageType[1] === '--div--') {
                    // Skip non arrays and divider items
                    continue;
                }
                [$label, $value] = $pageType;
                $icon = $this->iconFactory->mapRecordTypeToIconIdentifier('pages', ['doktype' => $pageType[1]]);
                $fieldDefinition['items'][] = [$label, $value, $icon];
            }
        }
    }

    public function populateAvailableGroupModules(array &$fieldDefinition): void
    {
        $fieldDefinition['items'] = $this->getAvailableModules('group', $fieldDefinition['items']);
    }

    public function populateAvailableUserModules(array &$fieldDefinition): void
    {
        $fieldDefinition['items'] = $this->getAvailableModules('user', $fieldDefinition['items']);
    }

    public function populateExcludeFields(array &$fieldDefinition): void
    {
        $languageService = $this->getLanguageService();
        foreach ($this->getGroupedExcludeFields() as $excludeFieldGroup) {
            $table = $excludeFieldGroup['table'] ?? '';
            $origin = $excludeFieldGroup['origin'] ?? '';
            // If the field comes from a FlexForm, the syntax is more complex
            if ($origin === 'flexForm') {
                // The field comes from a plugins FlexForm
                // Add header if not yet set for plugin section
                $sectionHeader = $excludeFieldGroup['sectionHeader'] ?? '';
                if (!isset($fieldDefinition['items'][$sectionHeader])) {
                    // there is no icon handling for plugins - we take the icon from the table
                    $icon = $this->iconFactory->mapRecordTypeToIconIdentifier($table, []);
                    $fieldDefinition['items'][$sectionHeader] = [$sectionHeader, '--div--', $icon];
                }
            } elseif (!isset($fieldDefinition['items'][$table])) {
                // Add header if not yet set for table
                $sectionHeader = $GLOBALS['TCA'][$table]['ctrl']['title'] ?? '';
                $icon = $this->iconFactory->mapRecordTypeToIconIdentifier($table, []);
                $fieldDefinition['items'][$table] = [$sectionHeader, '--div--', $icon];
            }
            $fullField = $excludeFieldGroup['fullField'] ?? '';
            $fieldName = $excludeFieldGroup['fieldName'] ?? '';
            $label = $origin === 'flexForm'
                ? ($excludeFieldGroup['fieldLabel'] ?? '')
                : $languageService->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label'] ?? '');
            // Add help text
            $languageService->loadSingleTableDescription($table);
            $helpText = (string)($GLOBALS['TCA_DESCR'][$table]['columns'][$fullField]['description'] ?? '');
            // Item configuration:
            $fieldDefinition['items'][] = [
                rtrim($label, ':') . ' (' . $fieldName . ')',
                $table . ':' . $fullField,
                'empty-empty',
                null,
                $helpText,
            ];
        }
    }

    public function populateExplicitAuthValues(array &$fieldDefinition): void
    {
        $icons = [
            'ALLOW' => 'status-status-permission-granted',
            'DENY' => 'status-status-permission-denied',
        ];
        // Traverse grouped field values:
        foreach ($this->getGroupedExplicitAuthFieldValues() as $groupKey => $tableFields) {
            if (empty($tableFields['items']) || !is_array($tableFields['items'])) {
                continue;
            }
            // Add header:
            $fieldDefinition['items'][] = [
                $tableFields['tableFieldLabel'] ?? '',
                '--div--',
            ];
            // Traverse options for this field:
            foreach ($tableFields['items'] as $itemValue => $itemContent) {
                [$allowDenyMode, $itemLabel, $allowDenyModeLabel] = $itemContent;
                // Add item to be selected:
                $fieldDefinition['items'][] = [
                    '[' . $allowDenyModeLabel . '] ' . $itemLabel,
                    $groupKey . ':' . preg_replace('/[:|,]/', '', (string)$itemValue) . ':' . $allowDenyMode,
                    $icons[$allowDenyMode],
                ];
            }
        }
    }

    public function populateCustomPermissionOptions(array &$fieldDefinition): void
    {
        $customOptions = $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'] ?? [];
        if (!is_array($customOptions) || $customOptions === []) {
            return;
        }
        $languageService = $this->getLanguageService();
        foreach ($customOptions as $customOptionsKey => $customOptionsValue) {
            if (empty($customOptionsValue['items']) || !is_array($customOptionsValue['items'])) {
                continue;
            }
            // Add header:
            $fieldDefinition['items'][] = [
                $languageService->sL($customOptionsValue['header'] ?? ''),
                '--div--',
            ];
            // Traverse items:
            foreach ($customOptionsValue['items'] as $itemKey => $itemConfig) {
                $icon = 'empty-empty';
                $helpText = '';
                if (!empty($itemConfig[1]) && $this->iconRegistry->isRegistered($itemConfig[1])) {
                    // Use icon identifier when registered
                    $icon = $itemConfig[1];
                }
                if (!empty($itemConfig[2])) {
                    $helpText = $languageService->sL($itemConfig[2]);
                }
                $fieldDefinition['items'][] = [
                    $languageService->sL($itemConfig[0] ?? ''),
                    $customOptionsKey . ':' . preg_replace('/[:|,]/', '', (string)$itemKey),
                    $icon,
                    null,
                    $helpText,
                ];
            }
        }
    }

    /**
     * Populates a list of category fields (with the defined relationships) for the given table
     *
     * @param array $fieldDefinition
     */
    public function populateAvailableCategoryFields(array &$fieldDefinition): void
    {
        $table = (string)($fieldDefinition['config']['itemsProcConfig']['table'] ?? '');
        if ($table === '') {
            throw new \UnexpectedValueException('No table to search for category fields given.', 1627565458);
        }

        $columns = $GLOBALS['TCA'][$table]['columns'] ?? false;
        if (!is_array($columns) || $columns === []) {
            throw new \RuntimeException('Given table ' . $table . ' does not define any columns to search for category fields.', 1627565459);
        }

        // For backwards compatibility, see CategoryRegistry->getCategoryFieldsForTable,
        // only category fields with the "manyToMany" relationship are allowed by default.
        // This can however be changed using the "allowedRelationships" itemsProcConfig.
        $allowedRelationships = $fieldDefinition['config']['itemsProcConfig']['allowedRelationships'] ?? false;
        if (!is_array($allowedRelationships) || $allowedRelationships === []) {
            $allowedRelationships = ['manyToMany'];
        }

        // @deprecated Only for backwards compatibility, in case extensions still add categories
        // through the registry (Not having type "category" set). Can be removed in v12.
        CategoryRegistry::getInstance()->getCategoryFieldsForTable($fieldDefinition);

        // Loop on all table columns to find category fields
        foreach ($columns as $fieldName => $fieldConfig) {
            if (($fieldConfig['config']['type'] ?? '') !== 'category'
                || !in_array($fieldConfig['config']['relationship'] ?? '', $allowedRelationships, true)
            ) {
                continue;
            }
            $fieldLabel = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label']);
            $fieldDefinition['items'][] = [$fieldLabel, $fieldName];
        }
    }

    /**
     * Get all available modules for the given context: "user" or "group"
     *
     * @param string $context
     * @param array $items
     * @return array
     */
    protected function getAvailableModules(string $context, array $items): array
    {
        if (!in_array($context, ['user', 'group'], true)) {
            return $items;
        }
        $languageService = $this->getLanguageService();
        $moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
        $moduleLoader->load($GLOBALS['TBE_MODULES']);
        $moduleList = $context === 'user' ? $moduleLoader->modListUser : $moduleLoader->modListGroup;
        if (!is_array($moduleList) || $moduleList === []) {
            return $items;
        }
        foreach ($moduleList as $module) {
            $moduleLabels = $moduleLoader->getLabelsForModule($module);
            $moduleArray = GeneralUtility::trimExplode('_', $module, true);
            $mainModule = $moduleArray[0] ?? '';
            $subModule = $moduleArray[1] ?? '';
            // Icon:
            if (!empty($subModule)) {
                $icon = $moduleLoader->getModules()[$mainModule]['sub'][$subModule]['iconIdentifier'] ?? '';
            } else {
                $icon = $moduleLoader->getModules()[$module]['iconIdentifier'] ?? '';
            }
            // Add help text
            $helpText = [
                'title' => $languageService->sL($moduleLabels['shortdescription'] ?? ''),
                'description' => $languageService->sL($moduleLabels['description'] ?? ''),
            ];
            $label = '';
            // Add label for main module if this is a submodule
            if (!empty($subModule)) {
                $mainModuleLabels = $moduleLoader->getLabelsForModule($mainModule);
                $label .= $languageService->sL($mainModuleLabels['title'] ?? '') . '>';
            }
            // Add modules own label now
            $label .= $languageService->sL($moduleLabels['title'] ?? '');
            // Item configuration
            $items[] = [$label, $module, $icon, null, $helpText];
        }
        return $items;
    }

    /**
     * Returns an array with the exclude fields as defined in TCA and FlexForms
     * Used for listing the exclude fields in be_groups forms.
     *
     * @return array Array of arrays with excludeFields (fieldName, table:fieldName) from TCA
     *               and FlexForms (fieldName, table:extKey;sheetName;fieldName)
     */
    protected function getGroupedExcludeFields(): array
    {
        $languageService = $this->getLanguageService();
        $excludeFieldGroups = [];

        // Fetch translations for table names
        $tableToTranslation = [];
        // All TCA keys
        foreach ($GLOBALS['TCA'] as $table => $conf) {
            $tableToTranslation[$table] = $languageService->sL($conf['ctrl']['title'] ?? '');
        }
        // Sort by translations
        asort($tableToTranslation);
        foreach ($tableToTranslation as $table => $translatedTable) {
            $excludeFieldGroup = [];

            // All field names configured and not restricted to admins
            if (!empty($GLOBALS['TCA'][$table]['columns'])
                && is_array($GLOBALS['TCA'][$table]['columns'])
                && empty($GLOBALS['TCA'][$table]['ctrl']['adminOnly'])
                && (empty($GLOBALS['TCA'][$table]['ctrl']['rootLevel']) || !empty($GLOBALS['TCA'][$table]['ctrl']['security']['ignoreRootLevelRestriction']))
            ) {
                foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $fieldDefinition) {
                    // Only show fields that can be excluded for editors, or are hidden for non-admins
                    if (($fieldDefinition['exclude'] ?? false) && ($fieldDefinition['displayCond'] ?? '') !== 'HIDE_FOR_NON_ADMINS') {
                        // Get human readable names of fields
                        $translatedField = $languageService->sL($fieldDefinition['label'] ?? '');
                        // Add entry, key 'labels' needed for sorting
                        $excludeFieldGroup[] = [
                            'labels' => $translatedTable . ':' . $translatedField,
                            'sectionHeader' => $translatedTable,
                            'table' => $table,
                            'tableField' => $fieldName,
                            'fieldName' => $fieldName,
                            'fullField' => $fieldName,
                            'fieldLabel' => $translatedField,
                            'origin' => 'tca',
                        ];
                    }
                }
            }
            // All FlexForm fields
            $flexFormArray = $this->getRegisteredFlexForms((string)$table);
            foreach ($flexFormArray as $tableField => $flexForms) {
                // Prefix for field label, e.g. "Plugin Options:"
                $labelPrefix = '';
                if (!empty($GLOBALS['TCA'][$table]['columns'][$tableField]['label'])) {
                    $labelPrefix = $languageService->sL($GLOBALS['TCA'][$table]['columns'][$tableField]['label']);
                }
                // Get all sheets
                foreach ($flexForms as $extIdent => $extConf) {
                    if (empty($extConf['sheets']) || !is_array($extConf['sheets'])) {
                        continue;
                    }
                    // Get all fields in sheet
                    foreach ($extConf['sheets'] as $sheetName => $sheet) {
                        if (empty($sheet['ROOT']['el']) || !is_array($sheet['ROOT']['el'])) {
                            continue;
                        }
                        foreach ($sheet['ROOT']['el'] as $pluginFieldName => $field) {
                            // Use only fields that have exclude flag set
                            if (empty($field['TCEforms']['exclude'])) {
                                continue;
                            }
                            $fieldLabel = !empty($field['TCEforms']['label']) ? $languageService->sL($field['TCEforms']['label']) : $pluginFieldName;
                            $excludeFieldGroup[] = [
                                'labels' => trim($translatedTable . ' ' . $labelPrefix . ' ' . $extIdent, ': ') . ':' . $fieldLabel,
                                'sectionHeader' => trim($translatedTable . ' ' . $labelPrefix . ' ' . $extIdent, ':'),
                                'table' => $table,
                                'tableField' => $tableField,
                                'extIdent' => $extIdent,
                                'fieldName' => $pluginFieldName,
                                'fullField' => $tableField . ';' . $extIdent . ';' . $sheetName . ';' . $pluginFieldName,
                                'fieldLabel' => $fieldLabel,
                                'origin' => 'flexForm',
                            ];
                        }
                    }
                }
            }
            // Sort fields by the translated value
            if (!empty($excludeFieldGroup)) {
                usort($excludeFieldGroup, static function (array $array1, array $array2) {
                    $array1 = reset($array1);
                    $array2 = reset($array2);
                    if (is_string($array1) && is_string($array2)) {
                        return strcasecmp($array1, $array2);
                    }
                    return 0;
                });
                $excludeFieldGroups = array_merge($excludeFieldGroups, $excludeFieldGroup);
            }
        }

        return $excludeFieldGroups;
    }

    /**
     * Returns FlexForm data structures it finds. Used in select "special" for be_groups
     * to set "exclude" flags for single flex form fields.
     *
     * This only finds flex forms registered in 'ds' config sections.
     * This does not resolve other sophisticated flex form data structure references.
     *
     * @todo: This approach is limited and doesn't find everything. It works for casual tt_content plugins, though:
     * @todo: The data structure identifier determination depends on data row, but we don't have all rows at hand here.
     * @todo: The code thus "guesses" some standard data structure identifier scenarios and tries to resolve those.
     * @todo: This guessing can not be solved in a good way. A general registry of "all" possible data structures is
     * @todo: probably not wanted, since that wouldn't work for truly dynamic DS calculations. Probably the only
     * @todo: thing we could do here is a hook to allow extensions declaring specific data structures to
     * @todo: allow backend admins to set exclude flags for certain fields in those cases.
     *
     * @param string $table Table to handle
     * @return array Data structures
     */
    protected function getRegisteredFlexForms(string $table): array
    {
        if (empty($GLOBALS['TCA'][$table]['columns']) || !is_array($GLOBALS['TCA'][$table]['columns'])) {
            return [];
        }
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $flexForms = [];
        foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $fieldDefinition) {
            if (($fieldDefinition['config']['type'] ?? '') !== 'flex'
                || empty($fieldDefinition['config']['ds'])
                || !is_array($fieldDefinition['config']['ds'])
            ) {
                continue;
            }
            $flexForms[$field] = [];
            foreach (array_keys($fieldDefinition['config']['ds']) as $flexFormKey) {
                $flexFormKey = (string)$flexFormKey;
                // Get extension identifier (uses second value if it's not empty, "list" or "*", else first one)
                $identFields = GeneralUtility::trimExplode(',', $flexFormKey);
                $extIdent = $identFields[0] ?? '';
                if (!empty($identFields[1]) && $identFields[1] !== 'list' && $identFields[1] !== '*') {
                    $extIdent = $identFields[1];
                }
                $flexFormDataStructureIdentifier = json_encode([
                    'type' => 'tca',
                    'tableName' => $table,
                    'fieldName' => $field,
                    'dataStructureKey' => $flexFormKey,
                ]);
                try {
                    $dataStructure = $flexFormTools->parseDataStructureByIdentifier($flexFormDataStructureIdentifier);
                    $flexForms[$field][$extIdent] = $dataStructure;
                } catch (InvalidIdentifierException $e) {
                    // Deliberately empty: The DS identifier is guesswork and the flex ds parser throws
                    // this exception if it can not resolve to a valid data structure. This is "ok" here
                    // and the exception is just eaten.
                }
            }
        }
        return $flexForms;
    }

    /**
     * Returns an array with explicit Allow/Deny fields.
     * Used for listing these field/value pairs in be_groups forms
     *
     * @return array Array with information from all of $GLOBALS['TCA']
     */
    protected function getGroupedExplicitAuthFieldValues(): array
    {
        $languageService = $this->getLanguageService();
        $allowDenyLabels = [
            'ALLOW' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.allow'),
            'DENY' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deny'),
        ];
        $allowDenyOptions = [];
        foreach ($GLOBALS['TCA'] as $table => $tableConfiguration) {
            if (empty($tableConfiguration['columns']) || !is_array($tableConfiguration['columns'])) {
                continue;
            }
            // All field names configured:
            foreach ($tableConfiguration['columns'] as $field => $fieldDefinition) {
                $fieldConfig = $fieldDefinition['config'] ?? [];
                if (($fieldConfig['type'] ?? '') !== 'select' || !(bool)($fieldConfig['authMode'] ?? false)) {
                    continue;
                }
                // Check for items
                if (empty($fieldConfig['items']) || !is_array($fieldConfig['items'])) {
                    continue;
                }
                // Get Human Readable names of fields and table:
                $allowDenyOptions[$table . ':' . $field]['tableFieldLabel'] =
                    $languageService->sL($GLOBALS['TCA'][$table]['ctrl']['title'] ?? '') . ': '
                    . $languageService->sL($GLOBALS['TCA'][$table]['columns'][$field]['label'] ?? '');

                foreach ($fieldConfig['items'] as $item) {
                    $itemIdentifier = (string)($item[1] ?? '');
                    // Values '' and '--div--' are not controlled by this setting.
                    if ($itemIdentifier === '' || $itemIdentifier === '--div--') {
                        continue;
                    }
                    // Find allowDenyMode
                    $allowDenyMode = '';
                    switch ((string)$fieldConfig['authMode']) {
                        case 'explicitAllow':
                            $allowDenyMode = 'ALLOW';
                            break;
                        case 'explicitDeny':
                            $allowDenyMode = 'DENY';
                            break;
                        case 'individual':
                            if ($item[5] ?? false) {
                                if ($item[5] === 'EXPL_ALLOW') {
                                    $allowDenyMode = 'ALLOW';
                                } elseif ($item[5] === 'EXPL_DENY') {
                                    $allowDenyMode = 'DENY';
                                }
                            }
                            break;
                    }
                    // Set allowDenyMode
                    if ($allowDenyMode) {
                        $allowDenyOptions[$table . ':' . $field]['items'][$itemIdentifier] = [
                            $allowDenyMode,
                            $languageService->sL($item[0] ?? ''),
                            $allowDenyLabels[$allowDenyMode],
                        ];
                    }
                }
            }
        }
        return $allowDenyOptions;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
