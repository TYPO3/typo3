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
 * TYPO3 sprite manager, used in BE and in FE if a BE user is logged in.
 *
 * This class builds CSS definitions of registered icons, writes TCA definitions
 * and registers sprite icons in a cache file.
 *
 * A configurable handler class does the business task.
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_SpriteManager {
	/**
	 * @var string Directory for cached sprite informations
	 */
	public static $tempPath = 'typo3temp/sprites/';

	/**
	 * Initialize sprite manager.
	 * Loads registered sprite configuration from cache, or
	 * rebuilds new cache before registration.
	 *
	 * @return void
	 */
	public static function initialize() {
		$cacheIdentifier = static::getCacheIdentifier();
		/** @var $codeCache t3lib_cache_frontend_PhpFrontend */
		$codeCache = $GLOBALS['typo3CacheManager']->getCache('cache_core');
		if ($codeCache->has($cacheIdentifier)) {
			$codeCache->requireOnce($cacheIdentifier);
		} else {
			static::createSpriteCache();
			$codeCache->requireOnce($cacheIdentifier);
		}
	}

	/**
	 * Compile sprite icon cache by calling the registered generator.
	 *
	 * Stuff the compiled $GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable']
	 * global into php code cache
	 *
	 * @throws RuntimeException
	 * @return void
	 */
	protected static function createSpriteCache() {
		$handlerClass = $GLOBALS['TYPO3_CONF_VARS']['BE']['spriteIconGenerator_handler'];
		/** @var $handler t3lib_spritemanager_SpriteIconGenerator */
		$handler = t3lib_div::makeInstance($handlerClass);

			// Throw exception if handler class does not implement required interface
		if (!$handler instanceof t3lib_spritemanager_SpriteIconGenerator) {
			throw new RuntimeException(
				'Class ' . $handlerClass . ' in $TYPO3_CONF_VARS[BE][spriteIconGenerator_handler] ' .
				' does not implement t3lib_spritemanager_SpriteIconGenerator',
				1294586333
			);
		}

			// Create temp directory if missing
		if (!is_dir(PATH_site . self::$tempPath)) {
			t3lib_div::mkdir(PATH_site . self::$tempPath);
		}

			// Generate CSS and TCA files, build icon set register
		$handler->generate();

			// Get all icons registered from skins, merge with core icon list
		$availableSkinIcons = (array) $GLOBALS['TBE_STYLES']['spriteIconApi']['coreSpriteImageNames'];
		foreach ($GLOBALS['TBE_STYLES']['skins'] as $skinData) {
			$availableSkinIcons = array_merge($availableSkinIcons, (array) $skinData['availableSpriteIcons']);
		}

			// Merge icon names provided by the skin, with
			// registered "complete sprites" and the handler class
		$iconNames = array_merge(
			$availableSkinIcons,
			(array) $GLOBALS['TBE_STYLES']['spritemanager']['spriteIconsAvailable'],
			$handler->getAvailableIconNames()
		);

		$cacheString = addslashes(serialize($iconNames));
		$cacheFileContent = '$GLOBALS[\'TBE_STYLES\'][\'spriteIconApi\'][\'iconsAvailable\'] = unserialize(stripslashes(\'' . $cacheString . '\'));';

		/** @var $codeCache t3lib_cache_frontend_PhpFrontend */
		$GLOBALS['typo3CacheManager']->getCache('cache_core')->set(
			static::getCacheIdentifier(),
			$cacheFileContent
		);
	}

	/**
	 * Get cache identifier for $GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable']
	 *
	 * @return string
	 */
	protected static function getCacheIdentifier() {
		return 'sprites_' . sha1(TYPO3_version . PATH_site . 'spriteManagement');
	}

	/**
	 * API for extensions to register own sprites.
	 *
	 * Get an array of icon names and the styleSheetFile with defined sprite icons.
	 * The stylesheet filename should contain the extension name to be unique.
	 *
	 * Naming conventions:
	 * - IconName: extensions-$extKey-$iconName
	 * - CSS class for loading the sprite: t3-icon-extensions-$extKey
	 * - CSS class for single icons: t3-icon-$extKey-$iconName
	 *
	 * @param array $icons Icon names
	 * @param string $styleSheetFile Stylesheet filename relative to PATH_typo3. Skins do not need to supply the $styleSheetFile, if the CSS file is within the registered stylesheet folders
	 * @return void
	 */
	public static function addIconSprite(array $icons, $styleSheetFile = '') {
		$GLOBALS['TBE_STYLES']['spritemanager']['spriteIconsAvailable'] = array_merge(
			(array) $GLOBALS['TBE_STYLES']['spritemanager']['spriteIconsAvailable'],
			$icons
		);
		if ($styleSheetFile !== '') {
			$GLOBALS['TBE_STYLES']['spritemanager']['cssFiles'][] = $styleSheetFile;
		}
	}

	/**
	 * API for extensions to register new sprite images which can be used with
	 * t3lib_iconWorks::getSpriteIcon('extensions-$extKey-iconName');
	 *
	 * @param array $icons Icons to be registered, $iconname => $iconFile, $iconFile must be relative to PATH_site
	 * @param string $extKey Extension key
	 * @return void
	 */
	public static function addSingleIcons(array $icons, $extKey = '') {
		foreach ($icons as $iconName => $iconFile) {
			$GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['extensions-' . $extKey . '-' . $iconName] = $iconFile;
		}
	}

	/**
	 * API to register new type icons for tables which use "typeicon_classes"
	 * Can be used to provide icons for "modules" in pages table
	 *
	 * @param string $table Table name to which the type icon should be added
	 * @param string $type Type column name of the table
	 * @param string $iconFile Icon filename, relative to PATH_typo3
	 * @return void
	 */
	public static function addTcaTypeIcon($table, $type, $iconFile) {
		$GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords-' . $table . '-' . $type] = $iconFile;
		if (is_array($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])) {
			$GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type] = 'tcarecords-' . $table . '-' . $type;
		}
	}
}
?>