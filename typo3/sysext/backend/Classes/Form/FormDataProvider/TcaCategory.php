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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Tree\TableConfiguration\ArrayTreeRenderer;
use TYPO3\CMS\Core\Tree\TableConfiguration\TableConfigurationTree;
use TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Data provider for type=category
 *
 * Used in combination with CategoryElement to create the base HTML for the category tree.
 *
 * Used in combination with FormSelectTreeAjaxController to fetch the final tree list, this
 * is triggered if $result['selectTreeCompileItems'] is set to true. This way the tree item
 * calculation is only triggered if needed in this ajax context. Writes the prepared item
 * array to ['config']['items'] in this case.
 */
class TcaCategory extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * Sanitize config options and resolve category items if requested.
     */
    public function addData(array $result): array
    {
        $table = $result['tableName'];

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            // This data provider only works for type=category
            if (($fieldConfig['config']['type'] ?? '') !== 'category') {
                continue;
            }

            // Make sure we are only processing supported renderTypes
            if (!$this->isTargetRenderType($fieldConfig)) {
                continue;
            }

            $fieldConfig = $this->initializeDefaultFieldConfig($fieldConfig);
            $fieldConfig = $this->parseStartingPointsFromSiteConfiguration($result, $fieldConfig);
            $fieldConfig = $this->overrideConfigFromPageTSconfig($result, $table, $fieldName, $fieldConfig);

            // Prepare the list of currently selected nodes using RelationHandler
            // This is needed to ensure a correct value initialization before the actual tree is loaded
            $result['databaseRow'][$fieldName] = $this->processDatabaseFieldValue($result['databaseRow'], $fieldName);
            $result['databaseRow'][$fieldName] = $this->processCategoryFieldValue($result, $fieldName);

            // Since AbstractItemProvider does sometimes access $result[...][config] instead of
            // our updated $fieldConfig, we have to assign it here and from now on, only work
            // with the $result[...][config] array.
            $result['processedTca']['columns'][$fieldName] = $fieldConfig;

            // This is usually only executed in an ajax request
            if ($result['selectTreeCompileItems'] ?? false) {
                // Fetch static items from TCA and TSconfig. Since this is
                // not supported, throw an exception if something was found.
                $staticItems = $this->sanitizeItemArray($result['processedTca']['columns'][$fieldName]['config']['items'] ?? [], $table, $fieldName);
                $tsConfigItems = $this->addItemsFromPageTsConfig($result, $fieldName, []);
                if ($staticItems !== [] || $tsConfigItems !== []) {
                    throw new \RuntimeException(
                        'Static items are not supported for field ' . $fieldName . ' from table ' . $table . ' with type category',
                        1627336557
                    );
                }

                // Fetch the list of all possible "related" items and apply processing
                // @todo: This uses 4th param 'true' as hack to add the full item rows speeding up
                //        processing of items in the tree class construct below. Simplify the construct:
                //        The entire $treeDataProvider / $treeRenderer / $tree construct should probably
                //        vanish and the tree processing could happen here in the data provider? Watch
                //        out for the permission event in the tree construct when doing this.
                $dynamicItems = $this->addItemsFromForeignTable($result, $fieldName, [], true);
                // Remove items as configured via TsConfig
                $dynamicItems = $this->removeItemsByKeepItemsPageTsConfig($result, $fieldName, $dynamicItems);
                $dynamicItems = $this->removeItemsByRemoveItemsPageTsConfig($result, $fieldName, $dynamicItems);
                // Finally, the only data needed for the tree code are the valid uids of the possible records
                $uidListOfAllDynamicItems = array_map('intval', array_filter(
                    array_values(array_column($dynamicItems, 'value')),
                    static fn($uid) => (int)$uid > 0
                ));
                $fullRowsOfDynamicItems = [];
                foreach ($dynamicItems as $item) {
                    // @todo: Prepare performance hack for tree calculation below.
                    if (isset($item['_row'])) {
                        $fullRowsOfDynamicItems[(int)$item['_row']['uid']] = $item['_row'];
                    }
                }
                // Initialize the tree data provider
                $treeDataProvider = TreeDataProviderFactory::getDataProvider(
                    $result['processedTca']['columns'][$fieldName]['config'],
                    $table,
                    $fieldName,
                    $result['databaseRow']
                );
                $treeDataProvider->setSelectedList(implode(',', $result['databaseRow'][$fieldName]));
                // Basically the tree data provider fetches all tree nodes again and
                // then verifies if a given rows' uid is within the item whitelist.
                // @todo: Simplify construct, probably remove entirely. See @todo above as well.
                $treeDataProvider->setAvailableItems($fullRowsOfDynamicItems);
                $treeDataProvider->setItemWhiteList($uidListOfAllDynamicItems);
                $treeDataProvider->initializeTreeData();
                $treeRenderer = GeneralUtility::makeInstance(ArrayTreeRenderer::class);
                $tree = GeneralUtility::makeInstance(TableConfigurationTree::class);
                $tree->setDataProvider($treeDataProvider);
                $tree->setNodeRenderer($treeRenderer);

                // Add the calculated tree nodes
                $result['processedTca']['columns'][$fieldName]['config']['items'] = $tree->render();
            }
        }

        return $result;
    }

    /**
     * A couple of tree specific config parameters can be overwritten via page TS.
     * Pick those that influence the data fetching and write them into the config
     * given to the tree data provider.
     */
    protected function overrideConfigFromPageTSconfig(
        array $result,
        string $table,
        string $fieldName,
        array $fieldConfig
    ): array {
        $pageTsConfig = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['config.']['treeConfig.'] ?? [];

        if (!is_array($pageTsConfig) || $pageTsConfig === []) {
            return $fieldConfig;
        }

        if (isset($pageTsConfig['startingPoints'])) {
            $fieldConfig['config']['treeConfig']['startingPoints'] = implode(',', array_unique(GeneralUtility::intExplode(',', (string)$pageTsConfig['startingPoints'])));
        }
        if (isset($pageTsConfig['appearance.']['expandAll'])) {
            $fieldConfig['config']['treeConfig']['appearance']['expandAll'] = (bool)$pageTsConfig['appearance.']['expandAll'];
        }
        if (isset($pageTsConfig['appearance.']['maxLevels'])) {
            $fieldConfig['config']['treeConfig']['appearance']['maxLevels'] = (int)$pageTsConfig['appearance.']['maxLevels'];
        }
        if (isset($pageTsConfig['appearance.']['nonSelectableLevels'])) {
            $fieldConfig['config']['treeConfig']['appearance']['nonSelectableLevels'] = $pageTsConfig['appearance.']['nonSelectableLevels'];
        }

        return $fieldConfig;
    }

    /**
     * Validate and sanitize the category field value.
     */
    protected function processCategoryFieldValue(array $result, string $fieldName): array
    {
        $fieldConfig = $result['processedTca']['columns'][$fieldName];
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);

        $newDatabaseValueArray = [];
        $currentDatabaseValueArray = array_key_exists($fieldName, $result['databaseRow']) ? $result['databaseRow'][$fieldName] : [];

        if (!empty($fieldConfig['config']['MM']) && $result['command'] !== 'new') {
            $relationHandler->start(
                implode(',', $currentDatabaseValueArray),
                $fieldConfig['config']['foreign_table'],
                $fieldConfig['config']['MM'],
                $result['databaseRow']['uid'],
                $result['tableName'],
                $fieldConfig['config']
            );
            $newDatabaseValueArray = array_merge($newDatabaseValueArray, $relationHandler->getValueArray());
        } else {
            // If not dealing with MM relations, use default live uid, not versioned uid for record relations
            $relationHandler->start(
                implode(',', $currentDatabaseValueArray),
                $fieldConfig['config']['foreign_table'],
                '',
                $this->getLiveUid($result),
                $result['tableName'],
                $fieldConfig['config']
            );
            $databaseIds = array_merge($newDatabaseValueArray, $relationHandler->getValueArray());
            // remove all items from the current DB values if not available as relation
            $newDatabaseValueArray = array_values(array_intersect($currentDatabaseValueArray, $databaseIds));
        }

        // Since only uids are allowed, the array must be unique
        return array_unique($newDatabaseValueArray);
    }

    protected function isTargetRenderType($fieldConfig): bool
    {
        // Type category does not support any renderType
        return !isset($fieldConfig['config']['renderType']);
    }

    protected function initializeDefaultFieldConfig(array $fieldConfig): array
    {
        $fieldConfig =  array_replace_recursive([
            'config' => [
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => [
                        'expandAll' => true,
                        'showHeader' => true,
                        'maxLevels' => 99,
                    ],
                ],
            ],
        ], $fieldConfig);

        // Calculate maxitems value, while 0 will fall back to 99999
        $fieldConfig['config']['maxitems'] = MathUtility::forceIntegerInRange(
            $fieldConfig['config']['maxitems'] ?? 0,
            0,
            99999
        ) ?: 99999;

        return $fieldConfig;
    }
}
