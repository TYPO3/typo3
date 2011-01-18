<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Steffen Ritter <info@steffen-ritter.net>
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
 * A class with an concrete implementation of t3lib_spritemanager_SpriteIconGenerator.
 * It is the standard / fallback handler of the sprite manager.
 * This implementation won't generate sprites at all. It will just render css-definitions
 * for all registered icons so that they may be used through t3lib_iconWorks::getSpriteIcon*
 * Without the css classes generated here, icons of for example tca records would be empty.
 *
 * @author	Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_spritemanager_SimpleHandler extends t3lib_spritemanager_AbstractHandler {

	/**
	 * css template for single Icons registered by extension authors
	 * @var String
	 */
	protected $styleSheetTemplateExtIcons = '
.t3-icon-###NAME### {
	background-position: 0px 0px !important;
	background-image: url(\'###IMAGE###\') !important;
}
	';

	/**
	 * constructor just init's the temp-file-name
	 * @return void
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Interface function. This will be called from the sprite manager to
	 * refresh all caches.
	 *
	 * @return void
	 */
	public function generate() {

			// generate IconData for single Icons registered
		$this->buildCssAndRegisterIcons();

		parent::generate();
	}


	/**
	 * This function builds an css class for every single icon registered via
	 * t3lib_SpriteManager::addSingleIcons to use them via t3lib_iconWorks::getSpriteIcon
	 * and TCA-Icons for "classic" record Icons to be uses via t3lib_iconWorks::getSpriteIconForRecord
	 * In the simpleHandler the icon just will be added as css-background-image.
	 *
	 * @return void
	 */
	protected function buildCssAndRegisterIcons() {
			// backpath from the stylesheet file ($cssTcaFile) to PATH_site dir
			// in order to set the background-image URL paths correct
		$iconPath = '../../' . TYPO3_mainDir;

		$iconsToProcess = array_merge(
			(array) $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons'],
			$this->collectTcaSpriteIcons()
		);
		foreach ($iconsToProcess as $iconName => $iconFile) {
			$css = str_replace('###NAME###', str_replace(
				array('extensions-', 'tcarecords-'), array('', ''), $iconName
			), $this->styleSheetTemplateExtIcons);
			$css = str_replace('###IMAGE###', t3lib_div::resolveBackPath($iconPath . $iconFile), $css);

			$this->iconNames[] = $iconName;
			$this->styleSheetData .= $css;
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/spritemanager/class.t3lib_spritemanager_simplehandler.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/spritemanager/class.t3lib_spritemanager_simplehandler.php']);
}
?>
