<?php
namespace TYPO3\CMS\Extbase\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Rens Admiraal <rens@rensnel.nl>
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
 * Utilities to simulate a frontend in backend context.
 *
 * ONLY USED INTERNALLY, MIGHT CHANGE WITHOUT NOTICE!
 *
 * @package Extbase
 * @subpackage Utility
 * @version $ID:$
 */
class FrontendSimulatorUtility {

	/**
	 * @var mixed
	 */
	static protected $tsfeBackup;

	/**
	 * Sets the $TSFE->cObjectDepthCounter in Backend mode
	 * This somewhat hacky work around is currently needed because the cObjGetSingle() function of tslib_cObj relies on this setting
	 *
	 * @param tslib_cObj|NULL $cObj
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	static public function simulateFrontendEnvironment(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj = NULL) {
		self::$tsfeBackup = isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : NULL;
		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->cObjectDepthCounter = 100;
		$GLOBALS['TSFE']->cObj = $cObj !== NULL ? $cObj : \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
	}

	/**
	 * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>(
	 * @see simulateFrontendEnvironment()
	 */
	static public function resetFrontendEnvironment() {
		if (!empty(self::$tsfeBackup)) {
			$GLOBALS['TSFE'] = self::$tsfeBackup;
		}
	}

}


?>