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
 * sprite generator
 *
 * @author	Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */

class t3lib_spritemanager_SpriteGenerator {
	/**
	 * template creating CSS for the spritefile
	 *
	 * @var string
	 */
	protected $templateSprite = '
.###NAMESPACE###-###SPRITENAME### {
	background-image: url(\'###SPRITEURL###\') !important;
	height: ###DEFAULTHEIGHT###px;
	width: ###DEFAULTWIDTH###px;
}
';

	/**
	 *
	 * template creating CSS for position and size of a single icon
	 *
	 * @var string
	 */
	protected $templateIcon = '.###NAMESPACE###-###ICONNAME### {
	background-position: -###LEFT###px -###TOP###px !important;
###SIZE_INFO###
}
';

	/**
	 * most common icon-width in the sprite
	 *
	 * @var int
	 */
	protected $defaultWidth = 0;

	/**
	 * most common icon-height in the sprite
	 *
	 * @var int
	 */
	protected $defaultHeight = 0;

	/**
	 * calculated width of the sprite
	 *
	 * @var int
	 */
	protected $spriteWidth = 0;

	/**
	 * calculated height of the sprite
	 * @var int
	 */
	protected $spriteHeight = 0;

	/**
	 * sprite name, will be the filename, too
	 *
	 * @var string
	 */
	protected $spriteName = '';

	/**
	 * the folder the sprite-images will be saved (relative to PATH_site)
	 *
	 * @var string
	 */
	protected $spriteFolder = 'typo3temp/sprites/';

	/**
	 * the folder the sprite-cs will be saved (relative to PATH_site)
	 *
	 * @var string
	 */
	protected $cssFolder = 'typo3temp/sprites/';

	/**
	 * the spriteName will not be included in icon names
	 *
	 * @var boolean
	 */
	protected $ommitSpriteNameInIconName = FALSE;

	/**
	 * namespace of css classes
	 *
	 * @var string
	 */
	protected $nameSpace = 't3-icon';

	/**
	 * setting this to TRUE, the timestamp of the creation will be included to the background import
	 * helps to easily rebuild sprites without cache problems
	 *
	 * @var boolean
	 */
	protected $includeTimestampInCSS = TRUE;

	/**
	 * all bases/root-names included in the sprite which has to be in css
	 * as sprite to include the background-image
	 *
	 * @var array
	 */
	protected $spriteBases = array();

	/**
	 * collects data about all icons to process
	 *
	 * @var array
	 */
	protected $iconsData = array();

	/**
	 * collects all sizes of icons within this sprite and there count
	 *
	 * @var array
	 */
	protected $iconSizes = array();

	/**
	 * maps icon-sizes to iconnames
	 *
	 * @var array
	 */
	protected $iconNamesPerSize = array();

	/**
	 * space in px between to icons in the sprite (gap)
	 *
	 * @var int
	 */
	protected $space = 2;

	/**
	 * Initializes the configuration of the spritegenerator
	 *
	 * @param string $spriteName	the name of the sprite to be generated
	 * @return void
	 */
	public function __construct($spriteName) {
		$this->spriteName = $spriteName;
	}

	/**
	 * Sets namespace of css code
	 *
	 * @param string $string
	 * @return t3lib_spritemanager_SpriteGenerator	an instance of $this, to enable chaining.
	 */
	public function setNamespace($nameSpace) {
		$this->nameSpace = $nameSpace;
		return $this;
	}

	/**
	 * Sets the spritename
	 *
	 * @param string $spriteName	the name of the sprite to be generated
	 * @return t3lib_spritemanager_SpriteGenerator	an instance of $this, to enable chaining.
	 */
	public function setSpriteName($spriteName) {
		$this->spriteName = $spriteName;
		return $this;
	}

	/**
	 * Sets the sprite-graphics target-folder
	 *
	 * @param string $folder the target folder where the generated sprite is stored
	 * @return t3lib_spritemanager_SpriteGenerator	an instance of $this, to enable chaining.
	 */
	public function setSpriteFolder($folder) {
		$this->spriteFolder = $folder;
		return $this;
	}

	/**
	 * Sets the sprite-css target-folder
	 *
	 * @param string $folder the target folder where the generated CSS files are stored
	 * @return t3lib_spritemanager_SpriteGenerator	an instance of $this, to enable chaining.
	 */
	public function setCSSFolder($folder) {
		$this->cssFolder = $folder;
		return $this;
	}

	/**
	 * Setter do enable the exclusion of the sprites-name from iconnames
	 *
	 * @param boolean $value
	 * @return t3lib_spritemanager_SpriteGenerator	an instance of $this, to enable chaining.
	 */
	public function setOmmitSpriteNameInIconName($value) {
		$this->ommitSpriteNameInIconName = is_bool($value) ? $value : FALSE;
		return $this;
	}

	/**
	 * Setter to adjust how much space is between to icons in the sprite
	 *
	 * @param int $value
	 * @return t3lib_spritemanager_SpriteGenerator	an instance of $this, to enable chaining.
	 */
	public function setIconSpace($value) {
		$this->space = intval($value);
		return $this;
	}

	/**
	 * Setter for timestamp inclusion: imageFiles will be included with ?timestamp
	 *
	 * @param boolean $value
	 * @return t3lib_spritemanager_SpriteGenerator	an instance of $this, to enable chaining.
	 */
	public function setIncludeTimestampInCSS($value) {
		$this->includeTimestampInCSS = is_bool($value) ? $value : TRUE;
		return $this;
	}

	/**
	 * Reads all png,gif,jpg files from the passed folder name (including 1 subfolder level)
	 * extracts size information and stores data in internal array,
	 * afterwards triggers sprite generation.
	 *
	 * @param array $inputFolder folder from which files are read
	 * @return	array
	 */
	public function generateSpriteFromFolder(array $inputFolder) {
		$iconArray = array();
		foreach ($inputFolder as $folder) {
				// detect all files to be included in sprites
			$iconArray = array_merge(
				$iconArray,
				$this->getFolder($folder)
			);
		}
		return $this->generateSpriteFromArray($iconArray);
	}

	/**
	 * Method processes an array of files into an sprite,
	 * the array can be build from files within an folder or
	 * by hand (as the SpriteManager does)
	 *
	 * @param array $files array(icon name => icon file)
	 * @return array
	 */
	public function generateSpriteFromArray(array $files) {
		if (!$this->ommitSpriteNameInIconName) {
			$this->spriteBases[] = $this->spriteName;
		}

		$this->buildFileInformationCache($files);
			// calculate Icon Position in sprite
		$this->calculateSpritePositions();

		$this->generateGraphic();

		$this->generateCSS();

		$iconNames = array_keys($this->iconsData);
		natsort($iconNames);

		return array(
			'spriteImage' => PATH_site . $this->spriteFolder . $this->spriteName . '.png',
			'cssFile' => PATH_site . $this->cssFolder . $this->spriteName . '.css',
			'iconNames' => $iconNames
		);
	}

	/**
	 * Generates the css files
	 *
	 * @return void
	 */
	protected function generateCSS() {
		$cssData = '';

		if ($this->includeTimestampInCSS) {
			$timestamp = '?' . time();
		} else {
			$timestamp = '';
		}

		$spritePathForCSS = $this->resolveSpritePath();

		$markerArray = array(
			'###NAMESPACE###' => $this->nameSpace,
			'###DEFAULTWIDTH###' => $this->defaultWidth,
			'###DEFAULTHEIGHT###' => $this->defaultHeight,
			'###SPRITENAME###' => '',
			'###SPRITEURL###' => ($spritePathForCSS ? $spritePathForCSS . '/' : '')
		);
		$markerArray['###SPRITEURL###'] .= $this->spriteName . '.png' . $timestamp;

		foreach ($this->spriteBases as $base) {
			$markerArray['###SPRITENAME###'] = $base;
			$cssData .= t3lib_parsehtml::substituteMarkerArray($this->templateSprite, $markerArray);
		}

		foreach ($this->iconsData as $key => $data) {
			$temp = $data['iconNameParts'];
			array_shift($temp);
			$cssName = implode('-', $temp);
			$markerArrayIcons = array(
				'###NAMESPACE###' => $this->nameSpace,
				'###ICONNAME###' => $cssName,
				'###LEFT###' => $data['left'],
				'###TOP###' => $data['top'],
				'###SIZE_INFO###' => ''
			);
			if ($data['height'] != $this->defaultHeight) {
				$markerArrayIcons['###SIZE_INFO###'] .= TAB . 'height: ' . $data['height'] . 'px;' . LF;
			}
			if ($data['width'] != $this->defaultWidth) {
				$markerArrayIcons['###SIZE_INFO###'] .= TAB . 'width: ' . $data['width'] . 'px;' . LF;
			}
			$cssData .= t3lib_parsehtml::substituteMarkerArray($this->templateIcon, $markerArrayIcons);

		}

		t3lib_div::writeFile(PATH_site . $this->cssFolder . $this->spriteName . '.css', $cssData);
	}

	/**
	 * Compares image path to CSS path and creates the relative backpath from css to the sprites
	 *
	 * @return string
	 */
	protected function resolveSpritePath() {
			// Fix window paths
		$this->cssFolder = str_replace('\\', '/', $this->cssFolder);
		$this->spriteFolder = str_replace('\\', '/', $this->spriteFolder);

		$cssPathSegments = t3lib_div::trimExplode('/', trim($this->cssFolder, '/'));
		$graphicPathSegments = t3lib_div::trimExplode('/', trim($this->spriteFolder, '/'));

		$i = 0;
		while (isset($cssPathSegments[$i]) && isset($graphicPathSegments[$i]) &&
			   $cssPathSegments[$i] == $graphicPathSegments[$i]) {
			unset($cssPathSegments[$i]);
			unset($graphicPathSegments[$i]);
			++$i;
		}
		foreach ($cssPathSegments as $key => $value) {
			$cssPathSegments[$key] = '..';
		}
		$completePath = array_merge($cssPathSegments, $graphicPathSegments);
		$path = implode('/', $completePath);
		return t3lib_div::resolveBackPath($path);
	}

	/**
	 * The actual sprite generator, renders the command for Im/GM and executes
	 *
	 * @return void
	 */
	protected function generateGraphic() {
		$tempSprite = t3lib_div::tempnam($this->spriteName);

		$filePath = array(
			'mainFile' => PATH_site . $this->spriteFolder . $this->spriteName . '.png',
		);
			// create black true color image with given size
		$newSprite = imagecreatetruecolor($this->spriteWidth, $this->spriteHeight);
		imagesavealpha($newSprite, TRUE);
			// make it transparent
		imagefill($newSprite, 0, 0, imagecolorallocatealpha($newSprite, 0, 255, 255, 127));
		foreach ($this->iconsData as $icon) {
			$function = 'imagecreatefrom' . strtolower($icon['fileExtension']);
			if (function_exists($function)) {
				$currentIcon = $function($icon['fileName']);
				imagecopy($newSprite, $currentIcon, $icon['left'], $icon['top'], 0, 0, $icon['width'], $icon['height']);
			}
		}
		imagepng($newSprite, $tempSprite . '.png');

		t3lib_div::upload_copy_move($tempSprite . '.png', $filePath['mainFile']);
		t3lib_div::unlink_tempfile($tempSprite . '.png');
	}

	/**
	 * Arranges icons in sprites,
	 * afterwards all icons have information about ther position in sprite
	 */
	protected function calculateSpritePositions() {
		$currentLeft = 0;
		$currentTop = 0;
			// calculate width of every icon-size-group
		$sizes = array();
		foreach ($this->iconSizes as $sizeTag => $count) {
			$size = $this->explodeSizeTag($sizeTag);
			$rowWidth = ceil(sqrt($count)) * $size['width'];
			while (isset($sizes[$rowWidth])) {
				$rowWidth++;
			}
			$sizes[$rowWidth] = $sizeTag;
		}
			// reverse sorting: widest group to top
		krsort($sizes);
			// integerate all icons grouped by icons size into the sprite
		foreach ($sizes as $sizeTag) {
			$size = $this->explodeSizeTag($sizeTag);
			$currentLeft = 0;
			$rowCounter = 0;

			$rowSize = ceil(sqrt($this->iconSizes[$sizeTag]));

			$rowWidth = $rowSize * $size['width'] + ($rowSize - 1) * $this->space;
			$this->spriteWidth = ($rowWidth > $this->spriteWidth ? $rowWidth : $this->spriteWidth);
			$firstLine = TRUE;

			natsort($this->iconNamesPerSize[$sizeTag]);
			foreach ($this->iconNamesPerSize[$sizeTag] as $iconName) {
				if ($rowCounter == $rowSize - 1) {
					$rowCounter = -1;
				} elseif ($rowCounter == 0) {
					if (!$firstLine) {
						$currentTop += $size['height'];
						$currentTop += $this->space;
					}
					$firstLine = FALSE;
					$currentLeft = 0;
				}
				$this->iconsData[$iconName]['left'] = $currentLeft;
				$this->iconsData[$iconName]['top'] = $currentTop;

				$currentLeft += $size['width'];
				$currentLeft += $this->space;

				$rowCounter++;
			}
			$currentTop += $size['height'];
			$currentTop += $this->space;
		}
		$this->spriteHeight = $currentTop;
	}

	/**
	 * Function getFolder traverses the target directory,
	 * locates all iconFiles and collects them into an array
	 *
	 * @param string path to an folder which contains images
	 * @return array returns an array with all files key: iconname, value: fileName
	 */
	protected function getFolder($directoryPath) {
		$subFolders = t3lib_div::get_dirs(PATH_site . $directoryPath);
		if (!$this->ommitSpriteNameInIconName) {
			$subFolders[] = '';
		}
		$resultArray = array();

		foreach ($subFolders as $folder) {
			if ($folder !== '.svn') {
				$icons = t3lib_div::getFilesInDir(PATH_site . $directoryPath . $folder . '/', 'gif,png,jpg');
				if (!in_array($folder, $this->spriteBases) && count($icons) && $folder !== '') {
					$this->spriteBases[] = $folder;
				}
				foreach ($icons as $icon) {
					$fileInfo = pathinfo($icon);

					$iconName = ($folder ? $folder . '-' : '') . $fileInfo['filename'];
					if (!$this->ommitSpriteNameInIconName) {
						$iconName = $this->spriteName . '-' . $iconName;
					}

					$resultArray[$iconName] = $directoryPath . $folder . '/' . $icon;
				}
			}
		}
		return $resultArray;
	}

	/**
	 * Generates file information cache from file array
	 *
	 * @param array	list of all files with their icon name
	 * @return void
	 */
	protected function buildFileInformationCache(array $files) {
		foreach ($files as $iconName => $iconFile) {

			$iconNameParts = t3lib_div::trimExplode('-', $iconName);
			if (!in_array($iconNameParts[0], $this->spriteBases)) {
				$this->spriteBases[] = $iconNameParts[0];
			}
			$fileInfo = @pathinfo(PATH_site . $iconFile);
			$imageInfo = @getimagesize(PATH_site . $iconFile);

			$this->iconsData[$iconName] = array(
				'iconName' => $iconName,
				'iconNameParts' => $iconNameParts,
				'singleName' => $fileInfo['filename'],
				'fileExtension' => $fileInfo['extension'],
				'fileName' => PATH_site . $iconFile,
				'width' => $imageInfo[0],
				'height' => $imageInfo[1],
				'left' => 0,
				'top' => 0
			);

			$sizeTag = $imageInfo[0] . 'x' . $imageInfo[1];
			if (isset($this->iconSizes[$sizeTag])) {
				$this->iconSizes[$sizeTag] += 1;
			} else {
				$this->iconSizes[$sizeTag] = 1;
				$this->iconNamesPerSize[$sizeTag] = array();
			}
			$this->iconNamesPerSize[$sizeTag][] = $iconName;
		}
			// find most common image size, save it as default
		asort($this->iconSizes);
		$defaultSize = $this->explodeSizeTag(array_pop(array_keys($this->iconSizes)));
		$this->defaultWidth = $defaultSize['width'];
		$this->defaultHeight = $defaultSize['height'];
	}

	/**
	 * Transforms size tag into size array
	 *
	 * @param string  a size tag at the cache arrays
	 * @return array
	 */
	protected function explodeSizeTag($tag = '') {
		$size = t3lib_div::trimExplode("x", $tag);
		return array(
			'width' => $size[0],
			'height' => $size[1]
		);
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/spritemanager/class.t3lib_spritemanager_spritegenerator.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/spritemanager/class.t3lib_spritemanager_spritegenerator.php']);
}
?>