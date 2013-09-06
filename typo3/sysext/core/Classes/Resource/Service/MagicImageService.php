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

use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Magic image service
 *
 * @author Stanislas Rolland <stanislas.rolland@typo3.org>
 */
class MagicImageService {

	/**
	 * Creates a magic image
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $imageFileObject: the original image file
	 * @param array $fileConfiguration (width, height, maxW, maxH)
	 * @param string $targetFolderCombinedIdentifier: target folder combined identifier
	 * @return \TYPO3\CMS\Core\Resource\ProcessedFile
	 */
	public function createMagicImage(\TYPO3\CMS\Core\Resource\File $imageFileObject, array $fileConfiguration) {
		$magicImage = NULL;
		// Process dimensions
		$maxWidth = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($fileConfiguration['width'], 0, $fileConfiguration['maxW']);
		$maxHeight = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($fileConfiguration['height'], 0, $fileConfiguration['maxH']);
		if (!$maxWidth) {
			$maxWidth = $fileConfiguration['maxW'];
		}
		if (!$maxHeight) {
			$maxHeight = $fileConfiguration['maxH'];
		}

		return $imageFileObject->process(
			ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
			array(
				'width' => $maxWidth . 'm',
				'height' => $maxHeight . 'm',
			)
		);
	}

}


?>
