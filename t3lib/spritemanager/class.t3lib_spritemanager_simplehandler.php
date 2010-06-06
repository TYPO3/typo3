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
class t3lib_spritemanager_SimpleHandler implements t3lib_spritemanager_SpriteIconGenerator {
	/**
	 * all "registered" Icons available through sprite-api will cumuluated within
	 * @var array
	 */
	protected $iconNames = array();

	/**
	 * contains the content of the CSS file to write
	 * @var String
	 */
	protected $styleSheetData = "/* Auto-Generated via t3lib_spritemanager_SimpleHandler */\n";

	/**
	 * css-template for each sprite-icon of an tca-record-symbol
	 * @var String
	 */
	protected $styleSheetTemplateTca = '
.t3-icon-###TABLE###-###TYPE### {
	background-position: 0px 0px !important;
	background-image: url(\'###IMAGE###\') !important;
}
	';

	/**
	 * css template for single Icons registered by extension authors
	 * @var String
	 */
	protected $styleSheetTemplateExtIcons = '
.t3-icon-###NAME### {
	background-position: 0px 0px;
	background-image: url(\'###IMAGE###\');
}
	';

	/**
	 * path to css file for generated styles
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
	}

	/**
	 * Interface function. This will be called from the sprite manager to
	 * refresh all caches.
	 *
	 * @return void
	 */
	public function generate() {
			// generate Icons for all TCA tables
		$this->buildTcaSpriteIcons();

			// generate IconData for single Icons registered
		$this->buildExtensionSpriteIcons();

			// include registered Sprites
		$this->loadRegisteredSprites();

			// cache results in the CSS file
		t3lib_div::writeFile($this->cssTcaFile, $this->styleSheetData);
	}


	/**
	 * This function builds an css class for every single icon registered via
	 * t3lib_SpriteManager::addSingleIcons to use them via t3lib_iconWorks::getSpriteIcon
	 * In the simpleHandler the icon just will be added as css-background-image.
	 *
	 * @return void
	 */
	protected function buildExtensionSpriteIcons() {
			// backpath from the stylesheet file ($cssTcaFile) to typo3 dir
			// in order to set the background-image URL paths correct
		$iconPath = '../../' . TYPO3_mainDir;

		foreach((array) $GLOBALS['TBE_STYLES']['spritemanager']['singleIcons'] as $iconName => $iconFile) {
			$css = str_replace('###NAME###', str_replace('extensions-', '', $iconName), $this->styleSheetTemplateExtIcons);
			$css = str_replace('###IMAGE###', t3lib_div::resolveBackPath($iconPath . $iconFile), $css);

			$this->iconNames[] = $iconName;
			$this->styleSheetData .= $css;
		}
	}

	/**
	 * Loads all StyleSheets Files registered through
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
		foreach((array) $GLOBALS['TBE_STYLES']['spritemanager']['cssFiles'] as $file) {
			$fileName = basename($file);
				// file should be present
			$allowedCssFilesinTempDir[] = $fileName;
				// get-Cache Filename
			$unique = md5($fileName . filemtime(PATH_site . $file) . filesize(PATH_site . $file));
			$cacheFile = PATH_site . t3lib_SpriteManager::$tempPath . $fileName . $unique . '.css';
			if(!file_exists($cacheFile)) {
				copy(PATH_site . $file, $cacheFile);
			}
		}
			// get all .css files in dir
		$cssFilesPresentInTempDir = t3lib_div::getFilesInDir(PATH_site . t3lib_SpriteManager::$tempPath , '.css', 0);
			// and delete old ones which are not needed anymore
		$filesToDelete = array_diff($cssFilesPresentInTempDir, $allowedCssFilesinTempDir);
		foreach ($filesToDelete as $file) {
			unlink(PATH_site . t3lib_SpriteManager::$tempPath . $file);
		}
	}

	/**
	 * public-interface function: getter for iconNames
	 * will return the detected icon-names which may be used throug t3lib_iconWorks::getSpriteIcon
	 *
	 * @return array all generated and registred sprite-icon-names
	 */
	public function getAvailableIconNames() {
		return $this->iconNames;
	}

	/**
	 * this method creates SpriteIcon names for all tables in TCA (including their possible type-icons)
	 * where there is no "typeicon_classes" of this TCA table ctrl section (moved form t3lib_iconWorks)
	 *
	 * @return void
	 */
	protected function buildTcaSpriteIcons() {
		$tcaTables = array_keys($GLOBALS['TCA']);

			// delete old tempFiles
		@unlink($this->cssTcaFile);

			// backpath from the stylesheet file ($cssTcaFile) to typo3 dir
			// in order to set the background-image URL paths correct
		$iconPath = '../../' . TYPO3_mainDir;

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
			if (!is_array($GLOBALS['TCA'][$tableName]['ctrl']['typeicon_classes'])) {
				$tcaCtrl = $GLOBALS['TCA'][$tableName]['ctrl'];

				$template = str_replace('###TABLE###', $tableName, $this->styleSheetTemplateTca);

					// adding the default Icon (without types)
				if (isset($tcaCtrl['iconfile'])) {
						// in CSS wie need a path relative to the css file
						// [TCA][ctrl][iconfile] defines icons without path info to reside in gfx/i/
					if (strpos($tcaCtrl['iconfile'], '/') !== FALSE) {
						$icon = $tcaCtrl['iconfile'];
					} else {
						$icon = $skinPath . 'gfx/i/' . $tcaCtrl['iconfile'];
					}

					$icon = t3lib_div::resolveBackPath($iconPath . $icon);

						// saving default icon
					$stylesString = str_replace('###TYPE###', 'default', $template);
					$stylesString = str_replace('###IMAGE###', $icon, $stylesString);
					$this->styleSheetData .= $stylesString;
					$this->iconNames[] = 'tcarecords-' . $tableName . '-default';
				}

					// if records types are available, register them
				if (isset($tcaCtrl['typeicon_column']) && is_array($tcaCtrl['typeicons'])) {
					foreach ($tcaCtrl['typeicons'] as $type => $icon) {

							// in CSS wie need a path relative to the css file
							// [TCA][ctrl][iconfile] defines icons without path info to reside in gfx/i/
						if (strpos($icon, '/') === FALSE) {
							$icon = $skinPath . 'gfx/i/' . $icon;
						}

						$icon = t3lib_div::resolveBackPath($iconPath . $icon);
						$stylesString = str_replace('###TYPE###', $type, $template);
						$stylesString = str_replace('###IMAGE###', $icon, $stylesString);
							// saving type icon
						$this->styleSheetData .= $stylesString;
						$this->iconNames[] = 'tcarecords-' . $tableName . '-' . $type;
					}
				}
			}
		}
	}
}

?>
