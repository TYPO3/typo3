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
 *
 * @author	Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_spritemanager_SpriteBuildingHandler extends t3lib_spritemanager_AbstractHandler {

	/**
	 * @var t3lib_spritemanager_SpriteGenerator
	 */
	protected $generatorInstance = NULL;

	/**
	 * Interface function. This will be called from the sprite manager to
	 * refresh all caches.
	 *
	 * @return void
	 */
	public function generate() {
		$this->generatorInstance = t3lib_div::makeInstance('t3lib_spritemanager_SpriteGenerator', 'GeneratorHandler');
		$this->generatorInstance
				->setOmmitSpriteNameInIconName(TRUE)
				->setIncludeTimestampInCSS(TRUE)
				->setSpriteFolder(t3lib_SpriteManager::$tempPath)
				->setCSSFolder(t3lib_SpriteManager::$tempPath);

		$iconsToProcess = array_merge(
			(array) $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons'],
			$this->collectTcaSpriteIcons()
		);

		foreach ($iconsToProcess as $iconName => $iconFile) {
			$iconsToProcess[$iconName] = t3lib_div::resolveBackPath('typo3/' . $iconFile);
		}

		$generatorResponse = $this->generatorInstance->generateSpriteFromArray($iconsToProcess);

		if (!is_dir(PATH_site . t3lib_SpriteManager::$tempPath . 'ie6')) {
			t3lib_div::mkdir(PATH_site . t3lib_SpriteManager::$tempPath . 'ie6');
		}

		t3lib_div::upload_copy_move(
			$generatorResponse['spriteGifImage'],
			t3lib_div::dirname($generatorResponse['spriteGifImage']) . '/ie6/' . basename($generatorResponse['spriteGifImage'])
		);
		unlink($generatorResponse['spriteGifImage']);

		t3lib_div::upload_copy_move(
			$generatorResponse['cssGif'],
			t3lib_div::dirname($generatorResponse['cssGif']) . '/ie6/' . basename($generatorResponse['cssGif'])
		);
		unlink($generatorResponse['cssGif']);

		$this->iconNames = array_merge($this->iconNames, $generatorResponse['iconNames']);

		parent::generate();
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/spritemanager/class.t3lib_spritemanager_autogeneratinghandler.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/spritemanager/class.t3lib_spritemanager_autogeneratinghandler.php']);
}
?>
