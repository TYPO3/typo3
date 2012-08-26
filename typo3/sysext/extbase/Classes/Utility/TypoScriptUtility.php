<?php
namespace TYPO3\CMS\Extbase\Utility;

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
class TypoScriptUtility {

	/**
	 * @var \TYPO3\CMS\Extbase\Service\TypoScriptService
	 */
	static protected $typoScriptService = NULL;

	/**
	 * @return \TYPO3\CMS\Extbase\Service\TypoScriptService|NULL
	 */
	static protected function getTypoScriptService() {
		if (self::$typoScriptService === NULL) {
			require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('extbase', 'Classes/Service/TypoScriptService.php');
			$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
			self::$typoScriptService = $objectManager->get('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService');
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
	 * @return array
	 * @deprecated since Extbase 1.4.0; will be removed in Extbase 6.0 - Use Tx_Extbase_Service_TypoScriptService instead
	 */
	static public function convertTypoScriptArrayToPlainArray(array $settings) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
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
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		if (!is_array($plainArray)) {
			return array();
		}
		$typoScriptService = self::getTypoScriptService();
		return $typoScriptService->convertPlainArrayToTypoScriptArray($plainArray);
	}

}


?>