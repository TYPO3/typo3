<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Set or initialize configuration for inline fields in TCA
 */
class TcaInlineConfiguration implements FormDataProviderInterface
{
    /**
     * Find all inline fields and force proper configuration
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException If inline configuration is broken
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'inline') {
                continue;
            }

            // Throw if an inline field without foreign_table is set
            if (!isset($fieldConfig['config']['foreign_table'])) {
                throw new \UnexpectedValueException(
                    'Inline field ' . $fieldName . ' of table ' . $result['tableName'] . ' must have a foreign_table config',
                    1443793404
                );
            }

            $result = $this->initializeMinMaxItems($result, $fieldName);
            $result = $this->initializeLocalizationMode($result, $fieldName);
            $result = $this->initializeAppearance($result, $fieldName);
            $result = $this->addInlineSelectorAndUniqueConfiguration($result, $fieldName);
        }
        return $result;
    }

    /**
     * Set and validate minitems and maxitems in config
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     * @return array
     */
    protected function initializeMinMaxItems(array $result, $fieldName)
    {
        $config = $result['processedTca']['columns'][$fieldName]['config'];

        $minItems = 0;
        if (isset($config['minitems'])) {
            $minItems = MathUtility::forceIntegerInRange($config['minitems'], 0);
        }
        $result['processedTca']['columns'][$fieldName]['config']['minitems'] = $minItems;

        $maxItems = 100000;
        if (isset($config['maxitems'])) {
            $maxItems = MathUtility::forceIntegerInRange($config['maxitems'], 1);
        }
        $result['processedTca']['columns'][$fieldName]['config']['maxitems'] = $maxItems;

        return $result;
    }

    /**
     * Set appearance configuration
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     * @return array
     */
    protected function initializeAppearance(array $result, $fieldName)
    {
        $config = $result['processedTca']['columns'][$fieldName]['config'];
        if (!isset($config['appearance']) || !is_array($config['appearance'])) {
            // Init appearance if not set
            $config['appearance'] = [];
        }
        // Set the position/appearance of the "Create new record" link
        if (isset($config['foreign_selector']) && $config['foreign_selector']
            && (!isset($config['appearance']['useCombination']) || !$config['appearance']['useCombination'])
        ) {
            $config['appearance']['levelLinksPosition'] = 'none';
        } elseif (!isset($config['appearance']['levelLinksPosition'])
            || !in_array($config['appearance']['levelLinksPosition'], ['top', 'bottom', 'both', 'none'], true)
        ) {
            $config['appearance']['levelLinksPosition'] = 'top';
        }
        $config['appearance']['showPossibleLocalizationRecords']
            = isset($config['appearance']['showPossibleLocalizationRecords']) && $config['appearance']['showPossibleLocalizationRecords'];
        $config['appearance']['showRemovedLocalizationRecords']
            = isset($config['appearance']['showRemovedLocalizationRecords']) && $config['appearance']['showRemovedLocalizationRecords'];
        // Defines which controls should be shown in header of each record
        $enabledControls = [
            'info' => true,
            'new' => true,
            'dragdrop' => true,
            'sort' => true,
            'hide' => true,
            'delete' => true,
            'localize' => true
        ];
        if (isset($config['appearance']['enabledControls']) && is_array($config['appearance']['enabledControls'])) {
            $config['appearance']['enabledControls'] = array_merge($enabledControls, $config['appearance']['enabledControls']);
        } else {
            $config['appearance']['enabledControls'] = $enabledControls;
        }
        $result['processedTca']['columns'][$fieldName]['config'] = $config;

        return $result;
    }

