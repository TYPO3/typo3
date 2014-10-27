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
 * Class to handle php environment specific options / functions
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class PhpOptionsUtility {

	/**
	 * Check if php sql.safe_mode is enabled
	 *
	 * @return bool TRUE if sql.safe_mode is enabled, FALSE if disabled
	 * @deprecated since 6.2, will be removed two versions later
	 */
	static public function isSqlSafeModeEnabled() {
		GeneralUtility::logDeprecatedFunction();
		return self::getIniValueBoolean('sql.safe_mode');
	}

	/**
	 * Check if php session.auto_start is enabled
	 *
	 * @return bool TRUE if session.auto_start is enabled, FALSE if disabled
	 */
	static public function isSessionAutoStartEnabled() {
		return self::getIniValueBoolean('session.auto_start');
	}

	/**
	 * Cast a on/off php ini value to boolean
	 *
	 * @param string $configOption
	 * @return bool TRUE if the given option is enabled, FALSE if disabled
	 */
	static public function getIniValueBoolean($configOption) {
		return filter_var(ini_get($configOption), FILTER_VALIDATE_BOOLEAN, array(FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE));
	}

}
