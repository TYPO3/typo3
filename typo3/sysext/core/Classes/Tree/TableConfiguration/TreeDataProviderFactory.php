<?php

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

namespace TYPO3\CMS\Core\Tree\TableConfiguration;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @param string $table
     * @param string $field
     * @param array $currentValue The current database row, handing over 'uid' is enough
     * @return DatabaseTreeDataProvider
     * @throws \InvalidArgumentException
     */
    public static function getDataProvider(array $tcaConfiguration, $table, $field, $currentValue)
    {
        /** @var DatabaseTreeDataProvider $dataProvider */
        $dataProvider = null;
        if (!isset($tcaConfiguration['treeConfig']) || !is_array($tcaConfiguration['treeConfig'])) {
            throw new \InvalidArgumentException('TCA Tree configuration is invalid: "treeConfig" array is missing', 1288215890);
        }

        if (!empty($tcaConfiguration['treeConfig']['dataProvider'])) {
            // This is a hack since TYPO3 v10 we use this to inject the EventDispatcher in the first argument
            // For TYPO3 Core, but this is only possible if the dataProvider is extending from the DatabaseTreeDataProvider
            // but did NOT use a custom constructor. This way, the original constructor receives the EventDispatcher properly
            // as first argument. It is encouraged to use a custom constructor that also receives the EventDispatcher
            // separately.
            $reflectionClass = new \ReflectionClass($tcaConfiguration['treeConfig']['dataProvider']);
            if ($reflectionClass->getConstructor()->getDeclaringClass()->getName() === DatabaseTreeDataProvider::class) {
                $dataProvider = GeneralUtility::makeInstance(
                    $tcaConfiguration['treeConfig']['dataProvider'],
                    GeneralUtility::makeInstance(EventDispatcherInterface::class)
                );
            } else {
                $dataProvider = GeneralUtility::makeInstance(
                    $tcaConfiguration['treeConfig']['dataProvider'],
                    $tcaConfiguration,
                    $table,
                    $field,
                    $currentValue,
                    GeneralUtility::makeInstance(EventDispatcherInterface::class)
                );
            }
        }
        $tcaConfiguration['internal_type'] = $tcaConfiguration['internal_type'] ?? 'db';
        if ($tcaConfiguration['internal_type'] === 'db') {
            if ($dataProvider === null) {
                $dataProvider = GeneralUtility::makeInstance(DatabaseTreeDataProvider::class);
            }
            if (isset($tcaConfiguration['foreign_table'])) {
                $tableName = $tcaConfiguration['foreign_table'];
                $dataProvider->setTableName($tableName);
                if ($tableName == $table) {
                    // The uid of the currently opened row can not be selected in a table relation to "self"
                    $unselectableUids = [$currentValue['uid']];
                    $dataProvider->setItemUnselectableList($unselectableUids);
                }
            } else {
                throw new \InvalidArgumentException('TCA Tree configuration is invalid: "foreign_table" not set', 1288215888);
            }
            if (isset($tcaConfiguration['foreign_label'])) {
                $dataProvider->setLabelField($tcaConfiguration['foreign_label']);
            } else {
                $dataProvider->setLabelField($GLOBALS['TCA'][$tableName]['ctrl']['label'] ?? '');
            }
            $dataProvider->setTreeId(md5($table . '|' . $field));

            $treeConfiguration = $tcaConfiguration['treeConfig'];
            if (isset($treeConfiguration['rootUid'])) {
                // @deprecated will be removed in v12
                $dataProvider->setRootUid((int)$treeConfiguration['rootUid']);
            }
            if (isset($treeConfiguration['startingPoints'])) {
                $dataProvider->setStartingPoints(array_unique(GeneralUtility::intExplode(',', $treeConfiguration['startingPoints'])));
            }
            if (isset($treeConfiguration['appearance']['expandAll'])) {
                $dataProvider->setExpandAll((bool)$treeConfiguration['appearance']['expandAll']);
            }
            if (isset($treeConfiguration['appearance']['maxLevels'])) {
                $dataProvider->setLevelMaximum((int)$treeConfiguration['appearance']['maxLevels']);
            }
            if (isset($treeConfiguration['appearance']['nonSelectableLevels'])) {
                $dataProvider->setNonSelectableLevelList($treeConfiguration['appearance']['nonSelectableLevels']);
            } elseif (isset($treeConfiguration['startingPoints'])) {
                // If there are more than 1 starting points, disable the first level. See description in DatabaseTreeProvider::loadTreeData()
                $dataProvider->setNonSelectableLevelList(substr_count($treeConfiguration['startingPoints'], ',') > 0 ? '0' : '');
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
        } elseif ($dataProvider === null) {
            throw new \InvalidArgumentException('TCA Tree configuration is invalid: tree for "internal_type=' . $tcaConfiguration['internal_type'] . '" not implemented yet', 1288215892);
        }
        return $dataProvider;
    }
}
