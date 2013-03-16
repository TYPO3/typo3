<?php
namespace TYPO3\CMS\IndexedSearch\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Dmitry Dulepov (dmitry@typo3.com)
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
/**
 * Class with common methods used across various classes in the indexed search.
 * Impementation is provided by various people from the TYPO3 community.
 *
 * This class is final because it contains only static methods.
 *
 * @author Dmitry Dulepov <dmitry@typo3.com>
 */
class IndexedSearchUtility {

	/**
	 * Check if the tables provided are configured for usage. This becomes
	 * neccessary for extensions that provide additional database functionality
	 * like indexed_search_mysql.
	 *
	 * @param string $tableName Table name to check
	 * @return boolean True if the given table is used
	 */
	static public function isTableUsed($tableName) {
		$tableList = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['use_tables'];
		return \TYPO3\CMS\Core\Utility\GeneralUtility::inList($tableList, $tableName);
	}

	/**
	 * md5 integer hash
	 * Using 7 instead of 8 just because that makes the integers lower than 32 bit (28 bit) and so they do not interfere with UNSIGNED integers or PHP-versions which has varying output from the hexdec function.
	 *
	 * @param string $stringToHash String to hash
	 * @return int Integer intepretation of the md5 hash of input string.
	 */
	static public function md5inthash($stringToHash) {
		return hexdec(substr(md5($stringToHash), 0, 7));
	}
}
?>