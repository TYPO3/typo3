<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Thumbnail service
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
// TODO should this be a singleton?
class t3lib_file_Service_ImageProcessingService {
	public function getThumbnailOfFile(t3lib_file_File $fileObject, $width = NULL, $height = NULL) {
		/**
		 * (- check if thumbnail exists)
		 * - create thumbnail file
		 * - store thumbnail file to some public, temporary storage
		 * - return a) file object or b) file URL
		 */
	}

	public function getThumbnailFromUsageRecord() {
		/**
		 * - 
		 */
	}

	/**
	 * @param tslib_cObj $contentObject
	 * @param $file
	 * @param array $fileConfiguration
	 * @return array
	 */
	public function getImgResource(tslib_cObj $contentObject, $file, array $fileConfiguration) {
		if ($fileConfiguration['import.']) {
			$ifile = $contentObject->stdWrap('', $fileConfiguration['import.']);
			if ($ifile) {
				$file = $fileConfiguration['import'] . $ifile;
			}
		}
		if (t3lib_utility_Math::canBeInterpretedAsInteger($file)) {
			$file = t3lib_div::makeInstance('t3lib_file_Factory')->getFileObject($file);
		}

		if ($file instanceof t3lib_file_FileInterface) {
			$theImage = $file->getForLocalProcessing(FALSE);
		} else {
			$file = t3lib_div::resolveBackPath($file); // clean ../ sections of the path and resolve to proper string. This is necessary for the t3lib_file_Service_BackwardsCompatibility_TslibContentAdapterService to work.

			$theImage = $GLOBALS['TSFE']->tmpl->getFileName($file);
			if (!$theImage) {
				return array();
			}
		}

		$fileConfiguration = $this->processFileConfiguration($fileConfiguration, $contentObject);

		$maskArray = $fileConfiguration['m.'];
		$maskImages = array();
		if (is_array($maskArray)) { // Must render mask images and include in hash-calculating - else we cannot be sure the filename is unique for the setup!
			$maskImages['m_mask'] = $this->getImgResource($contentObject, $maskArray['mask'], $maskArray['mask.']);
			$maskImages['m_bgImg'] = $this->getImgResource($contentObject, $maskArray['bgImg'], $maskArray['bgImg.']);
			$maskImages['m_bottomImg'] = $this->getImgResource($contentObject, $maskArray['bottomImg'], $maskArray['bottomImg.']);
			$maskImages['m_bottomImg_mask'] = $this->getImgResource($contentObject, $maskArray['bottomImg_mask'], $maskArray['bottomImg_mask.']);
		}

			// TODO use t3lib_file_FileInterface here
		if ($file instanceof t3lib_file_FileReference) {
			$hash = $file->getOriginalFile()->calculateChecksum();
		} else {
			$hash = t3lib_div::shortMD5($theImage . serialize($fileConfiguration) . serialize($maskImages));
		}

		if (isset($GLOBALS['TSFE']->tmpl->fileCache[$hash])) {
			return $GLOBALS['TSFE']->tmpl->fileCache[$hash];
		}

		/** @var $gifCreator tslib_gifbuilder */
		$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
		$gifCreator->init();

		if ($GLOBALS['TSFE']->config['config']['meaningfulTempFilePrefix']) {
			$filename = basename($theImage);
				// remove extension
			$filename = substr($filename, 0, strrpos($filename, '.'));
			$tempFilePrefixLength = intval($GLOBALS['TSFE']->config['config']['meaningfulTempFilePrefix']);
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
					/** @var $t3libCsInstance t3lib_cs */
				$t3libCsInstance = t3lib_div::makeInstance('t3lib_cs');
				$filenamePrefix = $t3libCsInstance->substr('utf-8', $filename, 0, $tempFilePrefixLength);
			} else {
					// strip everything non-ascii
				$filename = preg_replace('/[^A-Za-z0-9_-]/', '', trim($filename));
				$filenamePrefix = substr($filename, 0, $tempFilePrefixLength);
			}
			$gifCreator->filenamePrefix = $filenamePrefix . '_';
			unset($filename);
		}

