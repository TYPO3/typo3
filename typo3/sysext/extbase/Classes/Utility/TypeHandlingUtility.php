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
 * PHP type handling functions
 *
 * @api
 * @deprecated since Extbase 1.4.0; will be removed in Extbase 6.0. Please use Tx_Extbase_Service_TypeHandlingService instead
 */
class TypeHandlingUtility {

	/**
	 * @var \TYPO3\CMS\Extbase\Service\TypeHandlingService
	 */
	static protected $typeHandlingService = NULL;

	/**
	 * @return string
	 */
	static protected function getTypeHandlingService() {
		if (self::$typeHandlingService === NULL) {
			require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase', 'Classes/Service/TypeHandlingService.php');
			$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
			self::$typeHandlingService = $objectManager->get('TYPO3\\CMS\\Extbase\\Service\\TypeHandlingService');
		}
		return self::$typeHandlingService;
	}

	/**
	 * A property type parse pattern.
	 */
	const PARSE_TYPE_PATTERN = '/^\\\\?(?P<type>integer|int|float|double|boolean|bool|string|DateTime|Tx_[a-zA-Z0-9_]+|array|ArrayObject|SplObjectStorage)(?:<(?P<elementType>[a-zA-Z0-9_]+)>)?/';
	/**
	 * Adds (defines) a specific property and its type.
	 *
	 * @param string $type Type of the property (see PARSE_TYPE_PATTERN)
	 * @return array An array with information about the type
	 * @deprecated since Extbase 1.4.0; will be removed in Extbase 6.0 - Use Tx_Extbase_Service_TypoScriptService instead
	 */
	static public function parseType($type) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$typeHandlingService = self::getTypeHandlingService();
		return $typeHandlingService->parseType($type);
	}

	/**
	 * Normalize data types so they match the PHP type names:
	 * int -> integer
	 * float -> double
	 * bool -> boolean
	 *
	 * @param string $type Data type to unify
	 * @return string unified data type
	 * @deprecated since Extbase 1.4.0; will be removed in Extbase 6.0 - Use Tx_Extbase_Service_TypoScriptService instead
	 */
	static public function normalizeType($type) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$typeHandlingService = self::getTypeHandlingService();
		return $typeHandlingService->normalizeType($type);
	}

}


?>