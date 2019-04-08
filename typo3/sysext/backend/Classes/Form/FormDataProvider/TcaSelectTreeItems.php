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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Tree\TableConfiguration\ArrayTreeRenderer;
use TYPO3\CMS\Core\Tree\TableConfiguration\TableConfigurationTree;
use TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Data provider for type=select + renderType=selectTree fields.
 *
 * Used in combination with SelectTreeElement to create the base HTML for trees,
 * does a little bit of sanitation and preparation then.
 *
 * Used in combination with FormSelectTreeAjaxController to fetch the final tree list, this is
 * triggered if $result['selectTreeCompileItems'] is set to true. This way the tree item
 * calculation is only triggered if needed in this ajax context. Writes the prepared
 * item array to ['config']['items'] in this case.
 */
class TcaSelectTreeItems extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * Sanitize config options and resolve select items if requested.
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

            $fieldConfig['config']['maxitems'] = MathUtility::forceIntegerInRange($fieldConfig['config']['maxitems'], 0, 99999);
            if ($fieldConfig['config']['maxitems'] === 0) {
                $fieldConfig['config']['maxitems'] = 99999;
            }

            // A couple of tree specific config parameters can be overwritten via page TS.
            // Pick those that influence the data fetching and write them into the config
            // given to the tree data provider. This is additionally used in SelectTreeElement, so always do that.
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

            // Prepare the list of currently selected nodes using RelationHandler
            // This is needed to ensure a correct value initialization before the actual tree is loaded
            $result['databaseRow'][$fieldName] = $this->processDatabaseFieldValue($result['databaseRow'], $fieldName);
            $result['databaseRow'][$fieldName] = $this->processSelectFieldValue($result, $fieldName, []);

            if ($result['selectTreeCompileItems']) {
                $finalItems = [];

                // Prepare the list of "static" items if there are any.
                // "static" and "dynamic" is separated since the tree code only copes with "real" existing foreign nodes,
                // so this "static" stuff allows defining tree items that don't really exist in the tree.
                $itemsFromTca = $this->sanitizeItemArray($fieldConfig['config']['items'], $table, $fieldName);

                // List of additional items defined by page ts config "addItems"
                $itemsFromPageTsConfig = $this->addItemsFromPageTsConfig($result, $fieldName, []);
                // Resolve pageTsConfig item icons to markup
                $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                $finalPageTsConfigItems = [];
                foreach ($itemsFromPageTsConfig as $item) {
                    if ($item[2] !== null) {
                        $item[2] = $iconFactory->getIcon($item[2], Icon::SIZE_SMALL)->getMarkup('inline');
                    }
                    $finalPageTsConfigItems[] = $item;
                }

                if (!empty($itemsFromTca) || !empty($finalPageTsConfigItems)) {
                    // First apply "keepItems" to $itemsFromTca, this will restrict the tca item list to only
                    // those items that are defined in page ts "keepItems" if given
                    $itemsFromTca = $this->removeItemsByKeepItemsPageTsConfig($result, $fieldName, $itemsFromTca);
                    // Then, merge the items from page ts "addItems" into item list, since "addItems" should
                    // add additional items even if they are not in the "keepItems" list
                    $staticItems = array_merge($itemsFromTca, $finalPageTsConfigItems);
                    // Now apply page ts config "removeItems", so this is *after* addItems, so "removeItems" could
                    // possibly remove items again that were added via "addItems"
                    $staticItems = $this->removeItemsByRemoveItemsPageTsConfig($result, $fieldName, $staticItems);
                    // Now, apply user and access right restrictions to this item list
                    $staticItems = $this->removeItemsByUserLanguageFieldRestriction($result, $fieldName, $staticItems);
                    $staticItems = $this->removeItemsByUserAuthMode($result, $fieldName, $staticItems);
                    $staticItems = $this->removeItemsByDoktypeUserRestriction($result, $fieldName, $staticItems);
                    // Call itemsProcFunc if given. Note this function does *not* see the "dynamic" list of items
                    if (!empty($fieldConfig['config']['itemsProcFunc'])) {
                        $staticItems = $this->resolveItemProcessorFunction($result, $fieldName, $staticItems);
                        // itemsProcFunc must not be used anymore
                        unset($fieldConfig['config']['itemsProcFunc']);
                    }
                    // And translate any labels from the static list
                    $staticItems = $this->translateLabels($result, $staticItems, $table, $fieldName);
                    // Now compile the target items using the same array structure as the "dynamic" list below
                    foreach ($staticItems as $item) {
                        if ($item[1] === '--div--') {
                            // Skip divs that may occur here for whatever reason
                            continue;
                        }
                        $finalItems[] = [
                            'identifier' => $item[1],
                            'name' => $item[0],
                            'icon' => $item[2] ?? '',
                            'iconOverlay' => '',
                            'depth' => 0,
                            'hasChildren' => false,
                            'selectable' => true,
                            'checked' => in_array($item[1], $result['databaseRow'][$fieldName]),
                        ];
                    }
                }

                // Fetch the list of all possible "related" items (yuk!) and apply a similar processing as with the "static" list
                $dynamicItems = $this->addItemsFromForeignTable($result, $fieldName, []);
                $dynamicItems = $this->removeItemsByKeepItemsPageTsConfig($result, $fieldName, $dynamicItems);
                $dynamicItems = $this->removeItemsByRemoveItemsPageTsConfig($result, $fieldName, $dynamicItems);
                $dynamicItems = $this->removeItemsByUserLanguageFieldRestriction($result, $fieldName, $dynamicItems);
                $dynamicItems = $this->removeItemsByUserAuthMode($result, $fieldName, $dynamicItems);
                $dynamicItems = $this->removeItemsByDoktypeUserRestriction($result, $fieldName, $dynamicItems);
                // Funnily, the only data needed for the tree code are the uids of the possible records (yuk!) - get them
                $uidListOfAllDynamicItems = [];
                foreach ($dynamicItems as $item) {
                    if ((int)$item[1] > 0) {
                        $uidListOfAllDynamicItems[] = (int)$item[1];
                    }
                }
                // Now kick in this tree stuff
                $treeDataProvider = TreeDataProviderFactory::getDataProvider(
                    $fieldConfig['config'],
                    $table,
                    $fieldName,
                    $result['databaseRow']
                );
                $treeDataProvider->setSelectedList(implode(',', $result['databaseRow'][$fieldName]));
                // Basically the tree foo fetches all tree nodes again (aaargs), then verifies if
                // a given rows uid is within this "list of allowed uids". It then creates an object
                // tree representing the nested tree, just to collapse all that to a flat array again. Yay ...
                $treeDataProvider->setItemWhiteList($uidListOfAllDynamicItems);
                $treeDataProvider->initializeTreeData();
                $treeRenderer = GeneralUtility::makeInstance(ArrayTreeRenderer::class);
                $tree = GeneralUtility::makeInstance(TableConfigurationTree::class);
                $tree->setDataProvider($treeDataProvider);
                $tree->setNodeRenderer($treeRenderer);

                // Merge tree nodes after calculated nodes from static items
                $fieldConfig['config']['items'] = array_merge($finalItems, $tree->render());
            }

            $result['processedTca']['columns'][$fieldName] = $fieldConfig;
        }

        return $result;
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
