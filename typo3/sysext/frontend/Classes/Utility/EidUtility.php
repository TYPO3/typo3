<?php
namespace TYPO3\CMS\Frontend\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Tools for scripts using the eID feature of index.php
 * Included from index_ts.php
 * Since scripts using the eID feature does not
 * have a full FE environment initialized by default
 * this class seeks to provide functions that can
 * initialize parts of the FE environment as needed,
 * eg. Frontend User session, Database connection etc.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class EidUtility {

	/**
	 * Load and initialize Frontend User. Note, this process is slow because
	 * it creates a calls many objects. Call this method only if necessary!
	 *
	 * @return object Frontend User object (usually known as TSFE->fe_user)
	 */
	static public function initFeUser() {
		// Get TSFE instance. It knows how to initialize the user. We also
		// need TCA because services may need extra tables!
		self::initTCA();
		/** @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
		$tsfe = self::getTSFE();
		$tsfe->initFEuser();
		// Return FE user object:
		return $tsfe->fe_user;
	}

	/**
	 * Connecting to database. If the function fails, last error message
	 * can be retrieved using $GLOBALS['TYPO3_DB']->sql_error().
	 *
	 * @return boolean TRUE if connection was successful
	 * @deprecated since 6.1, database will connect itself if needed. Will be removed two versions later
	 */
	static public function connectDB() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return TRUE;
	}

	/**
	 * Initializes $GLOBALS['LANG'] for use in eID scripts.
	 *
	 * @param string $language TYPO3 language code
	 * @return void
	 */
	static public function initLanguage($language = 'default') {
		if (!is_object($GLOBALS['LANG'])) {
			$GLOBALS['LANG'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Lang\\LanguageService');
			$GLOBALS['LANG']->init($language);
		}
	}

	/**
	 * Makes TCA available inside eID
	 *
	 * @return void
	 */
	static public function initTCA() {
		// Some badly made extensions attempt to manipulate TCA in a wrong way
		// (inside ext_localconf.php). Therefore $GLOBALS['TCA'] may become an array
		// but in fact it is not loaded. The check below ensure that
		// TCA is still loaded if such bad extensions are installed
		if (!is_array($GLOBALS['TCA']) || !isset($GLOBALS['TCA']['pages'])) {
			\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadCachedTca();
		}
	}

	/**
	 * Makes TCA for the extension available inside eID. Use this function if
	 * you need not to include the whole $GLOBALS['TCA'].
	 *
	 * @param string $extensionKey Extension key
	 * @return void
	 */
	static public function initExtensionTCA($extensionKey) {
		$extTablesPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey, 'ext_tables.php');
		if (file_exists($extTablesPath)) {
			$GLOBALS['_EXTKEY'] = $extensionKey;
			require_once $extTablesPath;
			// We do not need to save restore the value of $GLOBALS['_EXTKEY']
			// because it is not defined to anything real outside of
			// ext_tables.php or ext_localconf.php scope.
			unset($GLOBALS['_EXTKEY']);
		}
	}

	/**
	 * Creating a single static cached instance of TSFE to use with this class.
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController New instance of tslib_fe
	 */
	static private function getTSFE() {
		// Cached instance
		static $tsfe = NULL;
		if (is_null($tsfe)) {
			$tsfe = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], 0, 0);
		}
		return $tsfe;
	}

}


?>