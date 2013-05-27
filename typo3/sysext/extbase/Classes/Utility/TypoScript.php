<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christian Müller <christian@kitsunet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Utilities to manage and convert Typoscript Code
 *
 * @package Extbase
 * @subpackage Utility
 * @deprecated since Extbase 1.4.0; will be removed in Extbase 6.0. Please use Tx_Extbase_Service_TypoScriptService instead
 */
class Tx_Extbase_Utility_TypoScript {

	/**
	 * @var Tx_Extbase_Service_TypoScriptService
	 */
	protected static $typoScriptService = NULL;

	/**
	 * @return void
	 */
	static protected function getTypoScriptService() {
		if (self::$typoScriptService === NULL) {
			require_once t3lib_extMgm::extPath('extbase', 'Classes/Service/TypoScriptService.php');
			$objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
			self::$typoScriptService = $objectManager->get('Tx_Extbase_Service_TypoScriptService');
		}
		return self::$typoScriptService;
	}

	/**
	 * Removes all trailing dots recursively from TS settings array
	 *
	 * Extbase converts the "classical" TypoScript (with trailing dot) to a format without trailing dot,
	 * to be more future-proof and not to have any conflicts with Fluid object accessor syntax.
	 *
	 * @param array $settings The settings array
	 * @return void
	 * @deprecated since Extbase 1.4.0; will be removed in Extbase 6.0 - Use Tx_Extbase_Service_TypoScriptService instead
	 */
	static public function convertTypoScriptArrayToPlainArray(array $settings) {
		t3lib_div::logDeprecatedFunction();
		$typoScriptService = self::getTypoScriptService();
		return $typoScriptService->convertTypoScriptArrayToPlainArray($settings);
	}

	/**
	 * Returns an array with Typoscript the old way (with dot).
	 *
	 * Extbase converts the "classical" TypoScript (with trailing dot) to a format without trailing dot,
	 * to be more future-proof and not to have any conflicts with Fluid object accessor syntax.
	 * However, if you want to call legacy TypoScript objects, you somehow need the "old" syntax (because this is what TYPO3 is used to).
	 * With this method, you can convert the extbase TypoScript to classical TYPO3 TypoScript which is understood by the rest of TYPO3.
	 *
	 * @param array $plainArray An Typoscript Array with Extbase Syntax (without dot but with _typoScriptNodeValue)
	 * @return array array with Typoscript as usual (with dot)
	 * @deprecated since Extbase 1.4.0; will be removed in Extbase 6.0 - Use Tx_Extbase_Service_TypoScriptService instead
	 */
	static public function convertPlainArrayToTypoScriptArray($plainArray) {
		t3lib_div::logDeprecatedFunction();
		if (!is_array($plainArray)) {
			return array();
		}
		$typoScriptService = self::getTypoScriptService();
		return $typoScriptService->convertPlainArrayToTypoScriptArray($plainArray);
	}
}
?>