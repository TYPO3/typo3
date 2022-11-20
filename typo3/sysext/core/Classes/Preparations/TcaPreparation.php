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

namespace TYPO3\CMS\Core\Preparations;

use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Prepare TCA. Used in bootstrap and Flex Form Data Structures.
 *
 * @internal Class and API may change any time.
 */
class TcaPreparation
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
     *
     * @param array $tca
     */
    public function prepare(array $tca): array
    {
        $tca = $this->configureCategoryRelations($tca);
        $tca = $this->configureFileReferences($tca);
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
     * set to "manyToMany" (which is the default).
     *
     * Finally all category fields with a "manyToMany" relationship are
     * added to the MM_oppositeUsage of sys_category "items".
     *
     * Important: Since this method defines a "foreign_table_where", this
     * must always be executed before prepareQuotingOfTableNamesAndColumnNames().
     *
     * @param array $tca
     */
    protected function configureCategoryRelations(array $tca): array
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

                // In case no relationship is given, fall back to "manyToMany"
                if (empty($fieldConfig['config']['relationship'])) {
                    $fieldConfig['config']['relationship'] = 'manyToMany';
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
                    // Therefore maxitems must be 1.
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
                } elseif ((int)($fieldConfig['config']['maxitems'] ?? 0) === 1) {
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
                // This will not be done for the sys_category table itself.
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

    protected function configureFileReferences(array $tca): array
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
                $fieldConfig['config'] = array_replace_recursive(
                    $fieldConfig['config'],
                    [
                        'foreign_table' => 'sys_file_reference',
                        'foreign_field' => 'uid_foreign',
                        'foreign_sortby' => 'sorting_foreign',
                        'foreign_table_field' => 'tablenames',
                        'foreign_match_fields' => [
                            'fieldname' => $fieldName,
                        ],
                        'foreign_label' => 'uid_local',
                        'foreign_selector' => 'uid_local',
                    ]
                );

                if (!empty(($allowed = ($fieldConfig['config']['allowed'] ?? null)))) {
                    $fieldConfig['config']['allowed'] = self::prepareFileExtensions($allowed);
                }
                if (!empty(($disallowed = ($fieldConfig['config']['disallowed'] ?? null)))) {
                    $fieldConfig['config']['disallowed'] = self::prepareFileExtensions($disallowed);
                }
            }
        }

        return $tca;
    }

    /**
     * Ensures format, replaces placeholders and remove duplicates
     *
     * @todo Does not need to be static, once FlexFormTools calls configureFileReferences() directly
     */
    public static function prepareFileExtensions(mixed $fileExtensions): string
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
}
