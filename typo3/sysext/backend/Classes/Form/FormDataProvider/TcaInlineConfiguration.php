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
            $result = $this->initializeChildrenLanguage($result, $fieldName);
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

        $maxItems = 99999;
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
            'localize' => true,
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
     * Set default value for child records 'sys_language_uid' field. This is relevant if a localized
     * parent is edited and a child is added via the ajax call. The child should then have the same
     * sys_language_uid as the parent.
     * The method verifies if the parent is a localized parent, and writes the current languageField
     * value into TCA ['config']['inline']['parentSysLanguageUid'] of the parent inline TCA field. The whole
     * ['config'] section is transferred to the 'create new child' ajax controller, the value is then used within
     * 'DatabaseRowInitializeNew' data provider to initialize the child languageField value with that value.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     */
    protected function initializeChildrenLanguage(array $result, $fieldName)
    {
        $childTableName = $result['processedTca']['columns'][$fieldName]['config']['foreign_table'];

        if (empty($result['processedTca']['ctrl']['languageField'])
            || empty($GLOBALS['TCA'][$childTableName]['ctrl']['languageField'])
        ) {
            return $result;
        }

        $parentConfig = $result['processedTca']['columns'][$fieldName]['config'];

        $parentLanguageField = $result['processedTca']['ctrl']['languageField'];
        if (!isset($parentConfig['inline']['parentSysLanguageUid'])
            && isset($result['databaseRow'][$parentLanguageField])
        ) {
            if (is_array($result['databaseRow'][$parentLanguageField])) {
                $result['processedTca']['columns'][$fieldName]['config']['inline']['parentSysLanguageUid']
                    = (int)$result['databaseRow'][$parentLanguageField][0];
            } else {
                $result['processedTca']['columns'][$fieldName]['config']['inline']['parentSysLanguageUid']
                    = (int)$result['databaseRow'][$parentLanguageField];
            }
        }

        return $result;
    }

    /**
     * If foreign_selector or foreign_unique is set, this points to a field configuration of the child
     * table. The InlineControlContainer may render a drop down field or an element browser later from this.
     *
     * Fetch configuration from child table configuration, sanitize and merge with
     * overrideChildTca of foreign_selector if given that allows overriding this field definition again.
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
            && (!isset($selectorOrUniqueConfiguration['config']['internal_type']) || $selectorOrUniqueConfiguration['config']['internal_type'] !== 'db')
        ) {
            throw new \UnexpectedValueException(
                'Table ' . $result['tableName'] . ' field ' . $fieldName . ' points in foreign_selector or foreign_unique'
                . ' to field ' . $fieldNameInChildConfiguration . ' of table ' . $config['foreign_table'] . '. This field'
                . ' is of type group and must be of internal_type db, which is not the case',
                1444999130
            );
        }

        // Merge overrideChildTca of foreign_selector if given
        if (isset($config['foreign_selector'], $config['overrideChildTca']['columns'][$config['foreign_selector']]['config'])
            && is_array($config['overrideChildTca']['columns'][$config['foreign_selector']]['config'])
        ) {
            $selectorOrUniqueConfiguration['config'] = array_replace_recursive($selectorOrUniqueConfiguration['config'], $config['overrideChildTca']['columns'][$config['foreign_selector']]['config']);
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