    /**
     * Set localization mode. This will end up with localizationMode to be set to either 'select', 'keep'
     * or 'none' if the handled record is a localized record.
     *
     * @see TcaInline for a detailed explanation on the meaning of these modes.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     * @throws \UnexpectedValueException If localizationMode configuration is broken
     */
    protected function initializeLocalizationMode(array $result, $fieldName)
    {
        if ($result['defaultLanguageRow'] === null) {
            // Currently handled parent is a localized row if a former provider added the "default" row
            // If handled record is not localized, set localizationMode to 'none' and return
            $result['processedTca']['columns'][$fieldName]['config']['behaviour']['localizationMode'] = 'none';
            return $result;
        }

        $childTableName = $result['processedTca']['columns'][$fieldName]['config']['foreign_table'];
        $parentConfig = $result['processedTca']['columns'][$fieldName]['config'];

        $isChildTableLocalizable = false;
        if (isset($GLOBALS['TCA'][$childTableName]['ctrl']) && is_array($GLOBALS['TCA'][$childTableName]['ctrl'])
            && isset($GLOBALS['TCA'][$childTableName]['ctrl']['languageField'])
            && $GLOBALS['TCA'][$childTableName]['ctrl']['languageField']
            && isset($GLOBALS['TCA'][$childTableName]['ctrl']['transOrigPointerField'])
            && $GLOBALS['TCA'][$childTableName]['ctrl']['transOrigPointerField']
        ) {
            $isChildTableLocalizable = true;
        }

        $mode = null;

        if (isset($parentConfig['behaviour']['localizationMode'])) {
            // Use explicit set mode, but validate before use
            // Use  mode if set, but throw if not set to either 'select' or 'keep'
            if ($parentConfig['behaviour']['localizationMode'] !== 'keep' && $parentConfig['behaviour']['localizationMode'] !== 'select') {
                throw new \UnexpectedValueException(
                    'localizationMode of table ' . $result['tableName'] . ' field ' . $fieldName . ' is not valid, set to either \'keep\' or \'select\'',
                    1443829370
                );
            }
            // Throw if is set to select, but child can not be localized
            if ($parentConfig['behaviour']['localizationMode'] === 'select' && !$isChildTableLocalizable) {
                throw new \UnexpectedValueException(
                    'Wrong configuration: localizationMode of table ' . $result['tableName'] . ' field ' . $fieldName . ' is set to \'select\', but table is not localizable.',
                    1443944274
                );
            }
            $mode = $parentConfig['behaviour']['localizationMode'];
        } else {
            // Not set explicitly -> use "none"
            $mode = 'none';
            if ($isChildTableLocalizable) {
                // Except if child is localizable, then use "select"
                $mode = 'select';
            }
        }

        $result['processedTca']['columns'][$fieldName]['config']['behaviour']['localizationMode'] = $mode;
        return $result;
    }

