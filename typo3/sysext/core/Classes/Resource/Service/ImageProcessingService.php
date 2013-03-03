<?php
namespace TYPO3\CMS\Core\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Andreas Wolf <andreas.wolf@typo3.org>
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
// TODO should this be a singleton?
/**
 * Thumbnail service
 *
 * @author Andreas Wolf <andreas.wolf@typo3.org>
 */

use TYPO3\CMS\Core\Utility\PathUtility;

class ImageProcessingService {

	/**
	 * Renders the actual image
	 *
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
	 * @param $file
	 * @param array $fileConfiguration
	 * @return array
	 */
	public function getImgResource(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject, $file, array $fileConfiguration) {
		if ($fileConfiguration['import.']) {
			$ifile = $contentObject->stdWrap('', $fileConfiguration['import.']);
			if ($ifile) {
				$file = $fileConfiguration['import'] . $ifile;
			}
		}
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($file)) {
			$file = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory')->getFileObject($file);
		}
		if ($file instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
			$theImage = $file->getForLocalProcessing(FALSE);
		} else {
			// clean ../ sections of the path and resolve to proper string.
			// This is necessary for the \TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService to work.
			$file = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($file);
			$theImage = $GLOBALS['TSFE']->tmpl->getFileName($file);
			if (!$theImage) {
				return array();
			}
		}
		$fileConfiguration = $this->processFileConfiguration($fileConfiguration, $contentObject);
		$maskArray = $fileConfiguration['m.'];
		$maskImages = array();
		// Must render mask images and include in hash-calculating - else we
		// cannot be sure the filename is unique for the setup!
		if (is_array($maskArray)) {
			$maskImages['m_mask'] = $this->getImgResource($contentObject, $maskArray['mask'], $maskArray['mask.']);
			$maskImages['m_bgImg'] = $this->getImgResource($contentObject, $maskArray['bgImg'], $maskArray['bgImg.']);
			$maskImages['m_bottomImg'] = $this->getImgResource($contentObject, $maskArray['bottomImg'], $maskArray['bottomImg.']);
			$maskImages['m_bottomImg_mask'] = $this->getImgResource($contentObject, $maskArray['bottomImg_mask'], $maskArray['bottomImg_mask.']);
		}
		// TODO use \TYPO3\CMS\Core\Resource\FileInterface here
		if ($file instanceof \TYPO3\CMS\Core\Resource\FileReference) {
			$hash = $file->getOriginalFile()->calculateChecksum();
		} else {
			$hash = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($theImage . serialize($fileConfiguration) . serialize($maskImages));
		}
		if (isset($GLOBALS['TSFE']->tmpl->fileCache[$hash])) {
			return $GLOBALS['TSFE']->tmpl->fileCache[$hash];
		}
		/** @var $gifCreator \TYPO3\CMS\Frontend\Imaging\GifBuilder */
		$gifCreator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
		$gifCreator->init();
		if ($GLOBALS['TSFE']->config['config']['meaningfulTempFilePrefix']) {
			$filename = PathUtility::basename($theImage);
			// Remove extension
			$filename = substr($filename, 0, strrpos($filename, '.'));
			$tempFilePrefixLength = intval($GLOBALS['TSFE']->config['config']['meaningfulTempFilePrefix']);
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
				/** @var $t3libCsInstance \TYPO3\CMS\Core\Charset\CharsetConverter */
				$t3libCsInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
				$filenamePrefix = $t3libCsInstance->substr('utf-8', $filename, 0, $tempFilePrefixLength);
			} else {
				// Strip everything non-ascii
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
		if ($fileConfiguration['alternativeTempPath'] && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['allowedTempPaths'], $fileConfiguration['alternativeTempPath'])) {
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
		$fileInformation = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($theImage);
		$imgExt = strtolower($fileInformation['fileext']) == $gifCreator->gifExtension ? $gifCreator->gifExtension : 'jpg';
		// If no mask  is used or ImageMagick is disabled, processing is quite simple
		if (!is_array($maskArray) || !$GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
			$fileConfiguration['params'] = $this->modifyImageMagickStripProfileParameters($fileConfiguration['params'], $fileConfiguration);
			$GLOBALS['TSFE']->tmpl->fileCache[$hash] = $gifCreator->imageMagickConvert($theImage, $fileConfiguration['ext'], $fileConfiguration['width'], $fileConfiguration['height'], $fileConfiguration['params'], $fileConfiguration['frame'], $options);
			if (($fileConfiguration['reduceColors'] || $imgExt === 'png' && !$gifCreator->png_truecolor) && is_file($GLOBALS['TSFE']->tmpl->fileCache[$hash][3])) {
				$reduced = $gifCreator->IMreduceColors($GLOBALS['TSFE']->tmpl->fileCache[$hash][3], \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($fileConfiguration['reduceColors'], 256, $gifCreator->truecolorColors, 256));
				if (is_file($reduced)) {
					unlink($GLOBALS['TSFE']->tmpl->fileCache[$hash][3]);
					rename($reduced, $GLOBALS['TSFE']->tmpl->fileCache[$hash][3]);
				}
			}
		} else {
			// Filename:
			$fileDestination = $gifCreator->tempPath . $hash . '.' . $imgExt;
			// Generate!
			if (!file_exists($fileDestination)) {
				$this->processMask($maskImages, $gifCreator, $theImage, $fileConfiguration, $options, $fileDestination);
			}
			// Finish off
			if (($fileConfiguration['reduceColors'] || $imgExt === 'png' && !$gifCreator->png_truecolor) && is_file($fileDestination)) {
				$reduced = $gifCreator->IMreduceColors($fileDestination, \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($fileConfiguration['reduceColors'], 256, $gifCreator->truecolorColors, 256));
				if (is_file($reduced)) {
					unlink($fileDestination);
					rename($reduced, $fileDestination);
				}
			}
			$GLOBALS['TSFE']->tmpl->fileCache[$hash] = $gifCreator->getImageDimensions($fileDestination);
		}
		$GLOBALS['TSFE']->tmpl->fileCache[$hash]['origFile'] = $theImage;
		// This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder, in order for the setup-array to create a unique filename hash.
		$GLOBALS['TSFE']->tmpl->fileCache[$hash]['origFile_mtime'] = @filemtime($theImage);
		$GLOBALS['TSFE']->tmpl->fileCache[$hash]['fileCacheHash'] = $hash;
		if ($file instanceof \TYPO3\CMS\Core\Resource\FileInterface && \TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($GLOBALS['TSFE']->tmpl->fileCache[$hash][3])) {
			$GLOBALS['TSFE']->tmpl->fileCache[$hash][3] = $file->getPublicUrl();
		}
		$imageResource = $GLOBALS['TSFE']->tmpl->fileCache[$hash];
		return $imageResource;
	}

