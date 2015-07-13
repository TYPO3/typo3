<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Contains CONTENT class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class ContentContentObject extends AbstractContentObject {

	/**
	 * Rendering the cObject, CONTENT
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		if (!empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
			return '';
		}

		$frontendController = $this->getFrontendController();
		$theValue = '';
		$originalRec = $frontendController->currentRecord;
		// If the currentRecord is set, we register, that this record has invoked this function.
		// It's should not be allowed to do this again then!!
		if ($originalRec) {
			++$frontendController->recordRegister[$originalRec];
		}
		$conf['table'] = isset($conf['table.']) ? trim($this->cObj->stdWrap($conf['table'], $conf['table.'])) : trim($conf['table']);
		$renderObjName = $conf['renderObj'] ?: '<' . $conf['table'];
		$renderObjKey = $conf['renderObj'] ? 'renderObj' : '';
		$renderObjConf = $conf['renderObj.'];
		$slide = isset($conf['slide.']) ? (int)$this->cObj->stdWrap($conf['slide'], $conf['slide.']) : (int)$conf['slide'];
		if (!$slide) {
			$slide = 0;
		}
		$slideCollect = isset($conf['slide.']['collect.']) ? (int)$this->cObj->stdWrap($conf['slide.']['collect'], $conf['slide.']['collect.']) : (int)$conf['slide.']['collect'];
		if (!$slideCollect) {
			$slideCollect = 0;
		}
		$slideCollectReverse = isset($conf['slide.']['collectReverse.']) ? (int)$this->cObj->stdWrap($conf['slide.']['collectReverse'], $conf['slide.']['collectReverse.']) : (int)$conf['slide.']['collectReverse'];
		$slideCollectReverse = (bool)$slideCollectReverse;
		$slideCollectFuzzy = isset($conf['slide.']['collectFuzzy.'])
			? (bool)$this->cObj->stdWrap($conf['slide.']['collectFuzzy'], $conf['slide.']['collectFuzzy.'])
			: (bool)$conf['slide.']['collectFuzzy'];
		if (!$slideCollect) {
			$slideCollectFuzzy = TRUE;
		}
		$again = FALSE;
		$tmpValue = '';
		$cobjValue = '';
		$databaseConnection = $this->getDatabaseConnection();
		do {
			$res = $this->cObj->exec_getQuery($conf['table'], $conf['select.']);
			if ($error = $databaseConnection->sql_error()) {
				$this->getTimeTracker()->setTSlogMessage($error, 3);
			} else {
				$this->cObj->currentRecordTotal = $databaseConnection->sql_num_rows($res);
				$this->getTimeTracker()->setTSlogMessage('NUMROWS: ' . $databaseConnection->sql_num_rows($res));
				/** @var $cObj ContentObjectRenderer */
				$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
				$cObj->setParent($this->cObj->data, $this->cObj->currentRecord);
				$this->cObj->currentRecordNumber = 0;
				$cobjValue = '';
				while (($row = $databaseConnection->sql_fetch_assoc($res)) !== FALSE) {
					// Versioning preview:
					$frontendController->sys_page->versionOL($conf['table'], $row, TRUE);
					// Language overlay:
					if (is_array($row) && $frontendController->sys_language_contentOL) {
						if ($conf['table'] == 'pages') {
							$row = $frontendController->sys_page->getPageOverlay($row);
						} else {
							$row = $frontendController->sys_page->getRecordOverlay(
								$conf['table'],
								$row,
								$frontendController->sys_language_content,
								$frontendController->sys_language_contentOL
							);
						}
					}
					// Might be unset in the sys_language_contentOL
					if (is_array($row)) {
						// Call hook for possible manipulation of database row for cObj->data
						if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content_content.php']['modifyDBRow'])) {
							foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content_content.php']['modifyDBRow'] as $_classRef) {
								$_procObj = GeneralUtility::getUserObj($_classRef);
								$_procObj->modifyDBRow($row, $conf['table']);
							}
						}
						if (!$frontendController->recordRegister[($conf['table'] . ':' . $row['uid'])]) {
							$this->cObj->currentRecordNumber++;
							$cObj->parentRecordNumber = $this->cObj->currentRecordNumber;
							$frontendController->currentRecord = $conf['table'] . ':' . $row['uid'];
							$this->cObj->lastChanged($row['tstamp']);
							$cObj->start($row, $conf['table']);
							$tmpValue = $cObj->cObjGetSingle($renderObjName, $renderObjConf, $renderObjKey);
							$cobjValue .= $tmpValue;
						}
					}
				}
				$databaseConnection->sql_free_result($res);
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
				$again = (string)$conf['select.']['pidInList'] !== '';
			}
		} while ($again && $slide && ((string)$tmpValue === '' && $slideCollectFuzzy || $slideCollect));

		$wrap = isset($conf['wrap.']) ? $this->cObj->stdWrap($conf['wrap'], $conf['wrap.']) : $conf['wrap'];
		if ($wrap) {
			$theValue = $this->cObj->wrap($theValue, $wrap);
		}
		if (isset($conf['stdWrap.'])) {
			$theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
		}
		// Restore
		$frontendController->currentRecord = $originalRec;
		if ($originalRec) {
			--$frontendController->recordRegister[$originalRec];
		}
		return $theValue;
	}

	/**
	 * Returns the database connection
	 *
	 * @return DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Returns the frontend controller
	 *
	 * @return TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}

	/**
	 * Returns Time Tracker
	 *
	 * @return TimeTracker
	 */
	protected function getTimeTracker() {
		return $GLOBALS['TT'];
	}

}
