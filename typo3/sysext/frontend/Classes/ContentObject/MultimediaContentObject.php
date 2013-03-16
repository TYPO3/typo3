<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) Steffen Kamper <steffen@typo3.org>
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
 * Contains MULTIMEDIA class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class MultimediaContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, MULTIMEDIA
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		$content = '';
		$filename = isset($conf['file.']) ? $this->cObj->stdWrap($conf['file'], $conf['file.']) : $conf['file'];
		$incFile = $GLOBALS['TSFE']->tmpl->getFileName($filename);
		if ($incFile) {
			$fileinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($incFile);
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('txt,html,htm', $fileinfo['fileext'])) {
				$content = $GLOBALS['TSFE']->tmpl->fileContent($incFile);
			} else {
				// Default params...
				$parArray = array();
				// Src is added
				$width = isset($conf['width.']) ? $this->cObj->stdWrap($conf['width'], $conf['width.']) : $conf['width'];
				if (!$width) {
					$width = 200;
				}
				$height = isset($conf['height.']) ? $this->cObj->stdWrap($conf['height'], $conf['height.']) : $conf['height'];
				if (!$height) {
					$height = 200;
				}
				$parArray['src'] = 'src="' . $GLOBALS['TSFE']->absRefPrefix . $incFile . '"';
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('au,wav,mp3', $fileinfo['fileext'])) {

				}
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('avi,mov,mpg,asf,wmv', $fileinfo['fileext'])) {
					$parArray['width'] = 'width="' . $width . '"';
					$parArray['height'] = 'height="' . $height . '"';
				}
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('swf,swa,dcr', $fileinfo['fileext'])) {
					$parArray['quality'] = 'quality="high"';
					$parArray['width'] = 'width="' . $width . '"';
					$parArray['height'] = 'height="' . $height . '"';
				}
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('class', $fileinfo['fileext'])) {
					$parArray['width'] = 'width="' . $width . '"';
					$parArray['height'] = 'height="' . $height . '"';
				}
				// fetching params
				$params = isset($conf['params.']) ? $this->cObj->stdWrap($conf['params'], $conf['params.']) : $conf['params'];
				$lines = explode(LF, $params);
				foreach ($lines as $l) {
					$parts = explode('=', $l);
					$parameter = strtolower(trim($parts[0]));
					$value = trim($parts[1]);
					if ((string) $value != '') {
						$parArray[$parameter] = $parameter . '="' . htmlspecialchars($value) . '"';
					} else {
						unset($parArray[$parameter]);
					}
				}
				if ($fileinfo['fileext'] == 'class') {
					unset($parArray['src']);
					$parArray['code'] = 'code="' . htmlspecialchars($fileinfo['file']) . '"';
					$parArray['codebase'] = 'codebase="' . htmlspecialchars($fileinfo['path']) . '"';
					$content = '<applet ' . implode(' ', $parArray) . '></applet>';
				} else {
					$content = '<embed ' . implode(' ', $parArray) . '></embed>';
				}
			}
		}
		if (isset($conf['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}
		return $content;
	}

}


?>