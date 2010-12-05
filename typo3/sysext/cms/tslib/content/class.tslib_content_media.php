<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010 Steffen Kamper <steffen@typo3.org>
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
 * $Id: class.tslib_content.php 7905 2010-06-13 14:42:33Z ohader $
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_Media extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, MEDIA
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 */
	public function render($conf = array()) {
		$content = '';
		$flexParams = isset($conf['flexParams.'])
			? $this->cObj->stdWrap($conf['flexParams'], $conf['flexParams.'])
			: $conf['flexParams'];
		if (substr($flexParams, 0, 1) === '<') {
				// it is a content element
			$this->cObj->readFlexformIntoConf($flexParams, $conf['parameter.']);
			$url = isset($conf['file.'])
				? $this->cObj->stdWrap($conf['parameter.']['mmFile'], $conf['file.'])
				: $conf['parameter.']['mmFile'];
			$mmFile = $url;
		} else {
				// it is a TS object
			$url = isset($conf['file.'])
				? $this->cObj->stdWrap($conf['file'], $conf['file.'])
				: $conf['file'];
		}

		$mode = is_file(PATH_site . $url) ? 'file' : 'url';
		if ($mode === 'file') {
				// render FILE
			$filename = $GLOBALS['TSFE']->tmpl->getFileName($url);
			$fileinfo = t3lib_div::split_fileref($filename);
			$conf['file'] = $filename;
		} else {
				// render URL
				// use media wizard to extract video from URL
			$mediaWizard = tslib_mediaWizardManager::getValidMediaWizardProvider($url);
			if ($mediaWizard !== NULL) {
				$url = $mediaWizard->rewriteUrl($url);
			}
			$conf['file'] = $this->cObj->typoLink_URL(array(
				'parameter' => $url
			));
		}

		$renderType = isset($conf['renderType.'])
			? $this->cObj->stdWrap($conf['renderType'], $conf['renderType.'])
			: $conf['renderType'];
		$mmRenderType = isset($conf['parameter.']['mmRenderType.'])
			? $this->cObj->stdWrap($conf['parameter.']['mmRenderType'], $conf['parameter.']['mmRenderType.'])
			: $conf['parameter.']['mmRenderType'];
		if ($mmRenderType) {
			$renderType = $mmRenderType;
		}
		if ($renderType === 'auto') {
				// default renderType is swf
			$renderType = 'swf';
			$handler = array_keys($conf['fileExtHandler.']);
			if (in_array($fileinfo['fileext'], $handler)) {
				$renderType = strtolower($conf['fileExtHandler.'][$fileinfo['fileext']]);
			}
		}

		$mmForcePlayer = isset($conf['parameter.']['mmforcePlayer.'])
			? $this->cObj->stdWrap($conf['parameter.']['mmforcePlayer'], $conf['parameter.']['mmforcePlayer.'])
			: $conf['parameter.']['mmforcePlayer'];

		$forcePlayer = $mmFile ? intval($mmForcePlayer) : $conf['forcePlayer'];
		$conf['forcePlayer'] = isset($conf['forcePlayer.'])
			? $this->cObj->stdWrap($forcePlayer, $conf['forcePlayer.'])
			: $forcePlayer;

		$mmType = isset($conf['parameter.']['mmType.'])
			? $this->cObj->stdWrap($conf['parameter.']['mmType'], $conf['parameter.']['mmType.'])
			: $conf['parameter.']['mmType'];

		$type = isset($conf['type.'])
			? $this->cObj->stdWrap($conf['type'], $conf['type.'])
			: $conf['type'];

		$conf['type'] = $mmType ? $mmType : $type;
		$mime = $renderType . 'object';
		$typeConf = $conf['mimeConf.'][$mime . '.'][$conf['type'] . '.'] ? $conf['mimeConf.'][$mime . '.'][$conf['type'] . '.'] : array();
		$conf['predefined'] = array();

		$width = isset($conf['parameter.']['mmWidth.'])
			? intval($this->cObj->stdWrap($conf['parameter.']['mmWidth'], $conf['parameter.']['mmWidth.']))
			: intval($conf['parameter.']['mmWidth']);
		$height = isset($conf['parameter.']['mmHeight.'])
			? intval($this->cObj->stdWrap($conf['parameter.']['mmHeight'], $conf['parameter.']['mmHeight.']))
			: intval($conf['parameter.']['mmHeight']);
		if ($width) {
			$conf['width'] = $width;
		} else {
			$width = isset($conf['width.'])
				? intval($this->cObj->stdWrap($conf['width'], $conf['width.']))
				: intval($conf['width']);
			$conf['width'] = $width ? $width : $typeConf['defaultWidth'];
		}
		if ($height) {
			$conf['height'] = $height;
		} else {
			$height = isset($conf['height.'])
				? intval($this->cObj->stdWrap($conf['height'], $conf['height.']))
				: intval($conf['width']);
			$conf['height'] = $height ? $height : $typeConf['defaultHeight'];
		}

		if (is_array($conf['parameter.']['mmMediaOptions'])) {
			$params = array();
			foreach ($conf['parameter.']['mmMediaOptions'] as $key => $value) {
				if ($key == 'mmMediaCustomParameterContainer') {
					foreach ($value as $val) {
							//custom parameter entry
						$rawTS = $val['mmParamCustomEntry'];
							//read and merge
						$tmp = t3lib_div::trimExplode(LF, $rawTS);
						if (count($tmp)) {
							foreach ($tmp as $tsLine) {
								if (substr($tsLine, 0, 1) != '#' && $pos = strpos($tsLine, '.')) {
									$parts[0] = substr($tsLine, 0, $pos);
									$parts[1] = substr($tsLine, $pos + 1);
									$valueParts = t3lib_div::trimExplode('=', $parts[1], TRUE);

									switch (strtolower($parts[0])) {
										case 'flashvars' :
											$conf['flashvars.'][$valueParts[0]] = $valueParts[1];
										break;
										case 'params' :
											$conf['params.'][$valueParts[0]] = $valueParts[1];
										break;
										case 'attributes' :
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

			// render MEDIA
		if ($mode == 'url' && !$forcePlayer) {
			// url is called direct, not with player
			if ($url == '' && !$conf['allowEmptyUrl']) {
				return '<p style="background-color: yellow;">' . $GLOBALS['TSFE']->sL('LLL:EXT:cms/locallang_ttc.xml:media.noFile', TRUE) . '</p>';
			}
			$conf = array_merge($conf['mimeConf.']['swfobject.'], $conf);
			$conf[$conf['type'] . '.']['player'] = strpos($url, '://') === FALSE ? 'http://' . $url : $url;
			$conf['installUrl'] = 'null';
			$conf['flashvars'] = array_merge((array) $conf['flashvars'], $conf['predefined']);
		}

		switch ($renderType) {
			case 'swf' :
				$conf[$conf['type'] . '.'] = array_merge($conf['mimeConf.']['swfobject.'][$conf['type'] . '.'], $typeConf);
				$conf = array_merge($conf['mimeConf.']['swfobject.'], $conf);
				unset($conf['mimeConf.']);
				$conf['flashvars.'] = array_merge((array) $conf['flashvars.'], $conf['predefined']);
				$content = $this->cObj->SWFOBJECT($conf);
			break;
			case 'qt' :
				$conf[$conf['type'] . '.'] = array_merge($conf['mimeConf.']['swfobject.'][$conf['type'] . '.'], $typeConf);
				$conf = array_merge($conf['mimeConf.']['qtobject.'], $conf);
				unset($conf['mimeConf.']);
				$conf['params.'] = array_merge((array) $conf['params.'], $conf['predefined']);
				$content = $this->cObj->QTOBJECT($conf);
			break;
			case 'embed' :
				$paramsArray = array_merge((array) $typeConf['default.']['params.'], (array) $conf['params.'], $conf['predefined']);
				$conf['params'] = '';
				foreach ($paramsArray as $key => $value) {
					$conf['params'] .= $key . '=' . $value . LF;
				}
				$content = $this->cObj->MULTIMEDIA($conf);
			break;
			default :
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRender'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['customMediaRender'] as $classRef) {
						$hookObj = t3lib_div::getUserObj($classRef);
						$conf['file'] = $url;
						$conf['mode'] = $mode;
						$content = $hookObj->customMediaRender($renderType, $conf, $this);
					}
				}
		}

		if (isset($conf['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}

		return $content;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_media.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_media.php']);
}

?>
