<?php
namespace TYPO3\CMS\Frontend\ContentObject\Menu;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Francois Suter <francois.suter@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility class for menus based on category collections of pages.
 *
 * Returns all the relevant pages for rendering with a menu content object.
 *
 * @author Francois Suter <francois.suter@typo3.org>
 */
class CategoryMenuUtility {
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
	 * @return string List of selected pages
	 */
	public function collectPages($selectedCategories, $configuration, $parentObject) {
		$selectedPages = array();
		$categoriesPerPage = array();
		// Determine the name of the relation field
		$relationField = '';
		if (isset($configuration['relation.'])) {
			$relationField = $parentObject->parent_cObj->stdWrap(
				$configuration['relation'],
				$configuration['relation.']
			);
		} elseif (isset($configuration['relation'])) {
			$relationField = $configuration['relation'];
		}
		// Get the pages for each selected category
		$selectedCategories = GeneralUtility::intExplode(',', $selectedCategories, TRUE);
		foreach ($selectedCategories as $aCategory) {
			$collection = \TYPO3\CMS\Core\Category\Collection\CategoryCollection::load(
				$aCategory,
				TRUE,
				'pages',
				$relationField
			);
			$categoryUid = $collection->getUid();
			// Loop on the results, overlay each page record found
			foreach ($collection as $pageItem) {
				$parentObject->getSysPage()->versionOL('pages', $pageItem, TRUE);
				if (is_array($pageItem)) {
					$selectedPages[$pageItem['uid']] = $parentObject->getSysPage()->getPageOverlay($pageItem);
					// Keep a list of the categories each page belongs to
					if (!isset($categoriesPerPage[$pageItem['uid']])) {
						$categoriesPerPage[$pageItem['uid']] = array();
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
		self::$sortingField = isset($configuration['sorting.']) ? $parentObject->getParentContentObject()->stdWrap($configuration['sorting'], $configuration['sorting.']) : $configuration['sorting'];
		$order = isset($configuration['order.']) ? $parentObject->getParentContentObject()->stdWrap($configuration['order'], $configuration['order.']) : $configuration['order'];
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
	protected function sortPages($pages, $order) {
		// Perform the sorting only if a criterion was actually defined
		if (!empty(self::$sortingField)) {
			// Check that the sorting field exists (checking the first record is enough)
			$firstPage = current($pages);
			if (isset($firstPage[self::$sortingField])) {
				// Make sure the order property is either "asc" or "desc" (default is "asc")
				if (!empty($order)) {
					$order = strtolower($order);
					if ($order != 'desc') {
						$order = 'asc';
					}
				}
				uasort(
					$pages,
					array(
						'\TYPO3\CMS\Frontend\ContentObject\Menu\CategoryMenuUtility',
						'sortPagesUtility'
					)
				);
				// If the sort order is descending, reverse the sorted array
				if ($order == 'desc') {
					$pages = array_reverse($pages, TRUE);
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
	 * @return array -1 if first argument is smaller than second argument, 1 if first is greater than second and 0 if both are equal
	 */
	static public function sortPagesUtility($pageA, $pageB) {
		return strnatcasecmp($pageA[self::$sortingField], $pageB[self::$sortingField]);
	}
}
