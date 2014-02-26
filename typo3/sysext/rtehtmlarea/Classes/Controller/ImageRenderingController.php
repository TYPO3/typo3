<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Stanislas Rolland <typo3(arobas)sjbr.ca>
 *           Fabien Udriot <fabien.udriot@typo3.org>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource;

/**
 * Render the image attributes and reconstruct magic images, if necessary (and possible)
 */
class ImageRenderingController extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {

	/**
	 * Same as class name
	 *
	 * @var string
	 */
	public $prefixId = 'ImageRenderingController';

	/**
	 * Path to this script relative to the extension dir
	 *
	 * @var string
	 */
	public $scriptRelPath = 'Classes/Controller/ImageRenderingController.php';

	/**
	 * The extension key
	 *
	 * @var string
	 */
	public $extKey = 'rtehtmlarea';

	/**
	 * Configuration
	 *
	 * @var array
	 */
	public $conf = array();

	/**
	 * cObj object
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;

	/**
	 * Returns a processed image to be displayed on the Frontend.
	 *
	 * @param string $content Content input (not used).
	 * @param array $conf TypoScript configuration
	 * @return string HTML output
	 */
	public function renderImageAttributes($content = '', $conf) {

		$imageAttributes = $this->getImageAttributes();

		// It is pretty rare to be in presence of an external image as the default behaviour
		// of the RTE is to download the external image and create a local image.
		// However, it may happen if the RTE has the flag "disable"
		if (!$this->isExternalImage()) {
			$fileUid = (int)$imageAttributes['data-htmlarea-file-uid'];
			if ($fileUid) {
				try {
					$file = Resource\ResourceFactory::getInstance()->getFileObject($fileUid);
					if ($imageAttributes['src'] !== $file->getPublicUrl()) {
						// Source file is a processed image
						$imageConfiguration = array(
							'width' => (int)$imageAttributes['width'],
							'height' => (int)$imageAttributes['height']
						);
						$processedFile = $this->getMagicImageService()->createMagicImage($file, $imageConfiguration);
						$additionalAttributes = array(
							'src' => $processedFile->getPublicUrl(),
							'title' => $imageAttributes['title'] ?: $file->getProperty('title'),
							'alt' => $imageAttributes['alt'] ?: $file->getProperty('alternative'),
							'width' => $processedFile->getProperty('width'),
							'height' => $processedFile->getProperty('height'),
						);
						$imageAttributes = array_merge($imageAttributes, $additionalAttributes);
					}
				} catch (Resource\Exception\FileDoesNotExistException $fileDoesNotExistException) {
					// Log the fact the file could not be retrieved.
					$message = sprintf('I could not find file with uid "%s"', $fileUid);
					$this->getLogger()->error($message);
				}
			}
		}
		return '<img ' . GeneralUtility::implodeAttributes($imageAttributes, TRUE, TRUE) . ' />';
	}

	/**
	 * Returns a sanitizes array of attributes out of $this->cObj
	 *
	 * @return array
	 */
	protected function getImageAttributes() {
		return $this->cObj->parameters;
	}

	/**
	 * Instantiates and prepares the Magic Image service.
	 *
	 * @return \TYPO3\CMS\Core\Resource\Service\MagicImageService
	 */
	protected function getMagicImageService() {

		/** @var $magicImageService Resource\Service\MagicImageService */
		$magicImageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\MagicImageService');

		// Get RTE configuration
		$pageTSConfig = $this->getFrontendObject()->getPagesTSconfig();
		if (is_array($pageTSConfig) && is_array($pageTSConfig['RTE.'])) {
			$magicImageService->setMagicImageMaximumDimensions($pageTSConfig['RTE.']);
		}

		return $magicImageService;
	}

	/**
	 * Tells whether the image URL is found to be "external".
	 *
	 * @return bool
	 */
	protected function isExternalImage() {
		$srcAbsoluteUrl = $this->cObj->parameters['src'];
		return strtolower(substr($srcAbsoluteUrl, 0, 4)) === 'http' || substr($srcAbsoluteUrl, 0, 2) === '//';
	}

	/**
	 * @return \TYPO3\CMS\Core\Log\Logger
	 */
	protected function getLogger() {

		/** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
		$logManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager');

		return $logManager->getLogger(get_class($this));
	}

	/**
	 * Returns an instance of the Frontend object.
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendObject() {
		return $GLOBALS['TSFE'];
	}
}
