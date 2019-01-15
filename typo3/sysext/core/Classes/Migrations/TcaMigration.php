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
}
