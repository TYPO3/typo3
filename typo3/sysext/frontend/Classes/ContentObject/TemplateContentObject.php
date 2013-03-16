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
 * Contains TEMPLATE class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class TemplateContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, TEMPLATE
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 * @see substituteMarkerArrayCached()
	 */
	public function render($conf = array()) {
		$subparts = array();
		$marks = array();
		$wraps = array();
		$markerWrap = isset($conf['markerWrap.']) ? $this->cObj->stdWrap($conf['markerWrap'], $conf['markerWrap.']) : $conf['markerWrap'];
		if (!$markerWrap) {
			$markerWrap = '### | ###';
		}
		list($PRE, $POST) = explode('|', $markerWrap);
		$POST = trim($POST);
		$PRE = trim($PRE);
		// Getting the content
		$content = $this->cObj->cObjGetSingle($conf['template'], $conf['template.'], 'template');
		$workOnSubpart = isset($conf['workOnSubpart.']) ? $this->cObj->stdWrap($conf['workOnSubpart'], $conf['workOnSubpart.']) : $conf['workOnSubpart'];
		if ($workOnSubpart) {
			$content = $this->cObj->getSubpart($content, $PRE . $workOnSubpart . $POST);
		}
		// Fixing all relative paths found:
		if ($conf['relPathPrefix']) {
			$htmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
			$content = $htmlParser->prefixResourcePath($conf['relPathPrefix'], $content, $conf['relPathPrefix.']);
		}
		if ($content) {
			$nonCachedSubst = isset($conf['nonCachedSubst.']) ? $this->cObj->stdWrap($conf['nonCachedSubst'], $conf['nonCachedSubst.']) : $conf['nonCachedSubst'];
			// NON-CACHED:
			if ($nonCachedSubst) {
				// Getting marks
				if (is_array($conf['marks.'])) {
					foreach ($conf['marks.'] as $theKey => $theValue) {
						if (!strstr($theKey, '.')) {
							$content = str_replace($PRE . $theKey . $POST, $this->cObj->cObjGetSingle($theValue, $conf['marks.'][$theKey . '.'], 'marks.' . $theKey), $content);
						}
					}
				}
				// Getting subparts.
				if (is_array($conf['subparts.'])) {
					foreach ($conf['subparts.'] as $theKey => $theValue) {
						if (!strstr($theKey, '.')) {
							$subpart = $this->cObj->getSubpart($content, $PRE . $theKey . $POST);
							if ($subpart) {
								$this->cObj->setCurrentVal($subpart);
								$content = $this->cObj->substituteSubpart($content, $PRE . $theKey . $POST, $this->cObj->cObjGetSingle($theValue, $conf['subparts.'][$theKey . '.'], 'subparts.' . $theKey), TRUE);
							}
						}
					}
				}
				// Getting subpart wraps
				if (is_array($conf['wraps.'])) {
					foreach ($conf['wraps.'] as $theKey => $theValue) {
						if (!strstr($theKey, '.')) {
							$subpart = $this->cObj->getSubpart($content, $PRE . $theKey . $POST);
							if ($subpart) {
								$this->cObj->setCurrentVal($subpart);
								$content = $this->cObj->substituteSubpart($content, $PRE . $theKey . $POST, explode('|', $this->cObj->cObjGetSingle($theValue, $conf['wraps.'][$theKey . '.'], 'wraps.' . $theKey)), TRUE);
							}
						}
					}
				}
			} else {
				// CACHED
				// Getting subparts.
				if (is_array($conf['subparts.'])) {
					foreach ($conf['subparts.'] as $theKey => $theValue) {
						if (!strstr($theKey, '.')) {
							$subpart = $this->cObj->getSubpart($content, $PRE . $theKey . $POST);
							if ($subpart) {
								$GLOBALS['TSFE']->register['SUBPART_' . $theKey] = $subpart;
								$subparts[$theKey]['name'] = $theValue;
								$subparts[$theKey]['conf'] = $conf['subparts.'][$theKey . '.'];
							}
						}
					}
				}
				// Getting marks
				if (is_array($conf['marks.'])) {
					foreach ($conf['marks.'] as $theKey => $theValue) {
						if (!strstr($theKey, '.')) {
							$marks[$theKey]['name'] = $theValue;
							$marks[$theKey]['conf'] = $conf['marks.'][$theKey . '.'];
						}
					}
				}
				// Getting subpart wraps
				if (is_array($conf['wraps.'])) {
					foreach ($conf['wraps.'] as $theKey => $theValue) {
						if (!strstr($theKey, '.')) {
							$wraps[$theKey]['name'] = $theValue;
							$wraps[$theKey]['conf'] = $conf['wraps.'][$theKey . '.'];
						}
					}
				}
				// Getting subparts
				$subpartArray = array();
				foreach ($subparts as $theKey => $theValue) {
					// Set current with the content of the subpart...
					$this->cObj->data[$this->cObj->currentValKey] = $GLOBALS['TSFE']->register['SUBPART_' . $theKey];
					// Get subpart cObject and substitute it!
					$subpartArray[$PRE . $theKey . $POST] = $this->cObj->cObjGetSingle($theValue['name'], $theValue['conf'], 'subparts.' . $theKey);
				}
				// Reset current to empty
				$this->cObj->data[$this->cObj->currentValKey] = '';
				// Getting marks
				$markerArray = array();
				foreach ($marks as $theKey => $theValue) {
					$markerArray[$PRE . $theKey . $POST] = $this->cObj->cObjGetSingle($theValue['name'], $theValue['conf'], 'marks.' . $theKey);
				}
				// Getting wraps
				$subpartWraps = array();
				foreach ($wraps as $theKey => $theValue) {
					$subpartWraps[$PRE . $theKey . $POST] = explode('|', $this->cObj->cObjGetSingle($theValue['name'], $theValue['conf'], 'wraps.' . $theKey));
				}
				// Substitution
				$substMarksSeparately = isset($conf['substMarksSeparately.']) ? $this->cObj->stdWrap($conf['substMarksSeparately'], $conf['substMarksSeparately.']) : $conf['substMarksSeparately'];
				if ($substMarksSeparately) {
					$content = $this->cObj->substituteMarkerArrayCached($content, array(), $subpartArray, $subpartWraps);
					$content = $this->cObj->substituteMarkerArray($content, $markerArray);
				} else {
					$content = $this->cObj->substituteMarkerArrayCached($content, $markerArray, $subpartArray, $subpartWraps);
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