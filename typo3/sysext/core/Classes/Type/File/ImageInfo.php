<?php
namespace TYPO3\CMS\Core\Type\File;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A SPL FileInfo class providing information related to an image.
 */
class ImageInfo extends FileInfo {

	/**
	 * @var array
	 */
	protected $imageSizes;

	/**
	 * Returns the width of the Image.
	 *
	 * @return int
	 */
	public function getWidth() {
		$imageSizes = $this->getImageSizes();
		return $imageSizes[0];
	}

	/**
	 * Returns the height of the Image.
	 *
	 * @return int
	 */
	public function getHeight() {
		$imageSizes = $this->getImageSizes();
		return $imageSizes[1];
	}

	/**
	 * @return array
	 */
	protected function getImageSizes() {
		if (is_null($this->imageSizes)) {
			$this->imageSizes = getimagesize($this->getPathname());

			// Fallback to IM identify
			if ($this->imageSizes === FALSE) {
				$this->imageSizes = $this->getGraphicalFunctions()->imageMagickIdentify($this->getPathname());
			}

			// In case the image size could not be retrieved, log the incident as a warning.
			if (empty($this->imageSizes)) {
				$this->getLogger()->warning('I could not retrieve the image size for file ' . $this->getPathname());
				$this->imageSizes = array(0, 0);
			}
		}
		return $this->imageSizes;
	}

	/**
	 * @return \TYPO3\CMS\Core\Log\Logger
	 */
	protected function getLogger(){
		/** @var $loggerManager \TYPO3\CMS\Core\Log\LogManager */
		$loggerManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager');

		return $loggerManager->getLogger(get_class($this));
	}

	/**
	 * @return GraphicalFunctions
	 */
	protected function getGraphicalFunctions() {
		static $graphicalFunctions = NULL;

		if ($graphicalFunctions === NULL) {
			$graphicalFunctions = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');
		}

		return $graphicalFunctions;
	}
}
