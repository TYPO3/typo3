<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2011 Christian Kuhn <lolli@schwarzbu.ch>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class to handle php environment specific options / functions
 *
 * @author	 Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage t3lib
 */
final class t3lib_utility_PhpOptions {
	/**
	 * Check if php safe_mode is enabled
	 *
	 * @return boolean TRUE if safe_mode is enabled, FALSE if disabled
	 */
	public static function isSafeModeEnabled() {
		return self::getIniValueBoolean('safe_mode');
	}

	/**
	 * Check if php sql.safe_mode is enabled
	 *
	 * @return boolean TRUE if sql.safe_mode is enabled, FALSE if disabled
	 */
	public static function isSqlSafeModeEnabled() {
		return self::getIniValueBoolean('sql.safe_mode');
	}

	/**
	 * Cast a on/off php ini value to boolean
	 *
	 * @return boolean TRUE if the given option is enabled, FALSE if disabled
	 */
	public static function getIniValueBoolean($configOption) {
		return filter_var(ini_get($configOption), FILTER_VALIDATE_BOOLEAN, array(FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE));
	}
}

?>