		if ($fileConfiguration['sample']) {
			$gifCreator->scalecmd = '-sample';
			$GLOBALS['TT']->setTSlogMessage('Sample option: Images are scaled with -sample.');
		}
		if ($fileConfiguration['alternativeTempPath'] && t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['allowedTempPaths'], $fileConfiguration['alternativeTempPath'])) {
			$gifCreator->tempPath = $fileConfiguration['alternativeTempPath'];
			$GLOBALS['TT']->setTSlogMessage('Set alternativeTempPath: ' . $fileConfiguration['alternativeTempPath']);
		}

		if (!trim($fileConfiguration['ext'])) {
			$fileConfiguration['ext'] = 'web';
		}
		$options = array();
		if ($fileConfiguration['maxW']) {
			$options['maxW'] = $fileConfiguration['maxW'];
		}
		if ($fileConfiguration['maxH']) {
			$options['maxH'] = $fileConfiguration['maxH'];
		}
		if ($fileConfiguration['minW']) {
			$options['minW'] = $fileConfiguration['minW'];
		}
		if ($fileConfiguration['minH']) {
			$options['minH'] = $fileConfiguration['minH'];
		}
		if ($fileConfiguration['noScale']) {
			$options['noScale'] = $fileConfiguration['noScale'];
		}

		$fileInformation = t3lib_div::split_fileref($theImage);
		$imgExt = (strtolower($fileInformation['fileext']) == $gifCreator->gifExtension ? $gifCreator->gifExtension : 'jpg');

			// if we have no mask or using ImageMagick is disabled, processing is quite simple
		if (!is_array($maskArray) || !$GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
			$fileConfiguration['params'] = $this->modifyImageMagickStripProfileParameters($fileConfiguration['params'], $fileConfiguration);
			$GLOBALS['TSFE']->tmpl->fileCache[$hash] = $gifCreator->imageMagickConvert($theImage, $fileConfiguration['ext'], $fileConfiguration['width'], $fileConfiguration['height'], $fileConfiguration['params'], $fileConfiguration['frame'], $options);

			if (($fileConfiguration['reduceColors'] || ($imgExt == 'png' && !$gifCreator->png_truecolor)) && is_file($GLOBALS['TSFE']->tmpl->fileCache[$hash][3])) {
				$reduced = $gifCreator->IMreduceColors($GLOBALS['TSFE']->tmpl->fileCache[$hash][3], t3lib_utility_Math::forceIntegerInRange($fileConfiguration['reduceColors'], 256, $gifCreator->truecolorColors, 256));
				if (is_file($reduced)) {
					unlink($GLOBALS['TSFE']->tmpl->fileCache[$hash][3]);
					rename($reduced, $GLOBALS['TSFE']->tmpl->fileCache[$hash][3]);
				}
			}
		} else {
				// Filename:
			$dest = $gifCreator->tempPath . $hash . '.' . $imgExt;
			if (!file_exists($dest)) { // Generate!
				$this->processMask($maskImages, $gifCreator, $theImage, $fileConfiguration, $options, $dest);
			}

				// Finish off
			if (($fileConfiguration['reduceColors'] || ($imgExt == 'png' && !$gifCreator->png_truecolor)) && is_file($dest)) {
				$reduced = $gifCreator->IMreduceColors($dest, t3lib_utility_Math::forceIntegerInRange($fileConfiguration['reduceColors'], 256, $gifCreator->truecolorColors, 256));
				if (is_file($reduced)) {
					unlink($dest);
					rename($reduced, $dest);
				}
			}
			$GLOBALS['TSFE']->tmpl->fileCache[$hash] = $gifCreator->getImageDimensions($dest);
		}

		$GLOBALS['TSFE']->tmpl->fileCache[$hash]['origFile'] = $theImage;
			// This is needed by tslib_gifbuilder, in order for the setup-array to create a unique filename hash.
		$GLOBALS['TSFE']->tmpl->fileCache[$hash]['origFile_mtime'] = @filemtime($theImage);
		$GLOBALS['TSFE']->tmpl->fileCache[$hash]['fileCacheHash'] = $hash;

		if ($file instanceof t3lib_file_FileInterface && t3lib_div::isAbsPath($GLOBALS['TSFE']->tmpl->fileCache[$hash][3])) {
			$GLOBALS['TSFE']->tmpl->fileCache[$hash][3] = $file->getPublicUrl();
		}

		$imageResource = $GLOBALS['TSFE']->tmpl->fileCache[$hash];

		return $imageResource;
	}

	/**
	 * @param $maskImages
	 * @param $gifCreator
	 * @param $theImage
	 * @param $fileConfiguration
	 * @param $options
	 * @param $dest
	 */
	protected function processMask($maskImages, $gifCreator, $theImage, $fileConfiguration, $options, $dest) {
		$m_mask = $maskImages['m_mask'];
		$m_bgImg = $maskImages['m_bgImg'];
		if ($m_mask && $m_bgImg) {
			$negate = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask'] ? ' -negate' : '';

			$temp_ext = 'png';
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_gif']) { // If ImageMagick version 5+
				$temp_ext = $gifCreator->gifExtension;
			}

			$tempFileInfo = $gifCreator->imageMagickConvert($theImage, $temp_ext, $fileConfiguration['width'], $fileConfiguration['height'], $fileConfiguration['params'], $fileConfiguration['frame'], $options);
			if (is_array($tempFileInfo)) {
				$m_bottomImg = $maskImages['m_bottomImg'];
				if ($m_bottomImg) {
					$m_bottomImg_mask = $maskImages['m_bottomImg_mask'];
				}
				// Scaling:
				$tempScale = array();
				$command = '-geometry ' . $tempFileInfo[0] . 'x' . $tempFileInfo[1] . '!';
				$command = $this->modifyImageMagickStripProfileParameters($command, $fileConfiguration);
				$tmpStr = $gifCreator->randomName();

				// m_mask
				$tempScale['m_mask'] = $tmpStr . '_mask.' . $temp_ext;
				$gifCreator->imageMagickExec($m_mask[3], $tempScale['m_mask'], $command . $negate);
				// m_bgImg
				$tempScale['m_bgImg'] = $tmpStr . '_bgImg.' . trim($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_noloss']);
				$gifCreator->imageMagickExec($m_bgImg[3], $tempScale['m_bgImg'], $command);

				// m_bottomImg / m_bottomImg_mask
				if ($m_bottomImg && $m_bottomImg_mask) {
					$tempScale['m_bottomImg'] = $tmpStr . '_bottomImg.' . $temp_ext;
					$gifCreator->imageMagickExec($m_bottomImg[3], $tempScale['m_bottomImg'], $command);
					$tempScale['m_bottomImg_mask'] = $tmpStr . '_bottomImg_mask.' . $temp_ext;
					$gifCreator->imageMagickExec($m_bottomImg_mask[3], $tempScale['m_bottomImg_mask'], $command . $negate);

					// BEGIN combining:
					// The image onto the background (including the mask here)
					$gifCreator->combineExec($tempScale['m_bgImg'], $tempScale['m_bottomImg'], $tempScale['m_bottomImg_mask'], $tempScale['m_bgImg']);
				}

				// The image onto the background
				$gifCreator->combineExec($tempScale['m_bgImg'], $tempFileInfo[3], $tempScale['m_mask'], $dest);

				// Remove the temporary images...
				foreach ($tempScale as $file) {
					if (@is_file($file)) {
						unlink($file);
					}
				}
			}
		}
	}

	protected function processFileConfiguration($fileConfiguration, $contentObject) {
		$fileConfiguration['width'] = isset($fileConfiguration['width.'])
			? $contentObject->stdWrap($fileConfiguration['width'], $fileConfiguration['width.'])
			: $fileConfiguration['width'];
		$fileConfiguration['height'] = isset($fileConfiguration['height.'])
			? $contentObject->stdWrap($fileConfiguration['height'], $fileConfiguration['height.'])
			: $fileConfiguration['height'];
		$fileConfiguration['ext'] = isset($fileConfiguration['ext.'])
			? $contentObject->stdWrap($fileConfiguration['ext'], $fileConfiguration['ext.'])
			: $fileConfiguration['ext'];
		$fileConfiguration['maxW'] = isset($fileConfiguration['maxW.'])
			? intval($contentObject->stdWrap($fileConfiguration['maxW'], $fileConfiguration['maxW.']))
			: intval($fileConfiguration['maxW']);
		$fileConfiguration['maxH'] = isset($fileConfiguration['maxH.'])
			? intval($contentObject->stdWrap($fileConfiguration['maxH'], $fileConfiguration['maxH.']))
			: intval($fileConfiguration['maxH']);
		$fileConfiguration['minW'] = isset($fileConfiguration['minW.'])
			? intval($contentObject->stdWrap($fileConfiguration['minW'], $fileConfiguration['minW.']))
			: intval($fileConfiguration['minW']);
		$fileConfiguration['minH'] = isset($fileConfiguration['minH.'])
			? intval($contentObject->stdWrap($fileConfiguration['minH'], $fileConfiguration['minH.']))
			: intval($fileConfiguration['minH']);
		$fileConfiguration['noScale'] = isset($fileConfiguration['noScale.'])
			? $contentObject->stdWrap($fileConfiguration['noScale'], $fileConfiguration['noScale.'])
			: $fileConfiguration['noScale'];
		$fileConfiguration['params'] = isset($fileConfiguration['params.'])
			? $contentObject->stdWrap($fileConfiguration['params'], $fileConfiguration['params.'])
			: $fileConfiguration['params'];

		return $fileConfiguration;
	}

	/**
	 * Modifies the parameters for ImageMagick for stripping of profile information.
	 *
	 * @param	string		$parameters: The parameters to be modified (if required)
	 * @param	array		$configuration: The TypoScript configuration of [IMAGE].file
	 * @param	string		The modified parameters
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
