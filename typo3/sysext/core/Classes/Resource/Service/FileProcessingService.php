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
	 * This method actually does the processing of files locally
	 *
	 * takes the original file (on remote storages this will be fetched from the remote server)
	 * does the IM magic on the local server by creating a temporary typo3temp/ file
	 * copies the typo3temp/ file to the processingfolder of the target storage
	 * removes the typo3temp/ file
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @return string
	 */
	protected function processImagePreview(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFile) {
			// Merge custom configuration with default configuration
		$configuration = array_merge(array('width' => 64, 'height' => 64), $processedFile->getProcessingConfiguration());
		$configuration['width'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($configuration['width'], 1, 1000);
		$configuration['height'] = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($configuration['height'], 1, 1000);

		$originalFileName = $processedFile->getOriginalFile()->getForLocalProcessing(FALSE);

			// Create a temporary file in typo3temp/
		if ($processedFile->getOriginalFile()->getExtension() === 'jpg') {
			$targetFileExtension = '.jpg';
		} else {
			$targetFileExtension = '.png';
		}

			// Create the thumb filename in typo3temp/preview_....jpg
		$temporaryFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('preview_') . $targetFileExtension;
			// Check file extension
		if ($processedFile->getOriginalFile()->getType() != \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE &&
			!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $processedFile->getOriginalFile()->getExtension())) {
				// Create a default image
			$this->getTemporaryImageWithText($temporaryFileName, 'Not imagefile!', 'No ext!', $processedFile->getOriginalFile()->getName());
		} else {
				// Create the temporary file
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
				$parameters = (((((('-sample ' . $configuration['width']) . 'x') . $configuration['height']) . ' ') . $this->wrapFileName($originalFileName)) . '[0] ') . $this->wrapFileName($temporaryFileName);
				$cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::imageMagickCommand('convert', $parameters);
				\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);
				if (!file_exists($temporaryFileName)) {
						// Create a error gif
					$this->getTemporaryImageWithText($temporaryFileName, 'No thumb', 'generated!', $processedFile->getOriginalFile()->getName());
				}
			}
		}
		return $temporaryFileName;
	}

	/**
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 *
	 * @return string
	 */
	protected function getFilenameForImagePreview(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFile) {
		if ($processedFile->getOriginalFile()->getExtension() === 'jpg') {
			$targetFileExtension = '.jpg';
		} else {
			$targetFileExtension = '.png';
		}
		return $processedFile->generateProcessedFileNameWithoutExtension() . $targetFileExtension;
	}

	/**
	 * Processes the file
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 *
	 * @throws \RuntimeException
	 */
	public function process(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFile) {
		$supportedContexts = array(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, \TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW);
		if (!in_array($processedFile->getContext(), $supportedContexts)) {
			throw new \RuntimeException('Unknown processing context "' . $processedFile->getContext() . '"');
		} else {
			$contextParts = explode('.', $processedFile->getContext());
			$contextParts = array_map('ucfirst', $contextParts);
			$contextFunctionNamePart = implode('', $contextParts);

			$fileNameFunction = 'getFilenameFor' . $contextFunctionNamePart;
			$processingFunction = 'process' . $contextFunctionNamePart;
		}
		$targetFolder = $this->storage->getProcessingFolder();

		$targetFileName = $this->$fileNameFunction($processedFile);

		if ($targetFolder !== NULL) {
			$this->removeExistingFileIfNeeded($processedFile, $targetFolder, $targetFileName);

			if ($targetFolder->hasFile($targetFileName)) {
				$processedFile->updateProperties(array(
					'identifier' => $targetFolder->getIdentifier() . $targetFileName,
					'name' => $targetFileName
				));
			} else {
				$temporaryFileName = $this->$processingFunction($processedFile);

				if ($temporaryFileName == $processedFile->getOriginalFile()->getForLocalProcessing(FALSE)) {
					$processedFile->updateProperties(array(
						'identifier' => $processedFile->getOriginalFile()->getIdentifier(),
						'name' => ''
					));
				} elseif (file_exists($temporaryFileName)) { // Temporary image could have been created
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
				}
			}
		}

		if ($processedFile->isProcessed()) {
			/** @var $processedFileRepository \TYPO3\CMS\Core\Resource\ProcessedFileRepository */
			$processedFileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');
			if (!$processedFile->isIndexed()) {
					// DB-query to update all info
				$processedFileRepository->add($processedFile);
			} else {
				$processedFileRepository->update($processedFile);
			}
		} else {
			//throw new \RuntimeException('The file could not be processed successfully.');
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
	 * @throws \RuntimeException
	 */
	protected function getTemporaryImageWithText($filename, $textline1, $textline2, $textline3) {
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
			throw new \RuntimeException((((('TYPO3 Fatal Error: No gdlib. ' . $textline1) . ' ') . $textline2) . ' ') . $textline3, 1270853952);
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
	 * @return string
	 */
	protected function processImageCropscalemask(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFile) {
		$originalFileName = $processedFile->getOriginalFile()->getForLocalProcessing(FALSE);
		/** @var $gifBuilder \TYPO3\CMS\Frontend\Imaging\GifBuilder */
		$gifBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
		$gifBuilder->init();

		$configuration = $processedFile->getProcessingConfiguration();
		$configuration['additionalParameters'] = $this->modifyImageMagickStripProfileParameters($configuration['additionalParameters'], $configuration);

		$options = $this->getConfigurationForImageCropscalemask($processedFile, $gifBuilder);
		// Normal situation (no masking)
		if (!(is_array($configuration['maskImages']) && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im'])) {
				// the result info is an array with 0=width,1=height,2=extension,3=filename
			$result = $gifBuilder->imageMagickConvert(
				$originalFileName,
				$configuration['fileExtension'],
				$configuration['width'],
				$configuration['height'],
				$configuration['additionalParameters'],
				$configuration['frame'],
				$options
			);
			$temporaryFileName = $result[3];
		} else {
			$targetFileName = $this->getFilenameForImageCropscalemask($processedFile);
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
					$command = ((('-geometry ' . $tempFileInfo[0]) . 'x') . $tempFileInfo[1]) . '!';
					$command = $this->modifyImageMagickStripProfileParameters($command, $configuration);
					$tmpStr = $gifBuilder->randomName();
					//	m_mask
					$tempScale['m_mask'] = ($tmpStr . '_mask.') . $temporaryExtension;
					$gifBuilder->imageMagickExec($maskImage->getForLocalProcessing(TRUE), $tempScale['m_mask'], $command . $negate);
					//	m_bgImg
					$tempScale['m_bgImg'] = ($tmpStr . '_bgImg.') . trim($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_noloss']);
					$gifBuilder->imageMagickExec($maskBackgroundImage->getForLocalProcessing(), $tempScale['m_bgImg'], $command);
					//	m_bottomImg / m_bottomImg_mask
					if ($maskBottomImage instanceof \TYPO3\CMS\Core\Resource\FileInterface && $maskBottomImageMask instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
						$tempScale['m_bottomImg'] = ($tmpStr . '_bottomImg.') . $temporaryExtension;
						$gifBuilder->imageMagickExec($maskBottomImage->getForLocalProcessing(), $tempScale['m_bottomImg'], $command);
						$tempScale['m_bottomImg_mask'] = ($tmpStr . '_bottomImg_mask.') . $temporaryExtension;
						$gifBuilder->imageMagickExec($maskBottomImageMask->getForLocalProcessing(), $tempScale['m_bottomImg_mask'], $command . $negate);
						// BEGIN combining:
						// The image onto the background
						$gifBuilder->combineExec($tempScale['m_bgImg'], $tempScale['m_bottomImg'], $tempScale['m_bottomImg_mask'], $tempScale['m_bgImg']);
					}
					// The image onto the background
					$gifBuilder->combineExec($tempScale['m_bgImg'], $tempFileInfo[3], $tempScale['m_mask'], $temporaryFileName);
					// Unlink the temp-images...
					foreach ($tempScale as $tempFile) {
						if (@is_file($tempFile)) {
							unlink($tempFile);
						}
					}
				}
			}
		}
		return $temporaryFileName;
	}

	/**
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @param \TYPO3\CMS\Frontend\Imaging\GifBuilder $gifBuilder
	 *
	 * @return array
	 */
	protected function getConfigurationForImageCropscalemask(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFile, \TYPO3\CMS\Frontend\Imaging\GifBuilder $gifBuilder) {
		$configuration = $processedFile->getProcessingConfiguration();

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

		return $options;
	}

	/**
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 *
	 * @return string
	 */
	protected function getFilenameForImageCropscalemask(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFile) {

		$configuration = $processedFile->getProcessingConfiguration();
		$targetFileExtension = $processedFile->getOriginalFile()->getExtension();
		$processedFileExtension = $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'] ? 'png' : 'gif';
		if (is_array($configuration['maskImages']) && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] && $processedFile->getOriginalFile()->getExtension() != $processedFileExtension) {
			$targetFileExtension = 'jpg';
		} elseif($configuration['fileExtension']) {
			$targetFileExtension = $configuration['fileExtension'];
		}

		return $processedFile->generateProcessedFileNameWithoutExtension() . '.' . ltrim(trim($targetFileExtension), '.');
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

	/**
	 * If a file exists, but reprocessing is needed, it is removed from the file system
	 *
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @param $fileName
	 */
	protected function removeExistingFileIfNeeded(\TYPO3\CMS\Core\Resource\ProcessedFile $processedFile, \TYPO3\CMS\Core\Resource\Folder $folder, $fileName) {
		if ($processedFile->needsReprocessing() && $folder->hasFile($fileName)) {
			$this->storage->getFile($folder->getIdentifier() . $fileName)->delete();
		}
	}
}


?>