<?php
namespace TYPO3\CMS\Extbase\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Service for processing images
 */
class ImageService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 * @inject
	 */
	protected $resourceFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
	 * @inject
	 */
	protected $environmentService;

	/**
	 * Create a processed file
	 *
	 * @param File|FileReference $image
	 * @param array $processingInstructions
	 * @return ProcessedFile
	 * @api
	 */
	public function applyProcessingInstructions($image, $processingInstructions) {
		if (is_callable(array($image, 'getOriginalFile'))) {
			// Get the original file from the file reference
			$image = $image->getOriginalFile();
		}

		$processedImage = $image->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingInstructions);
		$this->setCompatibilityValues($processedImage);

		return $processedImage;
	}

	/**
	 * Get public url of image depending on the environment
	 *
	 * @param FileInterface $image
	 * @return string
	 * @api
	 */
	public function getImageUri(FileInterface $image) {
		if ($this->environmentService->isEnvironmentInFrontendMode()) {
			$uriPrefix = $GLOBALS['TSFE']->absRefPrefix;
		} else {
			$uriPrefix = '../';
		}

		return $uriPrefix . $image->getPublicUrl();
	}

	/**
	 * Get File or FileReference object
	 *
	 * This method is a factory and compatibility method that does not belong to
	 * this service, but is put here for pragmatic reasons for the time being.
	 * It should be removed once we do not support string sources for images anymore.
	 *
	 * @param string $src
	 * @param mixed $image
	 * @param boolean $treatIdAsReference
	 * @return FileInterface
	 * @throws \UnexpectedValueException
	 * @internal
	 */
	public function getImage($src, $image, $treatIdAsReference) {
		if (is_null($image)) {
			$image = $this->getImageFromSourceString($src, $treatIdAsReference);
		} elseif (is_callable(array($image, 'getOriginalResource'))) {
			// We have a domain model, so we need to fetch the FAL resource object from there
			$image = $image->getOriginalResource();
		}

		if (!($image instanceof File || $image instanceof FileReference)) {
			throw new \UnexpectedValueException('Supplied file object type ' . get_class($image) . ' must be File or FileReference.', 1382687163);
		}

		return $image;
	}

	/**
	 * Get File or FileReference object by src
	 *
	 * @param string $src
	 * @param boolean $treatIdAsReference
	 * @return FileInterface|FileReference|\TYPO3\CMS\Core\Resource\Folder
	 */
	protected function getImageFromSourceString($src, $treatIdAsReference) {
		if ($this->environmentService->isEnvironmentInBackendMode() && substr($src, 0, 3) === '../') {
			$src = substr($src, 3);
		}
		if (MathUtility::canBeInterpretedAsInteger($src)) {
			if ($treatIdAsReference) {
				$image = $this->resourceFactory->getFileReferenceObject($src);
			} else {
				$image = $this->resourceFactory->getFileObject($src);
			}
		} else {
			// We have a combined identifier or legacy (storage 0) path
			$image = $this->resourceFactory->retrieveFileOrFolderObject($src);
		}
		return $image;
	}

	/**
	 * Set compatibility values to frontend controller object
	 * in case we are in frontend environment.
	 *
	 * @param ProcessedFile $processedImage
	 * @return void
	 */
	protected function setCompatibilityValues(ProcessedFile $processedImage) {
		if ($this->environmentService->isEnvironmentInFrontendMode()) {
			$imageInfo = $this->getCompatibilityImageResourceValues($processedImage);
			$GLOBALS['TSFE']->lastImageInfo = $imageInfo;
			$GLOBALS['TSFE']->imagesOnPage[] = $imageInfo[3];
		}
	}

	/**
	 * Calculates the compatibility values
	 * This is duplicate code taken from ContentObjectRenderer::getImgResource()
	 * Ideally we should get rid of this code in both places.
	 *
	 * @param ProcessedFile $processedImage
	 * @return array
	 */
	protected function getCompatibilityImageResourceValues(ProcessedFile $processedImage) {
		$hash = $processedImage->calculateChecksum();
		if (isset($GLOBALS['TSFE']->tmpl->fileCache[$hash])) {
			$compatibilityImageResourceValues = $GLOBALS['TSFE']->tmpl->fileCache[$hash];
		} else {
			$compatibilityImageResourceValues = array(
				0 => $processedImage->getProperty('width'),
				1 => $processedImage->getProperty('height'),
				2 => $processedImage->getExtension(),
				3 => $processedImage->getPublicUrl(),
				'origFile' => $processedImage->getOriginalFile()->getPublicUrl(),
				'origFile_mtime' => $processedImage->getOriginalFile()->getModificationTime(),
				// This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder,
				// in order for the setup-array to create a unique filename hash.
				'originalFile' => $processedImage->getOriginalFile(),
				'processedFile' => $processedImage,
				'fileCacheHash' => $hash
			);
		}
		return $compatibilityImageResourceValues;
	}
}
