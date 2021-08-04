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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Resolve select items, set processed item list in processedTca, sanitize and resolve database field
 */
class TcaSelectItems extends AbstractItemProvider implements FormDataProviderInterface
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

            $fieldConfig['config']['items'] = $this->sanitizeItemArray($fieldConfig['config']['items'] ?? [], $table, $fieldName);

            $fieldConfig['config']['maxitems'] = MathUtility::forceIntegerInRange($fieldConfig['config']['maxitems'] ?? 0, 0, 99999);
            if ($fieldConfig['config']['maxitems'] === 0) {
                $fieldConfig['config']['maxitems'] = 99999;
            }

            $fieldConfig['config']['items'] = $this->addItemsFromSpecial($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->addItemsFromFolder($result, $fieldName, $fieldConfig['config']['items']);

            $fieldConfig['config']['items'] = $this->addItemsFromForeignTable($result, $fieldName, $fieldConfig['config']['items']);

            // Resolve "itemsProcFunc"
            if (!empty($fieldConfig['config']['itemsProcFunc'])) {
                $fieldConfig['config']['items'] = $this->resolveItemProcessorFunction($result, $fieldName, $fieldConfig['config']['items']);
                // itemsProcFunc must not be used anymore
                unset($fieldConfig['config']['itemsProcFunc']);
            }

            // removing items before $dynamicItems and $removedItems have been built results in having them
            // not populated to the dynamic database row and displayed as "invalid value" in the forms view
            $fieldConfig['config']['items'] = $this->removeItemsByUserStorageRestriction($result, $fieldName, $fieldConfig['config']['items']);

            $removedItems = $fieldConfig['config']['items'];

            $fieldConfig['config']['items'] = $this->removeItemsByKeepItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->addItemsFromPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByRemoveItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);

            $fieldConfig['config']['items'] = $this->removeItemsByUserLanguageFieldRestriction($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByUserAuthMode($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByDoktypeUserRestriction($result, $fieldName, $fieldConfig['config']['items']);

            $removedItems = array_diff_key($removedItems, $fieldConfig['config']['items']);

            $currentDatabaseValuesArray = $this->processDatabaseFieldValue($result['databaseRow'], $fieldName);
            // Check if it's a new record to respect TCAdefaults
            if (!empty($fieldConfig['config']['MM']) && $result['command'] !== 'new') {
                // Getting the current database value on a mm relation doesn't make sense since the amount of selected
                // relations is stored in the field and not the uids of the items
                $currentDatabaseValuesArray = [];
            }

            $result['databaseRow'][$fieldName] = $currentDatabaseValuesArray;

            // add item values as keys to determine which items are stored in the database and should be preselected
            $itemArrayValues = array_column($fieldConfig['config']['items'], 1);
            $itemArray = array_fill_keys(
                $itemArrayValues,
                $fieldConfig['config']['items']
            );
            $result['databaseRow'][$fieldName] = $this->processSelectFieldValue($result, $fieldName, $itemArray);

            $fieldConfig['config']['items'] = $this->addInvalidItemsFromDatabase(
                $result,
                $table,
                $fieldName,
                $fieldConfig,
                $currentDatabaseValuesArray,
                $removedItems
            );

            // Translate labels and add icons
            // skip file of sys_file_metadata which is not rendered anyway but can use all memory
            if (!($table === 'sys_file_metadata' && $fieldName === 'file')) {
                $fieldConfig['config']['items'] = $this->translateLabels($result, $fieldConfig['config']['items'], $table, $fieldName);
                $fieldConfig['config']['items'] = $this->addIconFromAltIcons($result, $fieldConfig['config']['items'], $table, $fieldName);
            }

            // Keys may contain table names, so a numeric array is created
            $fieldConfig['config']['items'] = array_values($fieldConfig['config']['items']);

            $fieldConfig['config']['items'] = $this->groupAndSortItems(
                $fieldConfig['config']['items'],
                $fieldConfig['config']['itemGroups'] ?? [],
                $fieldConfig['config']['sortItems'] ?? []
            );

            $result['processedTca']['columns'][$fieldName] = $fieldConfig;
        }

        return $result;
    }

    /**
     * Add values that are currently listed in the database columns but not in the selectable items list
     * back to the list.
     *
     * @param array $result The current result array.
     * @param string $table The current table name
     * @param string $fieldName The current field name
     * @param array $fieldConf The configuration of the current field.
     * @param array $databaseValues The item values from the database, can contain invalid items!
     * @param array $removedItems Items removed by access checks and restrictions, must not be added as invalid values
     * @return array
     */
    public function addInvalidItemsFromDatabase(array $result, $table, $fieldName, array $fieldConf, array $databaseValues, array $removedItems)
    {
        // Early return if there are no items or invalid values should not be displayed
        if (empty($fieldConf['config']['items'])
            || $fieldConf['config']['renderType'] !== 'selectSingle'
            || ($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['disableNoMatchingValueElement'] ?? false)
            || ($fieldConf['config']['disableNoMatchingValueElement'] ?? false)
        ) {
            return $fieldConf['config']['items'];
        }

        $languageService = $this->getLanguageService();
        $noMatchingLabel = isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['noMatchingValue_label'])
            ? $languageService->sL(trim($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['noMatchingValue_label']))
            : '[ ' . $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue') . ' ]';

        $unmatchedValues = array_diff(
            array_values($databaseValues),
            array_column($fieldConf['config']['items'], 1),
            array_column($removedItems, 1)
        );

        foreach ($unmatchedValues as $unmatchedValue) {
            $invalidItem = [
                @sprintf($noMatchingLabel, $unmatchedValue),
                $unmatchedValue,
                null,
                'none', // put it in the very first position in the "none" group
            ];
            array_unshift($fieldConf['config']['items'], $invalidItem);
        }

        return $fieldConf['config']['items'];
    }

    /**
     * Determines whether the current field is a valid target for this DataProvider
     *
     * @param array $fieldConfig
     * @return bool
     */
    protected function isTargetRenderType(array $fieldConfig)
    {
        return $fieldConfig['config']['renderType'] !== 'selectTree';
    }

    /**
     * Is used when --div-- elements in the item list are used, or if groups are defined via "groupItems" config array.
     *
     * This method takes the --div-- elements out of the list, and adds them to the group lists.
     *
     * A main "none" group is added, which is always on top, when items are not set to be in a group.
     * All items without a groupId - which is defined by the fourth key of an item in the item array - are added
     * to the "none" group, or to the last group used previously, to ensure ordering as much as possible as before.
     *
     * Then the found groups are iterated over the order in the [itemGroups] list,
     * and items within a group can be sorted via "sortOrders" configuration.
     *
     * All grouped items are then "flattened" out and --div-- items are added for each group to keep backwards-compatibility.
     *
     * @param array $allItems all resolved items including the ones from foreign_table values. The group ID information can be found in fourth key [3] of an item.
     * @param array $definedGroups [config][itemGroups]
     * @param array $sortOrders [config][sortOrders]
     * @return array
     */
    protected function groupAndSortItems(array $allItems, array $definedGroups, array $sortOrders): array
    {
        $groupedItems = [];
        // Append defined groups at first, as their order is prioritized
        $itemGroups = ['none' => ''];
        foreach ($definedGroups as $groupId => $groupLabel) {
            $itemGroups[$groupId] = $this->getLanguageService()->sL($groupLabel);
        }
        $currentGroup = 'none';
        // Extract --div-- into itemGroups
        foreach ($allItems as $key => $item) {
            if ($item[1] === '--div--') {
                // A divider is added as a group (existing groups will get their label overridden)
                if (isset($item[3])) {
                    $currentGroup = $item[3];
                    $itemGroups[$currentGroup] = $item[0];
                } else {
                    $currentGroup = 'none';
                }
                continue;
            }
            // Put the given item in the currentGroup if no group has been given already
            if (!isset($item[3])) {
                $item[3] = $currentGroup;
            }
            $groupIdOfItem = !empty($item[3]) ? $item[3] : 'none';
            // It is still possible to have items that have an "unassigned" group, so they are moved to the "none" group
            if (!isset($itemGroups[$groupIdOfItem])) {
                $itemGroups[$groupIdOfItem] = '';
            }

            // Put the item in its corresponding group (and create it if it does not exist yet)
            if (!is_array($groupedItems[$groupIdOfItem] ?? null)) {
                $groupedItems[$groupIdOfItem] = [];
            }
            $groupedItems[$groupIdOfItem][] = $item;
        }
        // Only "none" = no grouping used explicitly via "itemGroups" or via "--div--"
        if (count($itemGroups) === 1) {
            if (!empty($sortOrders)) {
                $allItems = $this->sortItems($allItems, $sortOrders);
            }
            return $allItems;
        }

        // $groupedItems contains all items per group
        // $itemGroups contains all groups in order of each group

        // Let's add the --div-- items again ("unpacking")
        // And use the group ordering given by the itemGroups
        $finalItems = [];
        foreach ($itemGroups as $groupId => $groupLabel) {
            $itemsInGroup = $groupedItems[$groupId] ?? [];
            if (empty($itemsInGroup)) {
                continue;
            }
            // If sorting is defined, sort within each group now
            if (!empty($sortOrders)) {
                $itemsInGroup = $this->sortItems($itemsInGroup, $sortOrders);
            }
            // Add the --div-- if it is not the "none" default item
            if ($groupId !== 'none') {
                // Fall back to the groupId, if there is no label for it
                $groupLabel = $groupLabel ?: $groupId;
                $finalItems[] = [$groupLabel, '--div--', null, $groupId, null];
            }
            $finalItems = array_merge($finalItems, $itemsInGroup);
        }
        return $finalItems;
    }

    /**
     * Sort given items by label or value or a custom user function built like
     * "MyVendor\MyExtension\TcaSorter->sortItems" or a callable.
     *
     * @param array $items
     * @param array $sortOrders should be something like like [label => desc]
     * @return array the sorted items
     */
    protected function sortItems(array $items, array $sortOrders): array
    {
        foreach ($sortOrders as $order => $direction) {
            switch ($order) {
                case 'label':
                    $direction = strtolower($direction);
                    @usort(
                        $items,
                        static function ($item1, $item2) use ($direction) {
                            if ($direction === 'desc') {
                                return (strcasecmp($item1[0], $item2[0]) <= 0) ? 1 : 0;
                            }
                            return strcasecmp($item1[0], $item2[0]);
                        }
                    );
                    break;
                case 'value':
                    $direction = strtolower($direction);
                    @usort(
                        $items,
                        static function ($item1, $item2) use ($direction) {
                            if ($direction === 'desc') {
                                return (strcasecmp($item1[1], $item2[1]) <= 0) ? 1 : 0;
                            }
                            return strcasecmp($item1[1], $item2[1]);
                        }
                    );
                    break;
                default:
                    $reference = null;
                    GeneralUtility::callUserFunction($direction, $items, $reference);
            }
        }
        return $items;
    }
}
