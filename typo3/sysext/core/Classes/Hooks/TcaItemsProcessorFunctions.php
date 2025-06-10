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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\CategoryFieldType;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Various items processor functions, mainly used for special select fields in `be_users` and `be_groups`
 *
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
readonly class TcaItemsProcessorFunctions
{
    public function __construct(
        private IconFactory $iconFactory,
        private IconRegistry $iconRegistry,
        private ModuleProvider $moduleProvider,
        private FlexFormTools $flexFormTools,
        private TcaSchemaFactory $tcaSchemaFactory,
        private PageDoktypeRegistry $pageDoktypeRegistry,
    ) {}

    public function populateAvailableTables(array &$fieldDefinition): void
    {
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $tableName => $schema) {
            // Hide "admin only" tables
            if ($schema->getCapability(TcaSchemaCapability::AccessAdminOnly)->getValue()) {
                continue;
            }
            $label = ($schema->getRawConfiguration()['title'] ?? '') ?: '';
            $icon = $this->iconFactory->mapRecordTypeToIconIdentifier($tableName, []);
            $fieldDefinition['items'][] = ['label' => $label, 'value' => $tableName, 'icon' => $icon];
        }
    }

    public function populateAvailablePageTypes(array &$fieldDefinition): void
    {
        foreach ($this->pageDoktypeRegistry->getAllDoktypes() as $pageType) {
            if (!$pageType->getValue()) {
                continue;
            }
            $icon = $this->iconFactory->mapRecordTypeToIconIdentifier('pages', ['doktype' => $pageType->getValue()]);
            $fieldDefinition['items'][] = ['label' => $pageType->getLabel(), 'value' => $pageType->getValue(), 'icon' => $icon];
        }
    }

    public function populateAvailableUserModules(array &$fieldDefinition): void
    {
        $modules = $this->moduleProvider->getUserModules();
        if ($modules === []) {
            return;
        }
        $languageService = $this->getLanguageService();
        foreach ($modules as $identifier => $module) {
            // Item configuration
            $label = $languageService->sL($module->getTitle());
            $parentModule = $module->getParentModule();
            while ($parentModule) {
                $label = $languageService->sL($parentModule->getTitle()) . ' > ' . $label;
                $parentModule = $parentModule->getParentModule();
            }
            $help = null;
            if ($module->getDescription()) {
                $help = [
                    'title' =>  $languageService->sL($module->getShortDescription()),
                    'description' => $languageService->sL($module->getDescription()),
                ];
            }
            $fieldDefinition['items'][] = [
                'label' => $label,
                'value' => $identifier,
                'icon' => $module->getIconIdentifier(),
                'description' => $help,
            ];
        }
    }

    public function populateExcludeFields(array &$fieldDefinition): void
    {
        $languageService = $this->getLanguageService();
        foreach ($this->getGroupedExcludeFields() as $excludeFieldGroup) {
            $table = $excludeFieldGroup['table'] ?? '';
            $origin = $excludeFieldGroup['origin'] ?? '';
            $schema = $this->tcaSchemaFactory->get($table);
            // If the field comes from a FlexForm, the syntax is more complex
            if ($origin === 'flexForm') {
                // The field comes from a plugins FlexForm
                // Add header if not yet set for plugin section
                $sectionHeader = $excludeFieldGroup['sectionHeader'] ?? '';
                if (!isset($fieldDefinition['items'][$sectionHeader])) {
                    // there is no icon handling for plugins - we take the icon from the table
                    $icon = $this->iconFactory->mapRecordTypeToIconIdentifier($table, []);
                    $fieldDefinition['items'][$sectionHeader] = ['label' => $sectionHeader, 'value' => '--div--', 'icon' => $icon];
                }
            } elseif (!isset($fieldDefinition['items'][$table])) {
                // Add header if not yet set for table
                $sectionHeader = $schema->getRawConfiguration()['title'] ?? '';
                $icon = $this->iconFactory->mapRecordTypeToIconIdentifier($table, []);
                $fieldDefinition['items'][$table] = ['label' => $sectionHeader, 'value' => '--div--', 'icon' => $icon];
            }
            $fullField = $excludeFieldGroup['fullField'] ?? '';
            $fieldName = $excludeFieldGroup['fieldName'] ?? '';
            $label = $origin === 'flexForm'
                ? ($excludeFieldGroup['fieldLabel'] ?? '')
                : $languageService->sL($schema->getField($fieldName)->getLabel());
            // Item configuration:
            $fieldDefinition['items'][] = [
                'label' => rtrim($label, ':') . ' (' . $fieldName . ')',
                'value' => $table . ':' . $fullField,
                'icon' => 'empty-empty',
            ];
        }
    }

    public function populateExplicitAuthValues(array &$fieldDefinition): void
    {
        // Traverse grouped field values:
        foreach ($this->getGroupedExplicitAuthFieldValues() as $groupKey => $tableFields) {
            if (empty($tableFields['items']) || !is_array($tableFields['items'])) {
                continue;
            }
            // Add header:
            $fieldDefinition['items'][] = [
                'label' => $tableFields['tableFieldLabel'] ?? '',
                'value' => '--div--',
            ];
            // Traverse options for this field:
            foreach ($tableFields['items'] as $itemValue => $itemContent) {
                $fieldDefinition['items'][] = [
                    'label' => $itemContent,
                    'value' => $groupKey . ':' . preg_replace('/[:|,]/', '', (string)$itemValue),
                    'icon' => 'status-status-permission-granted',
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
                'label' => $languageService->sL($customOptionsValue['header'] ?? ''),
                'value' => '--div--',
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
                    'label' => $languageService->sL($itemConfig[0] ?? ''),
                    'value' => $customOptionsKey . ':' . preg_replace('/[:|,]/', '', (string)$itemKey),
                    'icon' => $icon,
                    'description' => $helpText,
                ];
            }
        }
    }

    /**
     * Populates a list of category fields (with the defined relationships) for the given table
     */
    public function populateAvailableCategoryFields(array &$fieldDefinition): void
    {
        $table = (string)($fieldDefinition['config']['itemsProcConfig']['table'] ?? '');
        if ($table === '') {
            throw new \UnexpectedValueException('No table to search for category fields given.', 1627565458);
        }

        if (!$this->tcaSchemaFactory->has($table)) {
            throw new \RuntimeException('Given table ' . $table . ' does not define any valid schema to search for category fields.', 1627565459);
        }

        // Only category fields with the "manyToMany" relationship are allowed by default.
        // This can however be changed using the "allowedRelationships" itemsProcConfig.
        $allowedRelationships = $fieldDefinition['config']['itemsProcConfig']['allowedRelationships'] ?? false;
        if (!is_array($allowedRelationships) || $allowedRelationships === []) {
            $allowedRelationships = ['manyToMany'];
        }

        $schema = $this->tcaSchemaFactory->get($table);

        // Loop on all table columns to find category fields
        foreach ($schema->getFields() as $fieldName => $fieldConfig) {
            /** @var CategoryFieldType $fieldConfig */
            if (!$fieldConfig->isType(TableColumnType::CATEGORY)) {
                continue;
            }
            if (!in_array($fieldConfig->getConfiguration()['relationship'] ?? '', $allowedRelationships, true)) {
                continue;
            }
            $fieldDefinition['items'][] = [
                'label' => $this->getLanguageService()->sL($fieldConfig->getLabel()),
                'value' => $fieldName,
            ];
        }
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
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            $tableToTranslation[$table] = $languageService->sL($schema->getRawConfiguration()['title'] ?? '');
        }
        // Sort by translations
        asort($tableToTranslation);
        foreach ($tableToTranslation as $table => $translatedTable) {
            $excludeFieldGroup = [];
            $schema = $this->tcaSchemaFactory->get($table);

            // All field names configured and not restricted to admins
            $rootLevelCapability = $schema->getCapability(TcaSchemaCapability::RestrictionRootLevel);
            if (!$rootLevelCapability->shallIgnoreRootLevelRestriction() && !empty($rootLevelCapability->getRootLevelType())) {
                continue;
            }
            if ($schema->hasCapability(TcaSchemaCapability::AccessAdminOnly)) {
                continue;
            }

            foreach ($schema->getFields() as $fieldName => $fieldDefinition) {
                // Only show fields that can be excluded for editors, or are hidden for non-admins
                if ($fieldDefinition->supportsAccessControl() && $fieldDefinition->getDisplayConditions() !== 'HIDE_FOR_NON_ADMINS') {
                    // Get human-readable names of fields
                    $translatedField = $languageService->sL($fieldDefinition->getLabel());
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
            // All FlexForm fields
            $flexFormArray = $this->getRegisteredFlexForms((string)$table);
            foreach ($flexFormArray as $tableField => $flexForms) {
                // Prefix for field label, e.g. "Plugin Options:"
                $labelPrefix = '';
                $fieldDefinition = $schema->getField($tableField);
                if ($fieldDefinition->getLabel() !== '') {
                    $labelPrefix = $languageService->sL($fieldDefinition->getLabel());
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
                            if (empty($field['exclude'])) {
                                continue;
                            }
                            $fieldLabel = !empty($field['label']) ? $languageService->sL($field['label']) : $pluginFieldName;
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
        if (!$this->tcaSchemaFactory->has($table)) {
            return [];
        }
        $schema = $this->tcaSchemaFactory->get($table);
        $flexForms = [];
        foreach ($schema->getFields() as $field => $fieldDefinition) {
            if ($fieldDefinition->getType() !== TableColumnType::FLEX->value) {
                continue;
            }
            $fieldDefinition = $fieldDefinition->getConfiguration();
            if (empty($fieldDefinition['ds']) || !is_array($fieldDefinition['ds'])) {
                continue;
            }
            $flexForms[$field] = [];
            foreach (array_keys($fieldDefinition['ds']) as $flexFormKey) {
                $flexFormKey = (string)$flexFormKey;
                // Get extension identifier (uses second value if it's not empty, "list" or "*", else first one)
                $identFields = GeneralUtility::trimExplode(',', $flexFormKey);
                $extIdent = $identFields[0] ?? '';
                // @todo resolve this special handling together with EMU::addPiFlexFormValue
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
                    $dataStructure = $this->flexFormTools->parseDataStructureByIdentifier($flexFormDataStructureIdentifier);
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
     * Returns an array with explicit allow fields.
     * Used for listing these field/value pairs in be_groups forms
     *
     * @return array Array with information from all of $GLOBALS['TCA']
     */
    protected function getGroupedExplicitAuthFieldValues(): array
    {
        $languageService = $this->getLanguageService();
        $allowOptions = [];
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            // All field names configured:
            foreach ($schema->getFields() as $field => $fieldDefinition) {
                $fieldConfig = $fieldDefinition->getConfiguration();
                if (($fieldConfig['type'] ?? '') !== 'select'
                    || ($fieldConfig['authMode'] ?? false) !== 'explicitAllow'
                    || empty($fieldConfig['items'])
                    || !is_array($fieldConfig['items'])
                ) {
                    continue;
                }
                // Get Human Readable names of fields and table:
                $allowOptions[$table . ':' . $field]['tableFieldLabel'] =
                    $languageService->sL($schema->getRawConfiguration()['title'] ?? '') . ': '
                    . $languageService->sL($fieldDefinition->getLabel());

                foreach ($fieldConfig['items'] as $item) {
                    $itemIdentifier = (string)($item['value'] ?? '');
                    // Values '' and '--div--' are not controlled by this setting.
                    if ($itemIdentifier === '' || $itemIdentifier === '--div--') {
                        continue;
                    }
                    $allowOptions[$table . ':' . $field]['items'][$itemIdentifier] = $languageService->sL($item['label'] ?? '');
                }
            }
        }
        return $allowOptions;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
