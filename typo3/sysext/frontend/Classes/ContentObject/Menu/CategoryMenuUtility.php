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

namespace TYPO3\CMS\Frontend\ContentObject\Menu;

use TYPO3\CMS\Core\Collection\AbstractRecordCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Category\Collection\CategoryCollection;

/**
 * Utility class for menus based on category collections of pages.
 *
 * Returns all the relevant pages for rendering with a menu content object.
 * @internal this is only used for internal purposes and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class CategoryMenuUtility
{
    /**
     * @var string Name of the field used for sorting the pages
     */
    protected static $sortingField;

    /**
     * Collects all pages for the selected categories, sorted according to configuration.
     *
     * @param string $selectedCategories Comma-separated list of system categories primary keys
     * @param array $configuration TypoScript configuration for the "special." keyword
     * @param \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject $parentObject Back-reference to the calling object
     * @return array List of selected pages
     */
    public function collectPages($selectedCategories, $configuration, $parentObject)
    {
        $selectedPages = [];
        $categoriesPerPage = [];
        // Determine the name of the relation field
        $relationField = (string)$parentObject->getParentContentObject()->stdWrapValue('relation', $configuration ?? []);
        // Get the pages for each selected category
        $selectedCategories = GeneralUtility::intExplode(',', $selectedCategories, true);
        foreach ($selectedCategories as $aCategory) {
            $collection = CategoryCollection::load(
                $aCategory,
                true,
                'pages',
                $relationField
            );
            $categoryUid = 0;
            if ($collection instanceof AbstractRecordCollection) {
                $categoryUid = $collection->getUid();
            }
            // Loop on the results, overlay each page record found
            foreach ($collection as $pageItem) {
                $parentObject->getSysPage()->versionOL('pages', $pageItem, true);
                if (is_array($pageItem)) {
                    $selectedPages[$pageItem['uid']] = $parentObject->getSysPage()->getPageOverlay($pageItem);
                    // Keep a list of the categories each page belongs to
                    if (!isset($categoriesPerPage[$pageItem['uid']])) {
                        $categoriesPerPage[$pageItem['uid']] = [];
                    }
                    $categoriesPerPage[$pageItem['uid']][] = $categoryUid;
                }
            }
        }
        // Loop on the selected pages to add the categories they belong to, as comma-separated list of category uid's)
        // (this makes them available for rendering, if needed)
        foreach ($selectedPages as $uid => $pageRecord) {
            $selectedPages[$uid]['_categories'] = implode(',', $categoriesPerPage[$uid]);
        }

        // Sort the pages according to the sorting property
        self::$sortingField = (string)$parentObject->getParentContentObject()->stdWrapValue('sorting', $configuration ?? []);
        $order = (string)$parentObject->getParentContentObject()->stdWrapValue('order', $configuration ?? []);
        $selectedPages = $this->sortPages($selectedPages, $order);

        return $selectedPages;
    }

    /**
     * Sorts the selected pages
     *
     * If the sorting field is not defined or does not corresponding to an existing field
     * of the "pages" tables, the list of pages will remain unchanged.
     *
     * @param array $pages List of selected pages
     * @param string $order Order for sorting (should "asc" or "desc")
     * @return array Sorted list of pages
     */
    protected function sortPages($pages, $order)
    {
        // Perform the sorting only if a criterion was actually defined
        if (!empty(self::$sortingField)) {
            // Check that the sorting field exists (checking the first record is enough)
            $firstPage = current($pages);
            if (isset($firstPage[self::$sortingField])) {
                // Make sure the order property is either "asc" or "desc" (default is "asc")
                if (!empty($order)) {
                    $order = strtolower($order);
                    if ($order !== 'desc') {
                        $order = 'asc';
                    }
                }
                uasort(
                    $pages,
                    [
                        self::class,
                        'sortPagesUtility',
                    ]
                );
                // If the sort order is descending, reverse the sorted array
                if ($order === 'desc') {
                    $pages = array_reverse($pages, true);
                }
            }
        }
        return $pages;
    }

    /**
     * Static utility for sorting pages according to the selected criterion
     *
     * @param array $pageA Record for first page to be compared
     * @param array $pageB Record for second page to be compared
     * @return int -1 if first argument is smaller than second argument, 1 if first is greater than second and 0 if both are equal
     */
    public static function sortPagesUtility($pageA, $pageB)
    {
        return strnatcasecmp($pageA[self::$sortingField], $pageB[self::$sortingField]);
    }
}
