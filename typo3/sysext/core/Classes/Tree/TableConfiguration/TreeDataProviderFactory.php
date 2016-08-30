<?php
namespace TYPO3\CMS\Core\Tree\TableConfiguration;

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
 * Builds a \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider
 * object based on some TCA configuration
 */
class TreeDataProviderFactory
{
    /**
     * Gets the data provider, depending on TCA configuration
     *
     * @param array $tcaConfiguration
     * @param $table
     * @param $field
     * @param $currentValue
     * @return DatabaseTreeDataProvider
     * @throws \InvalidArgumentException
     */
    public static function getDataProvider(array $tcaConfiguration, $table, $field, $currentValue)
    {
        /** @var $dataProvider \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider */
        $dataProvider = null;
        if (!isset($tcaConfiguration['treeConfig']) | !is_array($tcaConfiguration['treeConfig'])) {
            throw new \InvalidArgumentException('TCA Tree configuration is invalid: "treeConfig" array is missing', 1288215890);
        }

        if (!empty($tcaConfiguration['treeConfig']['dataProvider'])) {
            $dataProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($tcaConfiguration['treeConfig']['dataProvider'], $tcaConfiguration, $table, $field, $currentValue);
        }
        if (!isset($tcaConfiguration['internal_type'])) {
            $tcaConfiguration['internal_type'] = 'db';
        }
        if ($tcaConfiguration['internal_type'] === 'db') {
            $unselectableUids = [];
            if ($dataProvider === null) {
                $dataProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::class);
            }
            if (isset($tcaConfiguration['foreign_table'])) {
                $tableName = $tcaConfiguration['foreign_table'];
                $dataProvider->setTableName($tableName);
                if ($tableName == $table) {
                    $unselectableUids[] = $currentValue['uid'];
                }
            } else {
                throw new \InvalidArgumentException('TCA Tree configuration is invalid: "foreign_table" not set', 1288215888);
            }
            if (isset($tcaConfiguration['foreign_label'])) {
                $dataProvider->setLabelField($tcaConfiguration['foreign_label']);
            } else {
                $dataProvider->setLabelField($GLOBALS['TCA'][$tableName]['ctrl']['label']);
            }
            $dataProvider->setTreeId(md5($table . '|' . $field));
            $dataProvider->setSelectedList($currentValue);

            $treeConfiguration = $tcaConfiguration['treeConfig'];
            if (isset($treeConfiguration['rootUid'])) {
                $dataProvider->setRootUid((int)$treeConfiguration['rootUid']);
            }
            if (isset($treeConfiguration['appearance']['expandAll'])) {
                $dataProvider->setExpandAll((bool)$treeConfiguration['appearance']['expandAll']);
            }
            if (isset($treeConfiguration['appearance']['maxLevels'])) {
                $dataProvider->setLevelMaximum((int)$treeConfiguration['appearance']['maxLevels']);
            }
            if (isset($treeConfiguration['appearance']['nonSelectableLevels'])) {
                $dataProvider->setNonSelectableLevelList($treeConfiguration['appearance']['nonSelectableLevels']);
            } elseif (isset($treeConfiguration['rootUid'])) {
                $dataProvider->setNonSelectableLevelList('');
            }
            if (isset($treeConfiguration['childrenField'])) {
                $dataProvider->setLookupMode(DatabaseTreeDataProvider::MODE_CHILDREN);
                $dataProvider->setLookupField($treeConfiguration['childrenField']);
            } elseif (isset($treeConfiguration['parentField'])) {
                $dataProvider->setLookupMode(DatabaseTreeDataProvider::MODE_PARENT);
                $dataProvider->setLookupField($treeConfiguration['parentField']);
            } else {
                throw new \InvalidArgumentException('TCA Tree configuration is invalid: neither "childrenField" nor "parentField" is set', 1288215889);
            }
            $dataProvider->setItemUnselectableList($unselectableUids);
        } elseif ($tcaConfiguration['internal_type'] === 'file' && $dataProvider === null) {
            // @todo Not implemented yet
            throw new \InvalidArgumentException('TCA Tree configuration is invalid: tree for "internal_type=file" not implemented yet', 1288215891);
        } elseif ($dataProvider === null) {
            throw new \InvalidArgumentException('TCA Tree configuration is invalid: tree for "internal_type=' . $tcaConfiguration['internal_type'] . '" not implemented yet', 1288215892);
        }
        return $dataProvider;
    }
}