	/**
	 * Renders the mask configuration
	 *
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
			// If ImageMagick version 5+
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_gif']) {
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
				// Remove the temporary images
				foreach ($tempScale as $file) {
					if (@is_file($file)) {
						unlink($file);
					}
				}
			}
		}
	}

	/**
	 * Cleans and sets-up the image-processing configuration array
	 *
	 * @param $fileConfiguration
	 * @param $contentObject
	 * @return array
	 */
	protected function processFileConfiguration($fileConfiguration, $contentObject) {
		$fileConfiguration['width'] = isset($fileConfiguration['width.']) ? $contentObject->stdWrap($fileConfiguration['width'], $fileConfiguration['width.']) : $fileConfiguration['width'];
		$fileConfiguration['height'] = isset($fileConfiguration['height.']) ? $contentObject->stdWrap($fileConfiguration['height'], $fileConfiguration['height.']) : $fileConfiguration['height'];
		$fileConfiguration['ext'] = isset($fileConfiguration['ext.']) ? $contentObject->stdWrap($fileConfiguration['ext'], $fileConfiguration['ext.']) : $fileConfiguration['ext'];
		$fileConfiguration['maxW'] = isset($fileConfiguration['maxW.']) ? intval($contentObject->stdWrap($fileConfiguration['maxW'], $fileConfiguration['maxW.'])) : intval($fileConfiguration['maxW']);
		$fileConfiguration['maxH'] = isset($fileConfiguration['maxH.']) ? intval($contentObject->stdWrap($fileConfiguration['maxH'], $fileConfiguration['maxH.'])) : intval($fileConfiguration['maxH']);
		$fileConfiguration['minW'] = isset($fileConfiguration['minW.']) ? intval($contentObject->stdWrap($fileConfiguration['minW'], $fileConfiguration['minW.'])) : intval($fileConfiguration['minW']);
		$fileConfiguration['minH'] = isset($fileConfiguration['minH.']) ? intval($contentObject->stdWrap($fileConfiguration['minH'], $fileConfiguration['minH.'])) : intval($fileConfiguration['minH']);
		$fileConfiguration['noScale'] = isset($fileConfiguration['noScale.']) ? $contentObject->stdWrap($fileConfiguration['noScale'], $fileConfiguration['noScale.']) : $fileConfiguration['noScale'];
		$fileConfiguration['params'] = isset($fileConfiguration['params.']) ? $contentObject->stdWrap($fileConfiguration['params'], $fileConfiguration['params.']) : $fileConfiguration['params'];
		return $fileConfiguration;
	}

	/**
	 * Modifies the parameters for ImageMagick for stripping of profile information.
	 *
	 * @param 	string		$parameters: The parameters to be modified (if required)
	 * @param 	array		$configuration: The TypoScript configuration of [IMAGE].file
	 * @return string		ImageMagick parameters
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