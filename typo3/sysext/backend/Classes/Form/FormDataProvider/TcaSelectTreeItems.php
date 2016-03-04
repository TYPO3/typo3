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
use TYPO3\CMS\Core\Tree\TableConfiguration\ExtJsArrayTreeRenderer;
use TYPO3\CMS\Core\Tree\TableConfiguration\TableConfigurationTree;
use TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolve select items, set processed item list in processedTca, sanitize and resolve database field
 */
class TcaSelectTreeItems extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * Resolve select items
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        $table = $result['tableName'];

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'select') {
                continue;
            }

            // Make sure we are only processing supported renderTypes
            if (!$this->isTargetRenderType($fieldConfig)) {
                continue;
            }

            $fieldConfig['config']['items'] = $this->sanitizeItemArray($fieldConfig['config']['items'], $table, $fieldName);
            $fieldConfig['config']['maxitems'] = $this->sanitizeMaxItems($fieldConfig['config']['maxitems']);

            $fieldConfig['config']['items'] = $this->addItemsFromSpecial($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->addItemsFromFolder($result, $fieldName, $fieldConfig['config']['items']);
            $staticItems = $fieldConfig['config']['items'];

            $fieldConfig['config']['items'] = $this->addItemsFromForeignTable($result, $fieldName, $fieldConfig['config']['items']);
            $dynamicItems = array_diff_key($fieldConfig['config']['items'], $staticItems);

            $fieldConfig['config']['items'] = $this->removeItemsByKeepItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->addItemsFromPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByRemoveItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);

            $fieldConfig['config']['items'] = $this->removeItemsByUserLanguageFieldRestriction($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByUserAuthMode($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByDoktypeUserRestriction($result, $fieldName, $fieldConfig['config']['items']);

            // Resolve "itemsProcFunc"
            if (!empty($fieldConfig['config']['itemsProcFunc'])) {
                $fieldConfig['config']['items'] = $this->resolveItemProcessorFunction($result, $fieldName, $fieldConfig['config']['items']);
                // itemsProcFunc must not be used anymore
                unset($fieldConfig['config']['itemsProcFunc']);
            }

            // Translate labels
            $fieldConfig['config']['items'] = $this->translateLabels($result, $fieldConfig['config']['items'], $table, $fieldName);

            $staticValues = $this->getStaticValues($fieldConfig['config']['items'], $dynamicItems);
            $result['databaseRow'][$fieldName] = $this->processDatabaseFieldValue($result['databaseRow'], $fieldName);
            $result['databaseRow'][$fieldName] = $this->processSelectFieldValue($result, $fieldName, $staticValues);

            // Keys may contain table names, so a numeric array is created
            $fieldConfig['config']['items'] = array_values($fieldConfig['config']['items']);

            // A couple of tree specific config parameters can be overwritten via page TS.
            // Pick those that influence the data fetching and write them into the config
            // given to the tree data provider
            if (isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['config.']['treeConfig.'])) {
                $pageTsConfig = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['config.']['treeConfig.'];
                // If rootUid is set in pageTsConfig, use it
                if (isset($pageTsConfig['rootUid'])) {
                    $fieldConfig['config']['treeConfig']['rootUid'] = (int)$pageTsConfig['rootUid'];
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
            }

            $fieldConfig['config']['treeData'] = $this->renderTree($result, $fieldConfig, $fieldName, $staticItems);

            $result['processedTca']['columns'][$fieldName] = $fieldConfig;
        }

        return $result;
    }

    /**
     * Renders the Ext JS tree.
     *
     * @param array $result The current result array.
     * @param array $fieldConfig The configuration of the current field.
     * @param string $fieldName The name of the current field.
     * @param array $staticItems The static items from the field config.
     * @return array The tree data configuration
     */
    protected function renderTree(array $result, array $fieldConfig, $fieldName, array $staticItems)
    {
        $allowedUids = [];
        foreach ($fieldConfig['config']['items'] as $item) {
            if ((int)$item[1] > 0) {
                $allowedUids[] = $item[1];
            }
        }

        $treeDataProvider = TreeDataProviderFactory::getDataProvider(
            $fieldConfig['config'],
            $result['tableName'],
            $fieldName,
            $result['databaseRow']
        );
        $treeDataProvider->setSelectedList(is_array($result['databaseRow'][$fieldName]) ? implode(',', $result['databaseRow'][$fieldName]) : $result['databaseRow'][$fieldName]);
        $treeDataProvider->setItemWhiteList($allowedUids);
        $treeDataProvider->initializeTreeData();

        /** @var ExtJsArrayTreeRenderer $treeRenderer */
        $treeRenderer = GeneralUtility::makeInstance(ExtJsArrayTreeRenderer::class);

        /** @var TableConfigurationTree $tree */
        $tree = GeneralUtility::makeInstance(TableConfigurationTree::class);
        $tree->setDataProvider($treeDataProvider);
        $tree->setNodeRenderer($treeRenderer);

        $treeItems = $this->prepareAdditionalItems($staticItems, $result['databaseRow'][$fieldName]);
        $treeItems[] = $tree->render();

        $treeConfig = [
            'items' => $treeItems,
            'selectedNodes' => $this->prepareSelectedNodes($fieldConfig['config']['items'], $result['databaseRow'][$fieldName])
        ];

        return $treeConfig;
    }

    /**
     * Prepare the additional items that get prepended to the tree as leaves
     *
     * @param array $itemArray
     * @param array $selectedNodes
     * @return array
     */
    protected function prepareAdditionalItems(array $itemArray, array $selectedNodes)
    {
        $additionalItems = [];

        foreach ($itemArray as $item) {
            if ($item[1] === '--div--') {
                continue;
            }

            $additionalItems[] = [
                'uid' => $item[1],
                'text' => $item[0],
                'selectable' => true,
                'leaf' => true,
                'checked' => in_array($item[1], $selectedNodes),
                'icon' => $item[3]
            ];
        }

        return $additionalItems;
    }

    /**
     * Re-create the old pipe based syntax of selected nodes for the ExtJS rendering part
     *
     * @param array $itemArray
     * @param array $databaseValues
     * @return array
     * @todo: this is ugly - should be removed with the tree rewrite
     */
    protected function prepareSelectedNodes(array $itemArray, array $databaseValues)
    {
        $selectedNodes = [];
        if (!empty($databaseValues)) {
            foreach ($databaseValues as $selectedNode) {
                foreach ($itemArray as $possibleSelectBoxItem) {
                    if ((string)$possibleSelectBoxItem[1] === (string)$selectedNode) {
                        $selectedNodes[] = $selectedNode . '|' . rawurlencode($possibleSelectBoxItem[0]);
                    }
                }
            }
        }

        return $selectedNodes;
    }

    /**
     * Determines whether the current field is a valid target for this DataProvider
     *
     * @param array $fieldConfig
     * @return bool
     */
    protected function isTargetRenderType(array $fieldConfig)
    {
        return $fieldConfig['config']['renderType'] === 'selectTree';
    }
}
