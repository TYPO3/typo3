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

namespace TYPO3\CMS\Backend\Form\Processor;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides shared select item related utility functions.
 *
 * @internal ext:backend internal implementation and not part of the public core API.
 * @todo Move additional shareable code from form-engine into here.
 */
#[Autoconfigure(public: true)]
final readonly class SelectItemProcessor
{
    public function __construct(
        private LanguageServiceFactory $languageServiceFactory,
    ) {}

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
     * @param array $allItems all resolved items including the ones from foreign_table values. The group ID information can be found in key ['group'] of an item.
     * @param array $definedGroups [config][itemGroups]
     * @param array $sortOrders [config][sortOrders]
     */
    public function groupAndSortItems(array $allItems, array $definedGroups, array $sortOrders): array
    {
        $allItems = $this->transformArrayToSelectItems($allItems);
        $groupedItems = [];
        // Append defined groups at first, as their order is prioritized
        $itemGroups = ['none' => ''];
        foreach ($definedGroups as $groupId => $groupLabel) {
            $itemGroups[$groupId] = $this->getLanguageService()->sL($groupLabel);
        }
        $currentGroup = 'none';
        // Extract --div-- into itemGroups
        foreach ($allItems as $item) {
            if ($item->isDivider()) {
                // A divider is added as a group (existing groups will get their label overridden)
                if ($item->hasGroup()) {
                    $currentGroup = $item->getGroup();
                    $itemGroups[$currentGroup] = $item->getLabel();
                } else {
                    $currentGroup = 'none';
                }
                continue;
            }
            // Put the given item in the currentGroup if no group has been given already
            if (!$item->hasGroup()) {
                $item = $item->withGroup($currentGroup);
            }
            $groupIdOfItem = $item->hasGroup() ? $item->getGroup() : 'none';
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
            return $this->transformSelectItemsToArray($allItems);
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
                $finalItems[] = ['label' => $groupLabel, 'value' => '--div--', 'group' => $groupId];
            }
            $finalItems = array_merge($finalItems, $itemsInGroup);
        }
        return $this->transformSelectItemsToArray($finalItems);
    }

    /**
     * @return SelectItem[]
     */
    public function transformArrayToSelectItems(array $items, string $type = 'select'): array
    {
        return array_map(static function (array|SelectItem $item) use ($type): SelectItem {
            if ($item instanceof SelectItem) {
                return $item;
            }
            return SelectItem::fromTcaItemArray($item, $type);
        }, $items);
    }

    public function transformSelectItemsToArray(array $items): array
    {
        return array_map(static function (array|SelectItem $item): array {
            if (!$item instanceof SelectItem) {
                return $item;
            }
            return $item->toArray();
        }, $items);
    }

    /**
     * Sort given items by label or value or a custom user function built like
     * "MyVendor\MyExtension\TcaSorter->sortItems" or a callable.
     *
     * @param SelectItem[] $items
     * @param array $sortOrders should be something like like [label => desc]
     * @return SelectItem[] the sorted items
     */
    protected function sortItems(array $items, array $sortOrders): array
    {
        foreach ($sortOrders as $order => $direction) {
            switch ($order) {
                case 'label':
                    $direction = strtolower($direction);
                    $collator = new \Collator((string)($this->getLanguageService()->getLocale() ?? 'en'));
                    @usort(
                        $items,
                        static function (SelectItem $item1, SelectItem $item2) use ($direction, $collator) {
                            if ($direction === 'desc') {
                                return $collator->compare($item1->getLabel(), $item2->getLabel()) <= 0;
                            }
                            return $collator->compare($item1->getLabel(), $item2->getLabel());
                        }
                    );
                    break;
                case 'value':
                    $direction = strtolower($direction);
                    $collator = new \Collator((string)($this->getLanguageService()->getLocale() ?? 'en'));
                    @usort(
                        $items,
                        static function (SelectItem $item1, SelectItem $item2) use ($direction, $collator) {
                            if ($direction === 'desc') {
                                return ($collator->compare((string)$item1->getValue(), (string)$item2->getValue()) <= 0) ? 1 : 0;
                            }
                            return $collator->compare((string)$item1->getValue(), (string)$item2->getValue());
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

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'] ?? $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser());
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
