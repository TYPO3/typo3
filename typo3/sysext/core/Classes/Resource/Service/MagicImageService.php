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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Magic image service
 *
 * @author Stanislas Rolland <stanislas.rolland@typo3.org>
 */
class MagicImageService {

	/**
	 * Maximum width of magic images
	 * These defaults allow images to be based on their width - to a certain degree - by setting a high height.
	 * Then we're almost certain the image will be based on the width
	 * @var int
	 */
	protected $magicImageMaximumWidth = 300;

	/**
	 * Maximum height of magic images
	 * @var int
	 */
	protected $magicImageMaximumHeight = 1000;

	/**
	 * Creates a magic image
	 *
	 * @param Resource\File $imageFileObject: the original image file
	 * @param array $fileConfiguration (width, height)
	 * @return Resource\ProcessedFile
	 */
	public function createMagicImage(Resource\File $imageFileObject, array $fileConfiguration) {
		// Process dimensions
		$maxWidth = MathUtility::forceIntegerInRange($fileConfiguration['width'], 0, $this->magicImageMaximumWidth);
		$maxHeight = MathUtility::forceIntegerInRange($fileConfiguration['height'], 0, $this->magicImageMaximumHeight);
		if (!$maxWidth) {
			$maxWidth = $this->magicImageMaximumWidth;
		}
		if (!$maxHeight) {
			$maxHeight = $this->magicImageMaximumHeight;
		}
		// Create the magic image
		$magicImage = $imageFileObject->process(
			Resource\ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
			array(
				'width' => $maxWidth . 'm',
				'height' => $maxHeight . 'm'
			)
		);
		return $magicImage;
	}

	/**
	 * Set maximum dimensions of magic images based on RTE configuration
	 *
	 * @param array $rteConfiguration: RTE configuration probably coming from PageTSConfig
	 * @return void
	 */
	public function setMagicImageMaximumDimensions(array $rteConfiguration) {
		// Get maximum dimensions from the configuration of the RTE image button
		$imageButtonConfiguration = (is_array($rteConfiguration['buttons.']) && is_array($rteConfiguration['buttons.']['image.'])) ? $rteConfiguration['buttons.']['image.'] : array();
		if (is_array($imageButtonConfiguration['options.']) && is_array($imageButtonConfiguration['options.']['magic.'])) {
			if ((int) $imageButtonConfiguration['options.']['magic.']['maxWidth'] > 0) {
				$this->magicImageMaximumWidth = (int) $imageButtonConfiguration['options.']['magic.']['maxWidth'];
			}
			if ((int) $imageButtonConfiguration['options.']['magic.']['maxHeight'] > 0) {
				$this->magicImageMaximumHeight = (int) $imageButtonConfiguration['options.']['magic.']['maxHeight'];
			}
		}
	}
}
