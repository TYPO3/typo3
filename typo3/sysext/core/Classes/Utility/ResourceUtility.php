<?php
namespace TYPO3\CMS\Core\Utility;

/**
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
 * Utility class for the File Abstraction Layer (aka subpackage Resource in EXT:core)
 */
class ResourceUtility {
	/**
	 * This is a helper method that can be used with u?sort methods to sort a list of (relative) file paths, e.g.
	 * array("someDir/fileA", "fileA", "fileB", "anotherDir/fileA").
	 *
	 * Directories are sorted first in the lists, with the deepest structures first (while every level is sorted
	 * alphabetically)
	 *
	 * @param string $elementA
	 * @param string $elementB
	 * @return int
	 */
	public static function recursiveFileListSortingHelper($elementA, $elementB) {
		if (strpos($elementA, '/') === FALSE) {
			// first element is a file
			if (strpos($elementB, '/') === FALSE) {
				$result = strnatcasecmp($elementA, $elementB);
			} else {
				// second element is a directory => always sort it first
				$result = 1;
			}
		} else {
			// first element is a directory
			if (strpos($elementB, '/') === FALSE)  {
				// second element is a file => always sort it last
				$result = -1;
			} else {
				// both elements are directories => we have to recursively sort here
				list($pathPartA, $elementA) = explode('/', $elementA, 2);
				list($pathPartB, $elementB) = explode('/', $elementB, 2);

				if ($pathPartA === $pathPartB) {
					// same directory => sort by subpaths
					$result = self::recursiveFileListSortingHelper($elementA, $elementB);
				} else {
					// different directories => sort by current directories
					$result = strnatcasecmp($pathPartA, $pathPartB);
				}
			}
		}

		return $result;
	}
}
