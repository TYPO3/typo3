<?php
namespace TYPO3\CMS\Core\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Stanislas Rolland <stanislas.rolland@typo3.org>
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

use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Magic image service
 *
 * @author Stanislas Rolland <stanislas.rolland@typo3.org>
 */
class MagicImageService {

	/**
	 * @var \TYPO3\CMS\Core\Imaging\GraphicalFunctions
	 */
	protected $imageObject;

	/**
	 * Internal function to retrieve the target magic image folder
	 *
	 * @param string $targetFolderCombinedIdentifier
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	protected function getMagicFolder($targetFolderCombinedIdentifier) {
		// check if the input is already a folder
		if ($targetFolderCombinedIdentifier instanceof \TYPO3\CMS\Core\Resource\Folder) {
			$magicFolder = $targetFolderCombinedIdentifier;
		} else {
			$fileFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();

			// @todo Proper exception handling is missing here
			if ($targetFolderCombinedIdentifier) {
				$magicFolder = $fileFactory->getFolderObjectFromCombinedIdentifier($targetFolderCombinedIdentifier);
			}
			if (empty($magicFolder) || !$magicFolder instanceof \TYPO3\CMS\Core\Resource\Folder) {
				$magicFolder = $fileFactory->getFolderObjectFromCombinedIdentifier($GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir']);
			}
		}
		return $magicFolder;
	}

	/**
	 * Internal function to retrieve the image object,
	 * if it does not exist, an instance will be created
	 *
	 * @return \TYPO3\CMS\Core\Imaging\GraphicalFunctions
	 */
	protected function getImageObject() {
		if ($this->imageObject === NULL) {
			/** @var $this->imageObject \TYPO3\CMS\Core\Imaging\GraphicalFunctions */
			$this->imageObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');
			$this->imageObject->init();
			$this->imageObject->mayScaleUp = 0;
			$this->imageObject->tempPath = PATH_site . $this->imageObject->tempPath;
		}
		return $this->imageObject;
	}

	/**
	 * Creates a magic image
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $imageFileObject: the original image file
	 * @param array $fileConfiguration (width, height, maxW, maxH)
	 * @param string $targetFolderCombinedIdentifier: target folder combined identifier
	 * @return \TYPO3\CMS\Core\Resource\FileInterface
	 */
	public function createMagicImage(\TYPO3\CMS\Core\Resource\FileInterface $imageFileObject, array $fileConfiguration, $targetFolderCombinedIdentifier) {
		$magicImage = NULL;
		// Get file for processing
		$imageFilePath = $imageFileObject->getForLocalProcessing(TRUE);
		// Process dimensions
		$maxWidth = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($fileConfiguration['width'], 0, $fileConfiguration['maxW']);
		$maxHeight = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($fileConfiguration['height'], 0, $fileConfiguration['maxH']);
		if (!$maxWidth) {
			$maxWidth = $fileConfiguration['maxW'];
		}
		if (!$maxHeight) {
			$maxHeight = $fileConfiguration['maxH'];
		}
		// Create the magic image
		$magicImageInfo = $this->getImageObject()->imageMagickConvert($imageFilePath, 'WEB', $maxWidth . 'm', $maxHeight . 'm');
		if ($magicImageInfo[3]) {
			$targetFileName = 'RTEmagicC_' . PathUtility::pathInfo($imageFileObject->getName(), PATHINFO_FILENAME) . '.' . PathUtility::pathinfo($magicImageInfo[3], PATHINFO_EXTENSION);
			$magicFolder = $this->getMagicFolder($targetFolderCombinedIdentifier);
			if ($magicFolder instanceof \TYPO3\CMS\Core\Resource\Folder) {
				$magicImage = $magicFolder->addFile($magicImageInfo[3], $targetFileName, 'changeName');
			}
		}
		return $magicImage;
	}

}


?>
