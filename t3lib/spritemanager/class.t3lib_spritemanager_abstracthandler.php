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
 * An abstract class implementing t3lib_spritemanager_SpriteIconGenerator.
 * Provides base functionality for all handlers.
 *
 * @author	Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
abstract class t3lib_spritemanager_AbstractHandler implements t3lib_spritemanager_SpriteIconGenerator {
	/**
	 * all "registered" icons available through sprite API will cmmulated here
	 * @var array
	 */
	protected $iconNames = array();

	/**
	 * contains the content of the CSS file to write
	 * @var String
	 */
	protected $styleSheetData = '';

	/**
	 * path to CSS file for generated styles
	 * @var String
	 */
	protected $cssTcaFile = "";

	/**
	 * constructor just init's the temp-file-name
	 * @return void
	 */
	function __construct() {
			// the file name is prefixed with "z" since the concatenator orders files per name
		$this->cssTcaFile = PATH_site . t3lib_SpriteManager::$tempPath . 'zextensions.css';
		$this->styleSheetData = '/* Auto-Generated via ' . get_class($this) . ' */' . LF;
	}

	/**
	 * Loads all stylesheet files registered through
	 * t3lib_SpriteManager::::addIconSprite
	 *
	 * In fact the stylesheet-files are copied to t3lib_SpriteManager::tempPath
	 * where they automatically will be included from via template.php and
	 * t3lib_compressor.
	 *
	 * @return void
	 */
	protected function loadRegisteredSprites() {
			// saves which CSS Files are currently "allowed to be in place"
		$allowedCssFilesinTempDir = array(basename($this->cssTcaFile));
			// process every registeres file
		foreach ((array) $GLOBALS['TBE_STYLES']['spritemanager']['cssFiles'] as $file) {
			$fileName = basename($file);
				// file should be present
			$allowedCssFilesinTempDir[] = $fileName;
				// get-Cache Filename
			$unique = md5($fileName . filemtime(PATH_site . $file) . filesize(PATH_site . $file));
			$cacheFile = PATH_site . t3lib_SpriteManager::$tempPath . $fileName . $unique . '.css';
			if (!file_exists($cacheFile)) {
				copy(PATH_site . $file, $cacheFile);
			}
		}
			// get all .css files in dir
		$cssFilesPresentInTempDir = t3lib_div::getFilesInDir(PATH_site . t3lib_SpriteManager::$tempPath, '.css', 0);
			// and delete old ones which are not needed anymore
		$filesToDelete = array_diff($cssFilesPresentInTempDir, $allowedCssFilesinTempDir);
		foreach ($filesToDelete as $file) {
			unlink(PATH_site . t3lib_SpriteManager::$tempPath . $file);
		}
	}

	/**
	 * Interface function. This will be called from the sprite manager to
	 * refresh all caches.
	 *
	 * @return void
	 */
	public function generate() {
			// include registered Sprites
		$this->loadRegisteredSprites();

			// cache results in the CSS file
		t3lib_div::writeFile($this->cssTcaFile, $this->styleSheetData);
	}

	/**
	 * Returns the detected icon-names which may be used through t3lib_iconWorks::getSpriteIcon.
	 *
	 * @return array all generated and registred sprite-icon-names, will be empty if there are none
	 */
	public function getAvailableIconNames() {
		return $this->iconNames;
	}

	/**
	 * this method creates sprite icon names for all tables in TCA (including their possible type-icons)
	 * where there is no "typeicon_classes" of this TCA table ctrl section (moved form t3lib_iconWorks)
	 *
	 * @return array array as $iconName => $fileName
	 */
	protected function collectTcaSpriteIcons() {
		$tcaTables = array_keys($GLOBALS['TCA']);

		$resultArray = array();

			// path (relative from typo3 dir) for skin-Images
		if (isset($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['relDir'])) {
			$skinPath = $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['relDir'];
		} else {
			$skinPath = '';
		}

			// check every table in the TCA, if an icon is needed
		foreach ($tcaTables as $tableName) {

				// this method is only needed for TCA tables where
				// typeicon_classes are not configured
			if (is_array($GLOBALS['TCA'][$tableName]) && !is_array($GLOBALS['TCA'][$tableName]['ctrl']['typeicon_classes'])) {
				$tcaCtrl = $GLOBALS['TCA'][$tableName]['ctrl'];

					// adding the default Icon (without types)
				if (isset($tcaCtrl['iconfile'])) {
						// in CSS wie need a path relative to the css file
						// [TCA][ctrl][iconfile] defines icons without path info to reside in gfx/i/
					if (strpos($tcaCtrl['iconfile'], '/') !== FALSE) {
						$icon = $tcaCtrl['iconfile'];
					} else {
						$icon = $skinPath . 'gfx/i/' . $tcaCtrl['iconfile'];
					}

					$icon = t3lib_div::resolveBackPath($icon);
					$resultArray['tcarecords-' . $tableName . '-default'] = $icon;

				}

					// if records types are available, register them
				if (isset($tcaCtrl['typeicon_column']) && is_array($tcaCtrl['typeicons'])) {
					foreach ($tcaCtrl['typeicons'] as $type => $icon) {

							// in CSS wie need a path relative to the css file
							// [TCA][ctrl][iconfile] defines icons without path info to reside in gfx/i/
						if (strpos($icon, '/') === FALSE) {
							$icon = $skinPath . 'gfx/i/' . $icon;
						}

						$icon = t3lib_div::resolveBackPath($icon);

						$resultArray['tcarecords-' . $tableName . '-' . $type] = $icon;
					}
				}
			}
		}
		return $resultArray;
	}
}

?>