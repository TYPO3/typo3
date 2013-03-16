<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2013 Steffen Kamper <steffen@typo3.org>
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
 * Contains MEDIA class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class MediaContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, MEDIA
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		$content = '';
		// Add flex parameters to configuration
		$flexParams = isset($conf['flexParams.']) ? $this->cObj->stdWrap($conf['flexParams'], $conf['flexParams.']) : $conf['flexParams'];
		if (substr($flexParams, 0, 1) === '<') {
			// It is a content element rather a TS object
			$flexParams = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($flexParams, 'T3');
			foreach ($flexParams['data'] as $sheetData) {
				$this->cObj->readFlexformIntoConf($sheetData['lDEF'], $conf['parameter.'], TRUE);
			}
		}
		// Type is video or audio
		$conf['type'] = $this->doFlexFormOverlay($conf, 'type');
		// Video sources
		$sources = $this->doFlexFormOverlay($conf, 'sources', 'mmSourcesContainer');
		if (is_array($sources) && count($sources)) {
			$conf['sources'] = array();
			foreach ($sources as $key => $source) {
				if (isset($source['mmSource'])) {
					$source = $source['mmSource'];
					$conf['sources'][$key] = $this->retrieveMediaUrl($source);
				}
			}
		} else {
			unset($conf['sources']);
		}
		// Video fallback and backward compatibility file
		$videoFallback = $this->doFlexFormOverlay($conf, 'file');

		// Backward compatibility file
		if ($videoFallback !== NULL) {
			$conf['file'] = $this->retrieveMediaUrl($videoFallback);
		} else {
			unset($conf['file']);
		}
		// Audio sources
		$audioSources = $this->doFlexFormOverlay($conf, 'audioSources', 'mmAudioSourcesContainer');
		if (is_array($audioSources) && count($audioSources)) {
			$conf['audioSources'] = array();
			foreach ($audioSources as $key => $source) {
				if (isset($source['mmAudioSource'])) {
					$source = $source['mmAudioSource'];
					$conf['audioSources'][$key] = $this->retrieveMediaUrl($source);
				}
			}
		} else {
			unset($conf['audioSources']);
		}
		// Audio fallback
		$audioFallback = $this->doFlexFormOverlay($conf, 'audioFallback');
		if ($audioFallback) {
			$conf['audioFallback'] = $this->retrieveMediaUrl($audioFallback);
		} else {
			unset($conf['audioFallback']);
		}
		// Caption file
		$caption = $this->doFlexFormOverlay($conf, 'caption');
		if ($caption) {
			$conf['caption'] = $this->retrieveMediaUrl($caption);
		} else {
			unset($conf['caption']);
		}
		// Establish render type
		$renderType = $this->doFlexFormOverlay($conf, 'renderType');
		$conf['preferFlashOverHtml5'] = 0;
		if ($renderType === 'preferFlashOverHtml5') {
			$renderType = 'auto';
		}
		if ($renderType === 'auto') {
			// Default renderType is swf
			$renderType = 'swf';
			$handler = array_keys($conf['fileExtHandler.']);
			if ($conf['type'] === 'video') {
				$fileinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($conf['file']);
			} else {
				$fileinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($conf['audioFallback']);
			}
			if (in_array($fileinfo['fileext'], $handler)) {
				$renderType = strtolower($conf['fileExtHandler.'][$fileinfo['fileext']]);
			}
		}
		$mime = $renderType . 'object';
		$typeConf = $conf['mimeConf.'][$mime . '.'][$conf['type'] . '.'] ? $conf['mimeConf.'][$mime . '.'][$conf['type'] . '.'] : array();
		$conf['predefined'] = array();
		// Width and height
		$conf['width'] = intval($this->doFlexFormOverlay($conf, 'width'));
		$conf['height'] = intval($this->doFlexFormOverlay($conf, 'height'));
		if (is_array($conf['parameter.']['mmMediaOptions'])) {
			foreach ($conf['parameter.']['mmMediaOptions'] as $key => $value) {
				if ($key == 'mmMediaCustomParameterContainer') {
					foreach ($value as $val) {
						// Custom parameter entry
						$rawTS = $val['mmParamCustomEntry'];
						// Read and merge
						$tmp = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, $rawTS);
						if (count($tmp)) {
							foreach ($tmp as $tsLine) {
								if (substr($tsLine, 0, 1) != '#' && ($pos = strpos($tsLine, '.'))) {
									$parts[0] = substr($tsLine, 0, $pos);
									$parts[1] = substr($tsLine, $pos + 1);
									$valueParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('=', $parts[1], TRUE);
									switch (strtolower($parts[0])) {
									case 'flashvars':
										$conf['flashvars.'][$valueParts[0]] = $valueParts[1];
										break;
									case 'params':
										$conf['params.'][$valueParts[0]] = $valueParts[1];
										break;
									case 'attributes':
										$conf['attributes.'][$valueParts[0]] = $valueParts[1];
										break;
									}
								}
							}
						}
					}
				} elseif ($key == 'mmMediaOptionsContainer') {
					foreach ($value as $val) {
						if (isset($val['mmParamSet'])) {
							$pName = $val['mmParamName'];
							$pSet = $val['mmParamSet'];
							$pValue = $pSet == 2 ? $val['mmParamValue'] : ($pSet == 0 ? 'false' : 'true');
							$conf['predefined'][$pName] = $pValue;
						}
					}
				}
			}
		}
		if ($renderType === 'swf' && $this->doFlexFormOverlay($conf, 'useHTML5')) {
			$renderType = 'flowplayer';
		}
		if ($conf['type'] === 'audio' && !isset($conf['audioSources'])) {
			$renderType = 'swf';
		}
		if ($renderType !== 'qt' && $renderType !== 'embed' && $conf['type'] == 'video') {
			if (isset($conf['file']) && (strpos($conf['file'], '.swf') !== FALSE || strpos($conf['file'], '://') !== FALSE && strpos(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($conf['file'], 2), 'application/x-shockwave-flash') !== FALSE)) {
				$conf = array_merge((array) $conf['mimeConf.']['swfobject.'], $conf);
				$conf[$conf['type'] . '.']['player'] = strpos($conf['file'], '://') === FALSE ? 'http://' . $conf['file'] : $conf['file'];
				$conf['installUrl'] = 'null';
				$conf['forcePlayer'] = 0;
				$renderType = 'swf';
			} elseif (isset($conf['file']) && strpos($conf['file'], '://') !== FALSE) {
				$mediaWizard = \TYPO3\CMS\Frontend\MediaWizard\MediaWizardProviderManager::getValidMediaWizardProvider($conf['file']);
				if ($mediaWizard !== NULL) {
					$conf['installUrl'] = 'null';
					$conf['forcePlayer'] = 0;
					$renderType = 'swf';
				}
			} elseif (isset($conf['file']) && !isset($conf['caption']) && !isset($conf['sources'])) {
				$renderType = 'swf';
				$conf['forcePlayer'] = 1;
			}
		}
		switch ($renderType) {
		case 'flowplayer':
			$conf[$conf['type'] . '.'] = array_merge((array) $conf['mimeConf.']['flowplayer.'][($conf['type'] . '.')], $typeConf);
			$conf = array_merge((array) $conf['mimeConf.']['flowplayer.'], $conf);
			unset($conf['mimeConf.']);
			$conf['attributes.'] = array_merge((array) $conf['attributes.'], $conf['predefined']);
			$conf['params.'] = array_merge((array) $conf['params.'], $conf['predefined']);
			$conf['flashvars.'] = array_merge((array) $conf['flashvars.'], $conf['predefined']);
			$content = $this->cObj->FLOWPLAYER($conf);
			break;
		case 'swf':
			$conf[$conf['type'] . '.'] = array_merge((array) $conf['mimeConf.']['swfobject.'][($conf['type'] . '.')], $typeConf);
			$conf = array_merge((array) $conf['mimeConf.']['swfobject.'], $conf);
			unset($conf['mimeConf.']);
			$conf['flashvars.'] = array_merge((array) $conf['flashvars.'], $conf['predefined']);
			$content = $this->cObj->SWFOBJECT($conf);
			break;
		case 'qt':
			$conf[$conf['type'] . '.'] = array_merge($conf['mimeConf.']['swfobject.'][$conf['type'] . '.'], $typeConf);
			$conf = array_merge($conf['mimeConf.']['qtobject.'], $conf);
			unset($conf['mimeConf.']);
			$conf['params.'] = array_merge((array) $conf['params.'], $conf['predefined']);
			$content = $this->cObj->QTOBJECT($conf);
			break;
		case 'embed':
			$paramsArray = array_merge((array) $typeConf['default.']['params.'], (array) $conf['params.'], $conf['predefined']);
			$conf['params'] = '';
			foreach ($paramsArray as $key => $value) {
				$conf['params'] .= $key . '=' . $value . LF;
			}
			$content = $this->cObj->MULTIMEDIA($conf);
			break;
		default:
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRender'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRender'] as $classRef) {
					$hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
					$conf['file'] = $videoFallback;
					$conf['mode'] = is_file(PATH_site . $videoFallback) ? 'file' : 'url';
					if (method_exists($hookObj, 'customMediaRender')) {
						$content = $hookObj->customMediaRender($renderType, $conf, $this);
					}
				}
			}
			if (isset($conf['stdWrap.'])) {
				$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
			}
		}
		return $content;
	}

	/**
	 * Resolves the URL of an file
	 *
	 * @param string $file
	 * @return string|NULL
	 */
	protected function retrieveMediaUrl($file) {
		$returnValue = NULL;

		// because the file value can possibly have link parameters, use explode to split all values
		$fileParts = explode(' ', $file);

		/** @var $mediaWizard \TYPO3\CMS\Frontend\MediaWizard\MediaWizardProviderInterface */
		$mediaWizard = \TYPO3\CMS\Frontend\MediaWizard\MediaWizardProviderManager::getValidMediaWizardProvider($fileParts[0]);
		// Get the path relative to the page currently outputted
		if (substr($fileParts[0], 0, 5) === "file:") {
			$fileUid = substr($fileParts[0], 5);

			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($fileUid)) {
				$fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObject($fileUid);

				if ($fileObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
					$returnValue = $fileObject->getPublicUrl();
				}
			}
		} elseif (is_file(PATH_site . $fileParts[0])) {
			$returnValue = $GLOBALS['TSFE']->tmpl->getFileName($fileParts[0]);
		} elseif ($mediaWizard !== NULL) {
			$returnValue = $this->cObj->typoLink_URL(array(
				'parameter' => $mediaWizard->rewriteUrl($fileParts[0])
			));
		} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($fileParts[0])) {
			$returnValue = $fileParts[0];
		}

		return $returnValue;
	}

	/**
	 * Looks up if the key is set via flexform and returns the actual value.
	 * If not present in flexform, it processes the value which might be given in TS
	 * with stdWrap (if needed) and returns that value.
	 *
	 * @param array &$confArray
	 * @param string $key
	 * @param string $sectionKey
	 * @return mixed
	 */
	protected function doFlexFormOverlay(array &$confArray, $key, $sectionKey = NULL) {
		$flexValue = NULL;
		$flexKey = 'mm' . ucfirst($key);
		if ($sectionKey === NULL) {
			$flexValue = $confArray['parameter.'][$flexKey];
		} else {
			$flexValue = $confArray['parameter.'][$flexKey][$sectionKey];
		}
		if ($flexValue === NULL) {
			$flexValue = isset($confArray[$key . '.']) ? $this->cObj->stdWrap($confArray[$key], $confArray[$key . '.']) : $confArray[$key];
		}
		return $flexValue;
	}

}


?>
