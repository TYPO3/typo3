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
	 * @var t3lib_spritemanager_SpriteIconGenerator Handler class instance
	 */
	protected $handler = NULL;

	/**
	 * @var array Register of valid icons
	 */
	protected $iconNames = array();

	/**
	 * @var string Name of current cache file
	 */
	protected $tempFileName = '';

	/**
	 * Check if the icon cache has to be rebuild, instantiate and call the handler class if so.
	 *
	 * @param boolean Suppress regeneration if false (useful for feediting)
	 * @return void
	 */
	function __construct($allowRegeneration = TRUE) {
			// Create temp directory if missing
		if (!is_dir(PATH_site . self::$tempPath)) {
			t3lib_div::mkdir(PATH_site . self::$tempPath);
		}

			// Backwards compatibility handling for API calls <= 4.3, will be removed in 4.7
		$this->compatibilityCalls();

			// Create cache filename, the hash includes all icons, registered CSS styles registered and the extension list
		$this->tempFileName = PATH_site . self::$tempPath .
							  md5(serialize($GLOBALS['TBE_STYLES']['spritemanager']) .
								  md5(serialize($GLOBALS['TBE_STYLES']['spriteIconApi']['coreSpriteImageNames'])) .
								  $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList']) . '.inc';

			// Regenerate cache file if not already existing
		if (!@file_exists($this->tempFileName)) {
			if ($allowRegeneration) {
				$handlerClass = (
				$GLOBALS['TYPO3_CONF_VARS']['BE']['spriteIconGenerator_handler'] ?
						$GLOBALS['TYPO3_CONF_VARS']['BE']['spriteIconGenerator_handler'] :
						't3lib_spritemanager_SimpleHandler'
				);
				$this->handler = t3lib_div::makeInstance($handlerClass);

					// Throw exception if handler class does not implement required interface
				if (!$this->handler || !($this->handler instanceof t3lib_spritemanager_SpriteIconGenerator)) {
					throw new RuntimeException(
						'Class in $TYPO3_CONF_VARS[BE][spriteIconGenerator_handler] (' .
						$GLOBALS['TYPO3_CONF_VARS']['BE']['spriteIconGenerator_handler'] .
						') does not exist or does not implement t3lib_spritemanager_SpriteIconGenerator.',
						1294586333
					);
				}

				$this->rebuildCache();
			} else {
					// Set tempFileName to existing file if regeneration is not allowed
				list($this->tempFileName) = t3lib_div::getFilesInDir(PATH_site . self::$tempPath, 'inc', TRUE);
			}
		}
	}

	/**
	 * Call handler class, merge results with skin data and cache it.
	 *
	 * @return void
	 */
	protected function rebuildCache() {
			// Generate CSS and TCA files, build icon set register
		$this->handler->generate();

			// Get all icons registered from skins, merge with core icon list
		$availableSkinIcons = (array) $GLOBALS['TBE_STYLES']['spriteIconApi']['coreSpriteImageNames'];
		foreach ($GLOBALS['TBE_STYLES']['skins'] as $skinName => $skinData) {
			$availableSkinIcons = array_merge($availableSkinIcons, (array) $skinData['availableSpriteIcons']);
		}

			// Merge icon names provided by the skin, with
			// registered "complete sprites" and the handler class
		$this->iconNames = array_merge(
			$availableSkinIcons,
			(array) $GLOBALS['TBE_STYLES']['spritemanager']['spriteIconsAvailable'],
			$this->handler->getAvailableIconNames()
		);

			// Create serialized cache data
		$cacheString = addslashes(serialize($this->iconNames));
		$fileContent = '<?php $GLOBALS[\'TBE_STYLES\'][\'spriteIconApi\'][\'iconsAvailable\'] = unserialize(stripslashes(\'' . $cacheString . '\')); ?>';

			// Clean up cache directory
		$oldFiles = t3lib_div::getFilesInDir(PATH_site . self::$tempPath, 'inc', TRUE);
		foreach ($oldFiles as $file) {
			@unlink($file);
		}

			// Write new cache file
		t3lib_div::writeFile($this->tempFileName, $fileContent);
	}

	/**
	 * Backwards compatibility methods, log usage to deprecation log.
	 * Will be removed in 4.7
	 *
	 * @return void
	 */
	private function compatibilityCalls() {
			// Fallback for $TYPE_ICONS "contains-module" icons
		foreach ((array) $GLOBALS['ICON_TYPES'] as $module => $icon) {
			$iconFile = $icon['icon'];
			t3lib_div::deprecationLog('Usage of $ICON_TYPES is deprecated since 4.4.' . LF .
									  'The extTables.php entry $ICON_TYPES[\'' . $module . '\'] = \'' . $iconFile . '\'; should be replaced with' . LF .
									  't3lib_SpriteManager::addTcaTypeIcon(\'pages\', \'contains-' . $module . '\', \'' . $iconFile . '\');' . LF .
									  'instead.'
			);
			t3lib_SpriteManager::addTcaTypeIcon('pages', 'contains-' . $module, $iconFile);
		}

			// Fallback for $PAGE_TYPES icons
		foreach ((array) $GLOBALS['PAGES_TYPES'] as $type => $icon) {
			if (isset($icon['icon'])) {
				$iconFile = $icon['icon'];
				t3lib_div::deprecationLog('Usage of $PAGES_TYPES[\'icon\'] is deprecated since 4.4.' . LF .
										  'The extTables.php entry $PAGE_TYPES[\'' . $type . '\'][\'icon\'] = \'' . $iconFile . '\'; should be replaced with' . LF .
										  't3lib_SpriteManager::addTcaTypeIcon(\'pages\', \'' . $type . '\', \'' . $iconFile . '\');' . LF .
										  'instead.'
				);
				t3lib_SpriteManager::addTcaTypeIcon('pages', $module, $iconFile);
			}
		}
	}

	/**
	 * Include cache file if exists
	 *
	 * @return void
	 */
	public function loadCacheFile() {
		if (@file_exists($this->tempFileName)) {
			include_once($this->tempFileName);
		}
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
	 * @param array Icon names
	 * @param string Stylesheet filename relative to PATH_typo3. Skins do not need to supply the $styleSheetFile, if the CSS file is within the registered stylesheet folders
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
	 * @param array Icons to be registered, $iconname => $iconFile, $iconFile must be relative to PATH_site
	 * @param string Extension key
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
	 * @param string Table name to which the type icon should be added
	 * @param string Type column name of the table
	 * @param string Icon filename, relative to PATH_typo3
	 * @return void
	 */
	public static function addTcaTypeIcon($table, $type, $iconFile) {
		$GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords-' . $table . '-' . $type] = $iconFile;
		if (is_array($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])) {
			$GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type] = 'tcarecords-' . $table . '-' . $type;
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_spritemanager.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_spritemanager.php']);
}
?>