<?php
namespace TYPO3\CMS\Core\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Oliver Hader <oliver.hader@typo3.org>
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
 * File processing service
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class FileProcessingService {

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $storage;

	/**
	 * @var \TYPO3\CMS\Core\Resource\Driver\AbstractDriver
	 */
	protected $driver;

	/**
	 * Creates this object.
	 *
	 * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storage
	 * @param \TYPO3\CMS\Core\Resource\Driver\AbstractDriver $driver
	 */
	public function __construct(\TYPO3\CMS\Core\Resource\ResourceStorage $storage, \TYPO3\CMS\Core\Resource\Driver\AbstractDriver $driver) {
		$this->storage = $storage;
		$this->driver = $driver;
	}

	/**
	 * Processes the file.
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param string $context
	 * @param array $configuration
	 * @return \TYPO3\CMS\Core\Resource\ProcessedFile
	 */
	public function process(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFile, \TYPO3\CMS\Core\Resource\FileInterface $file, $context, array $configuration = array()) {
		switch ($context) {
		case $processedFile::CONTEXT_IMAGEPREVIEW:
			$this->processImagePreview($processedFile, $file, $configuration);
			break;
		case $processedFile::CONTEXT_IMAGECROPSCALEMASK:
			$this->processImageCropResizeMask($processedFile, $file, $configuration);
			break;
		default:
			throw new \RuntimeException('Unknown processing context "' . $context . '"');
		}
		if ($processedFile->isProcessed()) {
			// DB-query to update all info
			/** @var $processedFileRepository \TYPO3\CMS\Core\Resource\ProcessedFileRepository */
			$processedFileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');
			if ($processedFile->hasProperty('uid') && $processedFile->getProperty('uid') > 0) {
				$processedFileRepository->update($processedFile);
			} else {
				$processedFileRepository->add($processedFile);
			}
		}
		return $processedFile;
	}

	/**
	 * This method actually does the processing of files locally
	 *
	 * takes the original file (on remote storages this will be fetched from the remote server)
	 * does the IM magic on the local server by creating a temporary typo3temp/ file
	 * copies the typo3temp/ file to the processingfolder of the target storage
	 * removes the typo3temp/ file
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param array $configuration
	 * @return void
	 */
	protected function processImagePreview(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFile, \TYPO3\CMS\Core\Resource\FileInterface $file, array $configuration) {
		// Merge custom configuration with default configuration
		$configuration = array_merge(array('width' => 64, 'height' => 64), $configuration);
		$configuration['width'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($configuration['width'], 1, 1000);
		$configuration['height'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($configuration['height'], 1, 1000);
		$originalFileName = $file->getForLocalProcessing(FALSE);
		// Create a temporary file in typo3temp/
		if ($file->getExtension() === 'jpg') {
			$targetFileExtension = '.jpg';
		} else {
			$targetFileExtension = '.png';
		}
		$targetFolder = $this->storage->getProcessingFolder();
		$targetFileName = 'preview_' . $processedFile->calculateChecksum() . $targetFileExtension;
		// Do the actual processing
		if (!$targetFolder->hasFile($targetFileName)) {
			// Create the thumb filename in typo3temp/preview_....jpg
			$temporaryFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('preview_') . $targetFileExtension;
			// Check file extension
			if ($file->getType() != $file::FILETYPE_IMAGE && !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $file->getExtension())) {
				// Create a default image
				$this->getTemporaryImageWithText($temporaryFileName, 'Not imagefile!', 'No ext!', $file->getName());
			} else {
				// Create the temporary file
				if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
					$parameters = '-sample ' . $configuration['width'] . 'x' . $configuration['height'] . ' ' . $this->wrapFileName($originalFileName) . '[0] ' . $this->wrapFileName($temporaryFileName);
					$cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::imageMagickCommand('convert', $parameters);
					\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);
					if (!file_exists($temporaryFileName)) {
						// Create a error gif
						$this->getTemporaryImageWithText($temporaryFileName, 'No thumb', 'generated!', $file->getName());
					}
				}
			}
			// Temporary image could have been created
			if (file_exists($temporaryFileName)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($temporaryFileName);
				// Copy the temporary file to the processedFolder
				// this is done here, as the driver can do this without worrying
				// about existing ProcessedFile objects
				// or permissions in the storage
				// for "remote" storages this means "uploading" the file to the storage again
				// for the virtual storage, it is merely a thing of "copying a file from typo3temp/ to typo3temp/_processed_"
				$this->driver->addFile($temporaryFileName, $targetFolder, $targetFileName, $processedFile);
				// Remove the temporary file as it's not needed anymore
				\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($temporaryFileName);
				$processedFile->setProcessed(TRUE);
			}
		} else {
			// the file already exists, nothing to do locally, but still mark the file as processed and save the data
			// and update the fields, as they might have not been set
			if ($processedFile->getProperty('identifier') == '') {
				$identifier = $targetFolder->getIdentifier() . $targetFileName;
				$processedFile->updateProperties(array('name' => $targetFileName, 'identifier' => $identifier));
			}
			$processedFile->setProcessed(TRUE);
		}
	}

	/**
	 * Escapes a file name so it can safely be used on the command line.
	 *
	 * @param string $inputName filename to safeguard, must not be empty
	 * @return string $inputName escaped as needed
	 */
	protected function wrapFileName($inputName) {
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
			$currentLocale = setlocale(LC_CTYPE, 0);
			setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
			$escapedInputName = escapeshellarg($inputName);
			setlocale(LC_CTYPE, $currentLocale);
		} else {
			$escapedInputName = escapeshellarg($inputName);
		}
		return $escapedInputName;
	}

	/**
	 * Creates error image based on gfx/notfound_thumb.png
	 * Requires GD lib enabled, otherwise it will exit with the three
	 * textstrings outputted as text. Outputs the image stream to browser and exits!
	 *
	 * @param string $filename Name of the file
	 * @param string $textline1 Text line 1
	 * @param string $textline2 Text line 2
	 * @param string $textline3 Text line 3
	 * @return void
	 */
	protected function getTemporaryImageWithText($filename, $textline1, $textline2, $textline3) {
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
			throw new \RuntimeException('TYPO3 Fatal Error: No gdlib. ' . $textline1 . ' ' . $textline2 . ' ' . $textline3, 1270853952);
		}
		// Creates the basis for the error image
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
			$im = imagecreatefrompng(PATH_typo3 . 'gfx/notfound_thumb.png');
		} else {
			$im = imagecreatefromgif(PATH_typo3 . 'gfx/notfound_thumb.gif');
		}
		// Sets background color and print color.
		$white = imageColorAllocate($im, 255, 255, 255);
		$black = imageColorAllocate($im, 0, 0, 0);
		// Prints the text strings with the build-in font functions of GD
		$x = 0;
		$font = 0;
		if ($textline1) {
			imagefilledrectangle($im, $x, 9, 56, 16, $white);
			imageString($im, $font, $x, 9, $textline1, $black);
		}
		if ($textline2) {
			imagefilledrectangle($im, $x, 19, 56, 26, $white);
			imageString($im, $font, $x, 19, $textline2, $black);
		}
		if ($textline3) {
			imagefilledrectangle($im, $x, 29, 56, 36, $white);
			imageString($im, $font, $x, 29, substr($textline3, -14), $black);
		}
		// Outputting the image stream and exit
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) {
			imagePng($im, $filename);
		} else {
			imageGif($im, $filename);
		}
	}

	/**
	 * This method actually does the processing of files locally
	 *
	 * takes the original file (on remote storages this will be fetched from the remote server)
	 * does the IM magic on the local server by creating a temporary typo3temp/ file
	 * copies the typo3temp/ file to the processingfolder of the target storage
	 * removes the typo3temp/ file
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param array $configuration
	 * @return void
	 */
	protected function processImageCropResizeMask(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFile, \TYPO3\CMS\Core\Resource\FileInterface $file, array $configuration) {
		// checks to see if m (the mask array) is defined
		$doMasking = is_array($configuration['maskImages']) && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im'];
		// @todo: is it ok that we use tslib (=FE) here?
		/** @var $gifBuilder tslib_gifbuilder */
		$gifBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tslib_gifbuilder');
		$gifBuilder->init();
		// @todo: this is not clean yet
		if (!trim($configuration['fileExtension'])) {
			$configuration['fileExtension'] = 'web';
			$targetFileExtension = $file->getExtension();
		} elseif ($doMasking) {
			$targetFileExtension = $file->getExtension() == $gifBuilder->gifExtension ? $gifBuilder->gifExtension : 'jpg';
		} else {
			$targetFileExtension = $configuration['fileExtension'];
		}
		$originalFileName = $file->getForLocalProcessing(FALSE);
		$targetFolder = $this->storage->getProcessingFolder();
		$targetFileName = 'previewcrm_' . $processedFile->calculateChecksum() . '.' . $targetFileExtension;
		// @todo: implement meaningful TempFileIndex
		if ($configuration['useSample']) {
			$gifBuilder->scalecmd = '-sample';
		}
		$options = array();
		if ($configuration['maxWidth']) {
			$options['maxW'] = $configuration['maxWidth'];
		}
		if ($configuration['maxHeight']) {
			$options['maxH'] = $configuration['maxHeight'];
		}
		if ($configuration['minWidth']) {
			$options['minW'] = $configuration['minWidth'];
		}
		if ($configuration['minHeight']) {
			$options['minH'] = $configuration['minHeight'];
		}
		$options['noScale'] = $configuration['noScale'];
		$configuration['additionalParameters'] = $this->modifyImageMagickStripProfileParameters($configuration['additionalParameters'], $configuration);
		// Do the actual processing
		if (!$targetFolder->hasFile($targetFileName)) {
			if (!$doMasking) {
				// Normal situation (no masking)
				// the result info is an array with 0=width,1=height,2=extension,3=filename
				list($targetWidth, $targetHeight, $targetExtension, $temporaryFileName) = $gifBuilder->imageMagickConvert($originalFileName, $configuration['fileExtension'], $configuration['width'], $configuration['height'], $configuration['additionalParameters'], $configuration['frame'], $options);
			} else {
				$temporaryFileName = $gifBuilder->tempPath . $targetFileName;
				$maskImage = $configuration['maskImages']['maskImage'];
				$maskBackgroundImage = $configuration['maskImages']['backgroundImage'];
				if ($maskImage instanceof \TYPO3\CMS\Core\Resource\FileInterface && $maskBackgroundImage instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
					$negate = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask'] ? ' -negate' : '';
					$temporaryExtension = 'png';
					if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_gif']) {
						// If ImageMagick version 5+
						$temporaryExtension = $gifBuilder->gifExtension;
					}
					$tempFileInfo = $gifBuilder->imageMagickConvert($originalFileName, $temporaryExtension, $configuration['width'], $configuration['height'], $configuration['additionalParameters'], $configuration['frame'], $options);
					if (is_array($tempFileInfo)) {
						$maskBottomImage = $configuration['maskImages']['maskBottomImage'];
						if ($maskBottomImage instanceof $maskBottomImage) {
							$maskBottomImageMask = $configuration['maskImages']['maskBottomImageMask'];
						}
						//	Scaling:	****
						$tempScale = array();
						$command = '-geometry ' . $tempFileInfo[0] . 'x' . $tempFileInfo[1] . '!';
						$command = $this->modifyImageMagickStripProfileParameters($command, $configuration);
						$tmpStr = $gifBuilder->randomName();
						//	m_mask
						$tempScale['m_mask'] = $tmpStr . '_mask.' . $temporaryExtension;
						$gifBuilder->imageMagickExec($maskImage->getForLocalProcessing(TRUE), $tempScale['m_mask'], $command . $negate);
						//	m_bgImg
						$tempScale['m_bgImg'] = $tmpStr . '_bgImg.' . trim($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_noloss']);
						$gifBuilder->imageMagickExec($maskBackgroundImage->getForLocalProcessing(), $tempScale['m_bgImg'], $command);
						//	m_bottomImg / m_bottomImg_mask
						if ($maskBottomImage instanceof \TYPO3\CMS\Core\Resource\FileInterface && $maskBottomImageMask instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
							$tempScale['m_bottomImg'] = $tmpStr . '_bottomImg.' . $temporaryExtension;
							$gifBuilder->imageMagickExec($maskBottomImage->getForLocalProcessing(), $tempScale['m_bottomImg'], $command);
							$tempScale['m_bottomImg_mask'] = $tmpStr . '_bottomImg_mask.' . $temporaryExtension;
							$gifBuilder->imageMagickExec($maskBottomImageMask->getForLocalProcessing(), $tempScale['m_bottomImg_mask'], $command . $negate);
							// BEGIN combining:
							// The image onto the background
							$gifBuilder->combineExec($tempScale['m_bgImg'], $tempScale['m_bottomImg'], $tempScale['m_bottomImg_mask'], $tempScale['m_bgImg']);
						}
						// The image onto the background
						$gifBuilder->combineExec($tempScale['m_bgImg'], $tempFileInfo[3], $tempScale['m_mask'], $temporaryFileName);
						// Unlink the temp-images...
						foreach ($tempScale as $file) {
							if (@is_file($file)) {
								unlink($file);
							}
						}
					}
				}
				// Finish off
				list($targetWidth, $targetHeight) = $gifBuilder->getImageDimensions($temporaryFileName);
			}
			// Temporary image was created
			if (file_exists($temporaryFileName)) {
				$updatedProperties = array('width' => $targetWidth, 'height' => $targetHeight);
				// ImageMagick did not have to do anything, as it is already there...
				if ($originalFileName !== $temporaryFileName) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($temporaryFileName);
					// Copy the temporary file to the processedFolder
					// this is done here, as the driver can do this without worrying
					// about existing ProcessedFile objects
					// or permissions in the storage
					// for "remote" storages this means "uploading" the file to the storage again
					// for the virtual storage, it is merely a thing of "copying a file from typo3temp/ to typo3temp/_processed_"
					$this->driver->addFile($temporaryFileName, $targetFolder, $targetFileName, $processedFile);
					// Remove the temporary file as it's not needed anymore
					\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($temporaryFileName);
				} else {

				}
				$processedFile->updateProperties($updatedProperties);
				$processedFile->setProcessed(TRUE);
			}
		}
	}

	/**
	 * Modifies the parameters for ImageMagick for stripping of profile information.
	 *
	 * @param string $parameters The parameters to be modified (if required)
	 * @param array $configuration The TypoScript configuration of [IMAGE].file
	 * @return string
	 */
	protected function modifyImageMagickStripProfileParameters($parameters, array $configuration) {
		// Strips profile information of image to save some space:
		if (isset($configuration['stripProfile'])) {
			if ($configuration['stripProfile']) {
				$parameters = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_stripProfileCommand'] . $parameters;
			} else {
				$parameters .= '###SkipStripProfile###';
			}
		}
		return $parameters;
	}

}


?>