    /**
     * If foreign_selector or foreign_unique is set, this points to a field configuration of the child
     * table. The InlineControlContainer may render a drop down field or an element browser later from this.
     *
     * Fetch configuration from child table configuration, sanitize and merge with
     * foreign_selector_fieldTcaOverride that allows overriding this field definition again.
     *
     * Final configuration is written to selectorOrUniqueConfiguration of inline config section.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     * @throws \UnexpectedValueException If configuration is broken
     */
    protected function addInlineSelectorAndUniqueConfiguration(array $result, $fieldName)
    {
        $config = $result['processedTca']['columns'][$fieldName]['config'];

        // Early return if neither foreign_unique nor foreign_selector are set
        if (!isset($config['foreign_unique']) && !isset($config['foreign_selector'])) {
            return $result;
        }

        // If both are set, they must point to the same field
        if (isset($config['foreign_unique']) && isset($config['foreign_selector'])
            && $config['foreign_unique'] !== $config['foreign_selector']
        ) {
            throw new \UnexpectedValueException(
                'Table ' . $result['tableName'] . ' field ' . $fieldName . ': If both foreign_unique and'
                . ' foreign_selector are set, they must point to the same field',
                1444995464
            );
        }

        if (isset($config['foreign_unique'])) {
            $fieldNameInChildConfiguration = $config['foreign_unique'];
        } else {
            $fieldNameInChildConfiguration = $config['foreign_selector'];
        }

        // Throw if field name in globals does not exist or is not of type select or group
        if (!isset($GLOBALS['TCA'][$config['foreign_table']]['columns'][$fieldNameInChildConfiguration]['config']['type'])
            || ($GLOBALS['TCA'][$config['foreign_table']]['columns'][$fieldNameInChildConfiguration]['config']['type'] !== 'select'
                && $GLOBALS['TCA'][$config['foreign_table']]['columns'][$fieldNameInChildConfiguration]['config']['type'] !== 'group')
        ) {
            throw new \UnexpectedValueException(
                'Table ' . $result['tableName'] . ' field ' . $fieldName . ' points in foreign_selector or foreign_unique'
                . ' to field ' . $fieldNameInChildConfiguration . ' of table ' . $config['foreign_table'] . ', but this field'
                . ' is either not defined or is not of type select or group',
                1444996537
            );
        }

        $selectorOrUniqueConfiguration = [
            'config' => $GLOBALS['TCA'][$config['foreign_table']]['columns'][$fieldNameInChildConfiguration]['config'],
        ];

        // Throw if field is type group, but not internal_type db
        if ($selectorOrUniqueConfiguration['config']['type'] === 'group'
            && (!isset($selectorOrUniqueConfiguration['config']['internal_type']) ||  $selectorOrUniqueConfiguration['config']['internal_type'] !== 'db')) {
            throw new \UnexpectedValueException(
                'Table ' . $result['tableName'] . ' field ' . $fieldName . ' points in foreign_selector or foreign_unique'
                . ' to field ' . $fieldNameInChildConfiguration . ' of table ' . $config['foreign_table'] . '. This field'
                . ' is of type group and must be of internal_type db, which is not the case',
                1444999130
            );
        }

        // Merge foreign_selector_fieldTcaOverride if given
        if (isset($config['foreign_selector'])
            && isset($config['foreign_selector_fieldTcaOverride']['config'])
            && is_array($config['foreign_selector_fieldTcaOverride']['config'])
        ) {
            ArrayUtility::mergeRecursiveWithOverrule($selectorOrUniqueConfiguration['config'], $config['foreign_selector_fieldTcaOverride']['config']);
        }

        // Add field name to config for easy access later
        $selectorOrUniqueConfiguration['fieldName'] = $fieldNameInChildConfiguration;

        // Add remote table name for easy access later
        if ($selectorOrUniqueConfiguration['config']['type'] === 'select') {
            if (!isset($selectorOrUniqueConfiguration['config']['foreign_table'])) {
                throw new \UnexpectedValueException(
                    'Table ' . $result['tableName'] . ' field ' . $fieldName . ' points in foreign_selector or foreign_unique'
                    . ' to field ' . $fieldNameInChildConfiguration . ' of table ' . $config['foreign_table'] . '. This field'
                    . ' is of type select and must define foreign_table',
                    1445078627
                );
            }
            $foreignTable = $selectorOrUniqueConfiguration['config']['foreign_table'];
        } else {
            if (!isset($selectorOrUniqueConfiguration['config']['allowed'])) {
                throw new \UnexpectedValueException(
                    'Table ' . $result['tableName'] . ' field ' . $fieldName . ' points in foreign_selector or foreign_unique'
                    . ' to field ' . $fieldNameInChildConfiguration . ' of table ' . $config['foreign_table'] . '. This field'
                    . ' is of type select and must define allowed',
                    1445078628
                );
            }
            $foreignTable = $selectorOrUniqueConfiguration['config']['allowed'];
        }
        $selectorOrUniqueConfiguration['foreignTable'] = $foreignTable;

        // If this is a foreign_selector field, mark it as such for data fetching later
        $selectorOrUniqueConfiguration['isSelector'] = false;
        if (isset($config['foreign_selector'])) {
            $selectorOrUniqueConfiguration['isSelector'] = true;
        }

        // If this is a foreign_unique field, mark it a such for unique data fetching later
        $selectorOrUniqueConfiguration['isUnique'] = false;
        if (isset($config['foreign_unique'])) {
            $selectorOrUniqueConfiguration['isUnique'] = true;
        }

        // Add field configuration to inline configuration
        $result['processedTca']['columns'][$fieldName]['config']['selectorOrUniqueConfiguration'] = $selectorOrUniqueConfiguration;

        return $result;
    }
}
