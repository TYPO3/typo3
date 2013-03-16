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
 * Contains CONTENT class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class ContentContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, CONTENT
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		$theValue = '';
		$originalRec = $GLOBALS['TSFE']->currentRecord;
		// If the currentRecord is set, we register, that this record has invoked this function.
		// It's should not be allowed to do this again then!!
		if ($originalRec) {
			$GLOBALS['TSFE']->recordRegister[$originalRec]++;
		}
		$conf['table'] = isset($conf['table.']) ? trim($this->cObj->stdWrap($conf['table'], $conf['table.'])) : trim($conf['table']);
		$tablePrefix = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('_', $conf['table'], TRUE);
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('pages,tt,fe,tx,ttx,user,static', $tablePrefix[0])) {
			$renderObjName = $conf['renderObj'] ? $conf['renderObj'] : '<' . $conf['table'];
			$renderObjKey = $conf['renderObj'] ? 'renderObj' : '';
			$renderObjConf = $conf['renderObj.'];
			$slide = isset($conf['slide.']) ? intval($this->cObj->stdWrap($conf['slide'], $conf['slide.'])) : intval($conf['slide']);
			if (!$slide) {
				$slide = 0;
			}
			$slideCollect = isset($conf['slide.']['collect.']) ? intval($this->cObj->stdWrap($conf['slide.']['collect'], $conf['slide.']['collect.'])) : intval($conf['slide.']['collect']);
			if (!$slideCollect) {
				$slideCollect = 0;
			}
			$slideCollectReverse = isset($conf['slide.']['collectReverse.']) ? intval($this->cObj->stdWrap($conf['slide.']['collectReverse'], $conf['slide.']['collectReverse.'])) : intval($conf['slide.']['collectReverse']);
			$slideCollectReverse = $slideCollectReverse ? TRUE : FALSE;
			$slideCollectFuzzy = isset($conf['slide.']['collectFuzzy.']) ? intval($this->cObj->stdWrap($conf['slide.']['collectFuzzy'], $conf['slide.']['collectFuzzy.'])) : intval($conf['slide.']['collectFuzzy']);
			if ($slideCollectFuzzy) {
				$slideCollectFuzzy = TRUE;
			} else {
				$slideCollectFuzzy = FALSE;
			}
			if (!$slideCollect) {
				$slideCollectFuzzy = TRUE;
			}
			$again = FALSE;
			do {
				$res = $this->cObj->exec_getQuery($conf['table'], $conf['select.']);
				if ($error = $GLOBALS['TYPO3_DB']->sql_error()) {
					$GLOBALS['TT']->setTSlogMessage($error, 3);
				} else {
					$this->cObj->currentRecordTotal = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
					$GLOBALS['TT']->setTSlogMessage('NUMROWS: ' . $GLOBALS['TYPO3_DB']->sql_num_rows($res));
					/** @var $cObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
					$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
					$cObj->setParent($this->cObj->data, $this->cObj->currentRecord);
					$this->cObj->currentRecordNumber = 0;
					$cobjValue = '';
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						// Versioning preview:
						$GLOBALS['TSFE']->sys_page->versionOL($conf['table'], $row, TRUE);
						// Language overlay:
						if (is_array($row) && $GLOBALS['TSFE']->sys_language_contentOL) {
							if ($conf['table'] == 'pages') {
								$row = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
							} else {
								$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay($conf['table'], $row, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
							}
						}
						// Might be unset in the sys_language_contentOL
						if (is_array($row)) {
							// Call hook for possible manipulation of database row for cObj->data
							if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content_content.php']['modifyDBRow'])) {
								foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content_content.php']['modifyDBRow'] as $_classRef) {
									$_procObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
									$_procObj->modifyDBRow($row, $conf['table']);
								}
							}
							\TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow($row, $conf['table']);
							if (!$GLOBALS['TSFE']->recordRegister[($conf['table'] . ':' . $row['uid'])]) {
								$this->cObj->currentRecordNumber++;
								$cObj->parentRecordNumber = $this->cObj->currentRecordNumber;
								$GLOBALS['TSFE']->currentRecord = $conf['table'] . ':' . $row['uid'];
								$this->cObj->lastChanged($row['tstamp']);
								$cObj->start($row, $conf['table']);
								$tmpValue = $cObj->cObjGetSingle($renderObjName, $renderObjConf, $renderObjKey);
								$cobjValue .= $tmpValue;
							}
						}
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
				if ($slideCollectReverse) {
					$theValue = $cobjValue . $theValue;
				} else {
					$theValue .= $cobjValue;
				}
				if ($slideCollect > 0) {
					$slideCollect--;
				}
				if ($slide) {
					if ($slide > 0) {
						$slide--;
					}
					$conf['select.']['pidInList'] = $this->cObj->getSlidePids($conf['select.']['pidInList'], $conf['select.']['pidInList.']);
					if (isset($conf['select.']['pidInList.'])) {
						unset($conf['select.']['pidInList.']);
					}
					$again = strlen($conf['select.']['pidInList']) ? TRUE : FALSE;
				}
			} while ($again && ($slide && !strlen($tmpValue) && $slideCollectFuzzy || $slide && $slideCollect));
		}
		$wrap = isset($conf['wrap.']) ? $this->cObj->stdWrap($conf['wrap'], $conf['wrap.']) : $conf['wrap'];
		if ($wrap) {
			$theValue = $this->cObj->wrap($theValue, $wrap);
		}
		if (isset($conf['stdWrap.'])) {
			$theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
		}
		// Restore
		$GLOBALS['TSFE']->currentRecord = $originalRec;
		return $theValue;
	}

}


?>