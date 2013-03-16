<?php
namespace TYPO3\CMS\Backend\Sprite;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Ritter <info@steffen-ritter.net>
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
 * Sprite build handler
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 */
class SpriteBuildingHandler extends \TYPO3\CMS\Backend\Sprite\AbstractSpriteHandler {

	/**
	 * @var \TYPO3\CMS\Backend\Sprite\SpriteGenerator
	 */
	protected $generatorInstance = NULL;

	/**
	 * Interface function. This will be called from the sprite manager to
	 * refresh all caches.
	 *
	 * @return void
	 */
	public function generate() {
		$this->generatorInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Sprite\\SpriteGenerator', 'GeneratorHandler');
		$this->generatorInstance->setOmmitSpriteNameInIconName(TRUE)->setIncludeTimestampInCSS(TRUE)->setSpriteFolder(\TYPO3\CMS\Backend\Sprite\SpriteManager::$tempPath)->setCSSFolder(\TYPO3\CMS\Backend\Sprite\SpriteManager::$tempPath);
		$iconsToProcess = array_merge((array) $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons'], $this->collectTcaSpriteIcons());
		foreach ($iconsToProcess as $iconName => $iconFile) {
			$iconsToProcess[$iconName] = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath('typo3/' . $iconFile);
		}
		$generatorResponse = $this->generatorInstance->generateSpriteFromArray($iconsToProcess);
		$this->iconNames = array_merge($this->iconNames, $generatorResponse['iconNames']);
		parent::generate();
	}

}


?>