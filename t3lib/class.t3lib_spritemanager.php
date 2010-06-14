<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Steffen Ritter <info@steffen-ritter.net>
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
 * TYPO3 Sprite Manager, it is initiated from BE and FE if BE-User ist active
 * Its task will be to build css-definitions for registered Icons of Extensions,
 * TCA-Tables and so on, so that they will be usuable through sprite-icon-api.
 * An special configurable handler-class will process the "real" task so that
 * the user may differ between details of generation and their caching.
 *
 * @author	Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_SpriteManager {
	/**
	 * @var string
	 */
	public static $tempPath = 'typo3temp/sprites/';

	/**
	 *@var t3lib_spritemanager_SpriteIconGenerator
	 */
	protected $handler = NULL;

	/**
	 * @var array
	 */
	protected $iconNames = array();

	/**
	 * @var string the file name the current cache file
	 */
	protected $tempFileName = '';

	/**
	 * class constructor checks if cache has to be rebuild and initiates the rebuild
	 * instantiates the handler class
	 *
	 * @param boolean $regenerate	with set to false, cache won't be regenerated if needed (useful for feediting)
	 * @return void
	 */
	function __construct($regenerate = TRUE) {
			// we check for existance of our targetDirectory
		if (!is_dir(PATH_site . self::$tempPath)) {
			t3lib_div::mkdir(PATH_site . self::$tempPath);
		}
			// create a fileName, the hash includes all icons and css-styles registered and the extlist
		$this->tempFileName = PATH_site . self::$tempPath .
							md5(serialize($GLOBALS['TBE_STYLES']['spritemanager']) .
							md5(serialize($GLOBALS['TBE_STYLES']['spriteIconApi']['coreSpriteImageNames'])) . 
							$GLOBALS['TYPO3_CONF_VARS']['EXT']['extList']) . '.inc';
			// if no cache-file for the current config ist present, regenerate it
		if(!@file_exists($this->tempFileName)) {
				// regenerate if allowed
			if($regenerate) {
				$handlerClass = (
					$GLOBALS['TYPO3_CONF_VARS']['BE']['spriteIconGenerator_handler'] ?
					$GLOBALS['TYPO3_CONF_VARS']['BE']['spriteIconGenerator_handler'] :
					't3lib_spritemanager_SimpleHandler'
				);
				$this->handler = t3lib_div::makeInstance($handlerClass);
					// check if the handler could be loaded and implements the needed interface
				if (!$this->handler || !($this->handler instanceof t3lib_spritemanager_SpriteIconGenerator)) {
					throw new Exception(
						"class in TYPO3_CONF_VARS[BE][spriteIconGenerator_handler] does not exist,
						or does not implement t3lib_spritemanager_SpriteIconGenerator"
					);
				}
					// all went good? to go for rebuild
				$this->rebuildCache();
			} else {
					// use old file if present
				list($this->tempFileName) = t3lib_div::getFilesInDir(PATH_site . self::$tempPath, 'inc', 1);
			}
		}
	}

	/**
	 * this method calls the main methods from the handler classes
	 * merges the results with the data from the skin, and cache it
	 *
	 * @return void
	 */
	protected function rebuildCache() {
			// ask the handlerClass to kindly rebuild our data
		$this->handler->generate();

			// get all Icons registered from skins, merge with core-Icon-List
		$availableSkinIcons = (array)$GLOBALS['TBE_STYLES']['spriteIconApi']['coreSpriteImageNames'];
		foreach ($GLOBALS['TBE_STYLES']['skins'] as $skinName => $skinData) {
			$availableSkinIcons = array_merge($availableSkinIcons, (array)$skinData['availableSpriteIcons']);
		}

			// merge icon names whith them provided by the skin,
			// registered from "complete sprites" and the ones detected
			// by the handlerclass
		$this->iconNames = array_merge(
			$availableSkinIcons,
			(array) $GLOBALS['TBE_STYLES']['spritemanager']['spriteIconsAvailable'],
			$this->handler->getAvailableIconNames()
		);

			// serialize found icons, and cache them to file
		$cacheString = addslashes(serialize($this->iconNames));
		$fileContent = '<?php $GLOBALS[\'TBE_STYLES\'][\'spriteIconApi\'][\'iconsAvailable\'] = unserialize(stripslashes(\'' . $cacheString . '\')); ?>';

			// delete old cache files
		$oldFiles = t3lib_div::getFilesInDir(PATH_site . self::$tempPath, 'inc', 1);
		foreach ($oldFiles as $file) {
			@unlink($file);
		}
			// and write the new one
		t3lib_div::writeFile($this->tempFileName, $fileContent);
	}


	/**
	 * includes the generated cacheFile, if present
	 *
	 * @return void
	 */
	public function loadCacheFile() {
		if (@file_exists($this->tempFileName)) {
			include_once($this->tempFileName);
		}
	}

	/**
	 * if an extension has an pregenerated sprite, it might register it here.
	 * Giving the "available" iconNames and the styleSheetFile where the sprite icons are defined (make shure the css  filename contains the extname to be unique).
	 * the iconnames and the stylesheet must follow the conventions as follows:
	 * IconName: extensions-$extKey-$iconName.
	 * Class for loading the sprite: t3-icon-extensions-$extKey
	 * Class for single icons: t3-icon-$extKey-$iconName
	 * NOTE: do not use this for skins, stylesheets of skins will be included automatically.
	 * Available icons of skins should be located manually (extTables) to $GLOBALS[TBE_STYLES][skins][skinName][availableIcons]
	 *
	 * @param array	icons	the names of the introduced icons
	 * @parram string $styleSheetFile	the name of the styleshet file relative to PATH_site
	 */
	public static function addIconSprite(array $icons, $styleSheetFile) {
		$GLOBALS['TBE_STYLES']['spritemanager']['spriteIconsAvailable'] = array_merge(
			$GLOBALS['TBE_STYLES']['spritemanager']['spriteIconsAvailable'],
			$icons
		);

		$GLOBALS['TBE_STYLES']['spritemanager']['cssFiles'][] = $styleSheetFile;
	}

	/**
	 * will allow Ext-Developers to register their icons to get included in sprites,
	 * they may use them afterwards with t3lib_iconWorks::getSpriteIcon('extensions-$extKey-iconName');
	 * @param array	$icons	array which contains the adding icons array ( $iconname => $iconFile) $iconFile relative to PATH_typo3
	 * @param string	$extKey	string of the extension which adds the icons
	 * @return void
	 */
	public static function addSingleIcons(array $icons, $extKey = '') {
		foreach ($icons as $iconName => $iconFile) {
			$GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['extensions-' . $extKey . '-' . $iconName] = $iconFile;
		}
	}

	/**
	 * static function to add a type-with icon to an already existent table which makes use of "typeicon_classes"
	 * feature or to provide icon for "modules" in pages table
	 * @param string	$table	the table the type has been added
	 * @param string	$type	the type - must equal the value of the column in the table
	 * @param string	$iconFile	relative to PATH_typo3
	 */
	public static function addTcaTypeIcon($table, $type, $iconFile) {
		$GLOBALS['TBE_STYLES']['spritemanager']['singleIcons']['tcarecords-' . $table . '-' . $type] = $iconFile;
		if(isset($GLOBALS['TCA'][$table]['typeicon_classes'])) {
			$GLOBALS['TCA'][$table]['typeicon_classes'][$type] = 'tcarecords-' . $table . '-' . $type;
		} 
	}
}

?>
