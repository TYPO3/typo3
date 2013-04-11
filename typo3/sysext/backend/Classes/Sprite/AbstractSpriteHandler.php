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
 * An abstract class implementing SpriteIconGeneratorInterface.
 * Provides base functionality for all handlers.
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 */
abstract class AbstractSpriteHandler implements \TYPO3\CMS\Backend\Sprite\SpriteIconGeneratorInterface {

	/**
	 * all "registered" icons available through sprite API will cumulated here
	 *
	 * @var array
	 */
	protected $iconNames = array();

	/**
	 * contains the content of the CSS file to write
	 *
	 * @var string
	 */
	protected $styleSheetData = '';

	/**
	 * path to CSS file for generated styles
	 *
	 * @var string
	 */
	protected $cssTcaFile = '';

	/**
	 * constructor just init's the temp-file-name
	 *
	 * @return void
	 */
	public function __construct() {
		// The file name is prefixed with "z" since the concatenator orders files per name
		$this->cssTcaFile = PATH_site . \TYPO3\CMS\Backend\Sprite\SpriteManager::$tempPath . 'zextensions.css';
		$this->styleSheetData = '/* Auto-Generated via ' . get_class($this) . ' */' . LF;
	}

	/**
	 * Loads all stylesheet files registered through
	 * \TYPO3\CMS\Backend\Sprite\SpriteManager::addIconSprite
	 *
	 * In fact the stylesheet-files are copied to \TYPO3\CMS\Backend\Sprite\SpriteManager::tempPath
	 * where they automatically will be included from via template.php and
	 * \TYPO3\CMS\Core\Resource\ResourceCompressor
	 *
	 * @return void
	 */
	protected function loadRegisteredSprites() {
		// Saves which CSS Files are currently "allowed to be in place"
		$allowedCssFilesinTempDir = array(basename($this->cssTcaFile));
		// Process every registeres file
		foreach ((array) $GLOBALS['TBE_STYLES']['spritemanager']['cssFiles'] as $file) {
			$fileName = basename($file);
			// File should be present
			$allowedCssFilesinTempDir[] = $fileName;
			// get-Cache Filename
			$unique = md5($fileName . filemtime((PATH_site . $file)) . filesize((PATH_site . $file)));
			$cacheFile = PATH_site . \TYPO3\CMS\Backend\Sprite\SpriteManager::$tempPath . $fileName . $unique . '.css';
			if (!file_exists($cacheFile)) {
				copy(PATH_site . $file, $cacheFile);
			}
		}
		// Get all .css files in dir
		$cssFilesPresentInTempDir = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir(PATH_site . \TYPO3\CMS\Backend\Sprite\SpriteManager::$tempPath, '.css', 0);
		// and delete old ones which are not needed anymore
		$filesToDelete = array_diff($cssFilesPresentInTempDir, $allowedCssFilesinTempDir);
		foreach ($filesToDelete as $file) {
			unlink(PATH_site . \TYPO3\CMS\Backend\Sprite\SpriteManager::$tempPath . $file);
		}
	}

	/**
	 * Interface function. This will be called from the sprite manager to
	 * refresh all caches.
	 *
	 * @return void
	 */
	public function generate() {
		// Include registered Sprites
		$this->loadRegisteredSprites();
		// Cache results in the CSS file
		\TYPO3\CMS\Core\Utility\GeneralUtility::writeFile($this->cssTcaFile, $this->styleSheetData);
	}

	/**
	 * Returns the detected icon-names which may be used through
	 * \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon.
	 *
	 * @return array all generated and registered sprite-icon-names, will be empty if there are none
	 */
	public function getAvailableIconNames() {
		return $this->iconNames;
	}

	/**
	 * this method creates sprite icon names for all tables in TCA (including their possible type-icons)
	 * where there is no "typeicon_classes" of this TCA table ctrl section
	 * (moved form \TYPO3\CMS\Backend\Utility\IconUtility)
	 *
	 * @return array Array as $iconName => $fileName
	 */
	protected function collectTcaSpriteIcons() {
		$tcaTables = array_keys($GLOBALS['TCA']);
		$resultArray = array();
		// Path (relative from typo3 dir) for skin-Images
		if (isset($GLOBALS['TBE_STYLES']['skinImgAutoCfg']['relDir'])) {
			$skinPath = $GLOBALS['TBE_STYLES']['skinImgAutoCfg']['relDir'];
		} else {
			$skinPath = '';
		}
		// check every table in the TCA, if an icon is needed
		foreach ($tcaTables as $tableName) {
			// This method is only needed for TCA tables where
			// typeicon_classes are not configured
			if (is_array($GLOBALS['TCA'][$tableName]) && !is_array($GLOBALS['TCA'][$tableName]['ctrl']['typeicon_classes'])) {
				$tcaCtrl = $GLOBALS['TCA'][$tableName]['ctrl'];
				// Adding the default Icon (without types)
				if (isset($tcaCtrl['iconfile'])) {
					// In CSS we need a path relative to the css file
					// [TCA][ctrl][iconfile] defines icons without path info to reside in gfx/i/
					if (strpos($tcaCtrl['iconfile'], '/') !== FALSE) {
						$icon = $tcaCtrl['iconfile'];
					} else {
						$icon = $skinPath . 'gfx/i/' . $tcaCtrl['iconfile'];
					}
					$icon = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($icon);
					$resultArray['tcarecords-' . $tableName . '-default'] = $icon;
				}
				// If records types are available, register them
				if (isset($tcaCtrl['typeicon_column']) && is_array($tcaCtrl['typeicons'])) {
					foreach ($tcaCtrl['typeicons'] as $type => $icon) {
						// In CSS we need a path relative to the css file
						// [TCA][ctrl][iconfile] defines icons without path info to reside in gfx/i/
						if (strpos($icon, '/') === FALSE) {
							$icon = $skinPath . 'gfx/i/' . $icon;
						}
						$icon = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($icon);
						$resultArray['tcarecords-' . $tableName . '-' . $type] = $icon;
					}
				}
			}
		}
		return $resultArray;
	}

}


?>