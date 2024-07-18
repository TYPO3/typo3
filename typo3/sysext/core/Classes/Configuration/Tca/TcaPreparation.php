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

use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Prepare TCA. Used in bootstrap and Flex Form Data Structures,
 * executed *after* TcaMigration.
 * This is used to add *internal TCA details* needed by core.
 * For instance, type=category fields receive all the relation
 * details in order to work properly.
 *
 * @internal Class and API may change any time.
 */
readonly class TcaPreparation
{
    /**
     * Prepare TCA
     *
     * This class is typically called within bootstrap with empty caches after all TCA
     * files from extensions have been loaded. The preparation is then applied and
     * the prepared result is cached.
     * For flex form TCA, this class is called dynamically if opening a record in the backend.
     *
     * See unit tests for details.
     */
    public function prepare(array $tca, bool $isFlexForm = false): array
    {
        $tca = $this->configureCategoryRelations($tca, $isFlexForm);
        $tca = $this->configureFileReferences($tca, $isFlexForm);
        $tca = $this->configureEmailSoftReferences($tca);
        $tca = $this->configureLinkSoftReferences($tca);
        $tca = $this->configureSelectSingle($tca);
        return $tca;
    }

    /**
     * Prepares TCA configuration of type='category' fields.
     *
     * It adds some TCA config settings so category fields end up with similar
     * config as type='select' field, but in a more restricted way.
     * Some settings could also be set in TCA directly, but some fields
     * can not be overridden, e.g. foreign_table.
     *
     * This also sets necessary MM properties, in case relationship is
     * set to "manyToMany", which is the default. Note it is "oneToMany"
     * with flex forms, since flex forms do NOT support "manyToMany".
     *
     * Finally, all category fields with a "manyToMany" relationship are
     * added to the MM_oppositeUsage of sys_category "items".
     *
     * Important: Since this method defines a "foreign_table_where", this
     * must always be executed before prepareQuotingOfTableNamesAndColumnNames().
     */
    protected function configureCategoryRelations(array $tca, bool $isFlexForm): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'category') {
                    continue;
                }
                if (!isset($fieldConfig['label'])) {
                    $fieldConfig['label'] = 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.categories';
                }
                // Force foreign_table for type category
                $fieldConfig['config']['foreign_table'] = 'sys_category';
                // Initialize default column configuration and merge it with already defined
                $fieldConfig['config']['size'] ??= 20;
                $fieldConfig['config']['foreign_table_where'] ??= ' AND {#sys_category}.{#sys_language_uid} IN (-1, 0)';
                if (empty($fieldConfig['config']['relationship'])) {
                    // In case no relationship is given, set "manyToMany" for non flex form, but "oneToMany" with flex form.
                    $fieldConfig['config']['relationship'] = $isFlexForm ? 'oneToMany' : 'manyToMany';
                }

                // Sanitize 'relationship'
                if ($isFlexForm && !in_array($fieldConfig['config']['relationship'], ['oneToOne', 'oneToMany'], true)) {
                    throw new \UnexpectedValueException(
                        '"relationship" must be one of "oneToOne" or "oneToMany", "manyToMany" is not supported as "relationship"' .
                        ' for field ' . $fieldName . ' of type "category" in flexform.',
                        1627640208
                    );
                }
                if (!in_array($fieldConfig['config']['relationship'], ['oneToOne', 'oneToMany', 'manyToMany'], true)) {
                    throw new \RuntimeException(
                        $fieldName . ' of table ' . $table . ' is defined as type category with relationship "'
                        . $fieldConfig['config']['relationship'] . '", but only "oneToOne", "oneToMany" and "manyToMany"'
                        . ' are allowed.',
                        1627898896
                    );
                }

                // Set the maxitems value (necessary for DataHandling and FormEngine)
                if ($fieldConfig['config']['relationship'] === 'oneToOne') {
                    // In case relationship is set to "oneToOne", the database column for this
                    // field will be an integer column. This means, only one uid can be stored.
                    // Therefore, maxitems must be 1. Sanitize for flex form fields as well.
                    if ((int)($fieldConfig['config']['maxitems'] ?? 0) > 1) {
                        throw new \RuntimeException(
                            $fieldName . ' of table ' . $table . ' is defined as type category with an oneToOne relationship. ' .
                            'Therefore maxitems must be 1. Otherwise, use oneToMany or manyToMany as relationship instead.',
                            1627335016
                        );
                    }
                    $fieldConfig['config']['maxitems'] = 1;
                } elseif (!($fieldConfig['config']['maxitems'] ?? false)) {
                    // In case maxitems is not set or set to 0, set the default value "99999"
                    $fieldConfig['config']['maxitems'] = 99999;
                } elseif ($fieldConfig['config']['relationship'] === 'oneToMany'
                    && (int)($fieldConfig['config']['maxitems'] ?? 0) === 1
                ) {
                    throw new \RuntimeException(
                        $fieldName . ' of table ' . $table . ' is defined as type category with a ' . $fieldConfig['config']['relationship'] .
                        ' relationship. Therefore, maxitems can not be set to 1. Use oneToOne as relationship instead.',
                        1627335017
                    );
                }

                // Add the default value if not set
                if (!isset($fieldConfig['config']['default'])
                    && $fieldConfig['config']['relationship'] !== 'oneToMany'
                ) {
                    // @todo: This is db wise not accurate: A oneToOne relation without a relation being assigned,
                    // @todo: should have NULL as value, not 0, since 0 looks like a uid, but it isn't.
                    // @todo: The field should be nullable and DH should handle that.
                    $fieldConfig['config']['default'] = 0;
                }

                // Add MM related properties in case relationship is set to "manyToMany".
                // This will not be done for the sys_category table itself. Not relevant with flex forms.
                if ($fieldConfig['config']['relationship'] === 'manyToMany' && $table !== 'sys_category') {
                    // Note these settings are hard coded here and can't be overridden.
                    $fieldConfig['config'] = array_replace_recursive($fieldConfig['config'], [
                        'MM' => 'sys_category_record_mm',
                        'MM_opposite_field' => 'items',
                        'MM_match_fields' => [
                            'tablenames' => $table,
                            'fieldname' => $fieldName,
                        ],
                    ]);
                    // Register opposite references for the foreign side of a category relation
                    if (empty($tca['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$table])) {
                        $tca['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$table] = [];
                    }
                    if (!in_array($fieldName, $tca['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$table], true)) {
                        $tca['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$table][] = $fieldName;
                    }
                    // Take specific value of exclude flag into account
                    if (!isset($fieldConfig['exclude'])) {
                        $fieldConfig['exclude'] = true;
                    }
                }
            }
        }
        return $tca;
    }

    protected function configureFileReferences(array $tca, bool $isFlexForm): array
    {
        foreach ($tca as $table => &$tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }
            foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'file') {
                    continue;
                }
                // Set static values for this type. Most of them are not needed due to the
                // dedicated TCA type. However a lot of underlying code in DataHandler and
                // friends relies on those keys, especially "foreign_table" and "foreign_selector".
                // @todo Check which of those values can be removed since only used by FormEngine
                $fieldConfig['config'] = array_replace_recursive($fieldConfig['config'], [
                    'foreign_table' => 'sys_file_reference',
                    'foreign_field' => 'uid_foreign',
                    'foreign_sortby' => 'sorting_foreign',
                    'foreign_table_field' => 'tablenames',
                    'foreign_match_fields' => [
                        'fieldname' => $fieldName,
                    ],
                    'foreign_label' => 'uid_local',
                    'foreign_selector' => 'uid_local',
                ]);
                if (!$isFlexForm) {
                    $fieldConfig['config']['foreign_match_fields']['tablenames'] = $table;
                }
                $fieldConfig['config'] = $this->configureAllowedDisallowedFileExtensions($fieldConfig['config']);
                if (is_array($fieldConfig['config']['overrideChildTca'] ?? null)) {
                    $fieldConfig['config']['overrideChildTca'] = $this->configureAllowedDisallowedInOverrideChildTca($fieldConfig['config']['overrideChildTca']);
                }
            }
            unset($fieldConfig);
            if (is_array($tableDefinition['types'] ?? null)) {
                foreach ($tableDefinition['types'] as &$typeConfig) {
                    if (!isset($typeConfig['columnsOverrides']) || !is_array($typeConfig['columnsOverrides'])) {
                        continue;
                    }
                    foreach ($typeConfig['columnsOverrides'] as &$columnsOverridesConfig) {
                        if (!isset($columnsOverridesConfig['config']) || !is_array($columnsOverridesConfig['config'])) {
                            continue;
                        }
                        $columnsOverridesConfig['config'] = $this->configureAllowedDisallowedFileExtensions($columnsOverridesConfig['config']);
                        if (is_array($columnsOverridesConfig['config']['overrideChildTca'] ?? null)) {
                            $columnsOverridesConfig['config']['overrideChildTca'] = $this->configureAllowedDisallowedInOverrideChildTca($columnsOverridesConfig['config']['overrideChildTca']);
                        }
                    }
                }
            }
        }
        return $tca;
    }

    /**
     * configureFileReferences() helper
     */
    protected function configureAllowedDisallowedInOverrideChildTca(array $overrideChildTcaConfig): array
    {
        if (is_array($overrideChildTcaConfig['columns'] ?? null)) {
            foreach ($overrideChildTcaConfig['columns'] as &$overrideChildTcaColumnConfig) {
                if (!isset($overrideChildTcaColumnConfig['config'])) {
                    continue;
                }
                $overrideChildTcaColumnConfig['config'] = $this->configureAllowedDisallowedFileExtensions($overrideChildTcaColumnConfig['config']);
            }
            unset($overrideChildTcaColumnConfig);
        }
        if (is_array($overrideChildTcaConfig['types'] ?? null)) {
            foreach ($overrideChildTcaConfig['types'] as &$overrideChildTcaTypeConfig) {
                if (!isset($overrideChildTcaTypeConfig['config'])) {
                    continue;
                }
                $overrideChildTcaTypeConfig['config'] = $this->configureAllowedDisallowedFileExtensions($overrideChildTcaTypeConfig['config']);
            }
        }
        return $overrideChildTcaConfig;
    }

    /**
     * configureFileReferences() helper
     */
    protected function configureAllowedDisallowedFileExtensions(array $config): array
    {
        if (!empty($allowed = ($config['allowed'] ?? null))) {
            $config['allowed'] = $this->prepareFileExtensions($allowed);
        }
        if (!empty($disallowed = ($config['disallowed'] ?? null))) {
            $config['disallowed'] = $this->prepareFileExtensions($disallowed);
        }
        return $config;
    }

    /**
     * configureFileReferences() helper: Ensures format, replaces placeholders and remove duplicates
     */
    protected function prepareFileExtensions(mixed $fileExtensions): string
    {
        if (is_array($fileExtensions)) {
            $fileExtensions = implode(',', $fileExtensions);
        } else {
            $fileExtensions = (string)$fileExtensions;
        }
        // Replace placeholders with the corresponding $GLOBALS value for now
        if (preg_match_all('/common-(image|text|media)-types/', $fileExtensions, $matches)) {
            foreach ($matches[1] as $key => $type) {
                $fileExtensions = str_replace(
                    $matches[0][$key],
                    $GLOBALS['TYPO3_CONF_VARS'][$type === 'image' ? 'GFX' : 'SYS'][$type . 'file_ext'] ?? '',
                    $fileExtensions
                );
            }
        }
        return StringUtility::uniqueList($fileExtensions);
    }

    /**
     * Add "'softref' = 'email[subst]'" to all 'type' = 'email' column fields.
     */
    protected function configureEmailSoftReferences(array $tca): array
    {
        foreach ($tca as &$tableDefinition) {
            if (!is_array($tableDefinition['columns'] ?? null)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? null) === 'email') {
                    // Hard set/override: 'softref' is not listed as property for type=email at all,
                    // there is little need to have this configurable.
                    $fieldConfig['config']['softref'] = 'email[subst]';
                }
            }
        }
        return $tca;
    }

    /**
     * Add "'softref' = 'typolink'" to all 'type' = 'link' column fields.
     */
    protected function configureLinkSoftReferences(array $tca): array
    {
        foreach ($tca as &$tableDefinition) {
            if (!is_array($tableDefinition['columns'] ?? null)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? null) === 'link') {
                    // Hard set/override: 'softref' is not listed as property for type=link at all,
                    // there is little need to have this configurable.
                    $fieldConfig['config']['softref'] = 'typolink';
                }
            }
        }
        return $tca;
    }

    /**
     * Add "'maxitems' => 1" to all "'type' => 'select'" column fields with "'renderType' => 'selectSingle'".
     */
    protected function configureSelectSingle(array $tca): array
    {
        foreach ($tca as &$tableDefinition) {
            if (!is_array($tableDefinition['columns'] ?? null)) {
                continue;
            }
            foreach ($tableDefinition['columns'] as &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? null) === 'select' && ($fieldConfig['config']['renderType'] ?? null) === 'selectSingle') {
                    // Hard set/override: 'maxitems' to 1, since selectSingle - as the name suggests - only
                    // allows a single item to be selected. Setting this config option allows to prevent
                    // further checks for the renderType, which should be avoided.
                    // @todo This should be solved by using a dedicated TCA type for selectSingle instead.
                    $fieldConfig['config']['maxitems'] = 1;
                }
            }
        }
        return $tca;
    }
}
