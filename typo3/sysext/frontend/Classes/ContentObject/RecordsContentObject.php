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
 * Contains RECORDS class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class RecordsContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, RECORDS
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
		$tables = isset($conf['tables.']) ? $this->cObj->stdWrap($conf['tables'], $conf['tables.']) : $conf['tables'];
		$source = isset($conf['source.']) ? $this->cObj->stdWrap($conf['source'], $conf['source.']) : $conf['source'];
		if ($tables && $source) {
			$allowedTables = $tables;
			if (is_array($conf['conf.'])) {
				foreach ($conf['conf.'] as $k => $v) {
					if (substr($k, -1) != '.') {
						$allowedTables .= ',' . $k;
					}
				}
			}
			/** @var \TYPO3\CMS\Core\Database\RelationHandler $loadDB*/
			$loadDB = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
			$loadDB->setFetchAllFields(TRUE);
			$loadDB->start($source, $allowedTables);
			foreach ($loadDB->tableArray as $table => $v) {
				if (is_array($GLOBALS['TCA'][$table])) {
					$loadDB->additionalWhere[$table] = $this->cObj->enableFields($table);
				}
			}
			$loadDB->getFromDB();
			reset($loadDB->itemArray);
			$data = $loadDB->results;
			$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
			$cObj->setParent($this->cObj->data, $this->cObj->currentRecord);
			$this->cObj->currentRecordNumber = 0;
			$this->cObj->currentRecordTotal = count($loadDB->itemArray);
			foreach ($loadDB->itemArray as $val) {
				$row = $data[$val['table']][$val['id']];
				// Versioning preview:
				$GLOBALS['TSFE']->sys_page->versionOL($val['table'], $row);
				// Language overlay:
				if (is_array($row) && $GLOBALS['TSFE']->sys_language_contentOL) {
					if ($val['table'] === 'pages') {
						$row = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
					} else {
						$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay($val['table'], $row, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
					}
				}
				// Might be unset in the content overlay things...
				if (is_array($row)) {
					$dontCheckPid = isset($conf['dontCheckPid.']) ? $this->cObj->stdWrap($conf['dontCheckPid'], $conf['dontCheckPid.']) : $conf['dontCheckPid'];
					if (!$dontCheckPid) {
						$row = $this->cObj->checkPid($row['pid']) ? $row : '';
					}
					if ($row && !$GLOBALS['TSFE']->recordRegister[($val['table'] . ':' . $val['id'])]) {
						$renderObjName = $conf['conf.'][$val['table']] ? $conf['conf.'][$val['table']] : '<' . $val['table'];
						$renderObjKey = $conf['conf.'][$val['table']] ? 'conf.' . $val['table'] : '';
						$renderObjConf = $conf['conf.'][$val['table'] . '.'];
						$this->cObj->currentRecordNumber++;
						$cObj->parentRecordNumber = $this->cObj->currentRecordNumber;
						$GLOBALS['TSFE']->currentRecord = $val['table'] . ':' . $val['id'];
						$this->cObj->lastChanged($row['tstamp']);
						$cObj->start($row, $val['table']);
						$tmpValue = $cObj->cObjGetSingle($renderObjName, $renderObjConf, $renderObjKey);
						$theValue .= $tmpValue;
					}
				}
			}
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
