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
 * Contains CTABLE class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class ContentTableContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, CTABLE
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		$controlTable = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\TableRenderer');
		$tableParams = isset($conf['tableParams.']) ? $this->cObj->stdWrap($conf['tableParams'], $conf['tableParams.']) : $conf['tableParams'];
		if ($tableParams) {
			$controlTable->tableParams = $tableParams;
		}
		// loads the pagecontent
		$conf['cWidth'] = isset($conf['cWidth.']) ? $this->cObj->stdWrap($conf['cWidth'], $conf['cWidth.']) : $conf['cWidth'];
		$controlTable->contentW = $conf['cWidth'];
		// loads the menues if any
		if (is_array($conf['c.'])) {
			$controlTable->content = $this->cObj->cObjGet($conf['c.'], 'c.');
			$contentTDParams = isset($conf['c.']['TDParams.']) ? $this->cObj->stdWrap($conf['c.']['TDParams'], $conf['c.']['TDParams.']) : $conf['c.']['TDParams'];
			$controlTable->contentTDparams = isset($contentTDParams) ? $contentTDParams : 'valign="top"';
		}
		if (is_array($conf['lm.'])) {
			$controlTable->lm = $this->cObj->cObjGet($conf['lm.'], 'lm.');
			$lmTDParams = isset($conf['lm.']['TDParams.']) ? $this->cObj->stdWrap($conf['lm.']['TDParams'], $conf['lm.']['TDParams.']) : $conf['lm.']['TDParams'];
			$controlTable->lmTDparams = isset($lmTDParams) ? $lmTDParams : 'valign="top"';
		}
		if (is_array($conf['tm.'])) {
			$controlTable->tm = $this->cObj->cObjGet($conf['tm.'], 'tm.');
			$tmTDParams = isset($conf['tm.']['TDParams.']) ? $this->cObj->stdWrap($conf['tm.']['TDParams'], $conf['tm.']['TDParams.']) : $conf['tm.']['TDParams'];
			$controlTable->tmTDparams = isset($tmTDParams) ? $tmTDParams : 'valign="top"';
		}
		if (is_array($conf['rm.'])) {
			$controlTable->rm = $this->cObj->cObjGet($conf['rm.'], 'rm.');
			$rmTDParams = isset($conf['rm.']['TDParams.']) ? $this->cObj->stdWrap($conf['rm.']['TDParams'], $conf['rm.']['TDParams.']) : $conf['rm.']['TDParams'];
			$controlTable->rmTDparams = isset($rmTDParams) ? $rmTDParams : 'valign="top"';
		}
		if (is_array($conf['bm.'])) {
			$controlTable->bm = $this->cObj->cObjGet($conf['bm.'], 'bm.');
			$bmTDParams = isset($conf['bm.']['TDParams.']) ? $this->cObj->stdWrap($conf['bm.']['TDParams'], $conf['bm.']['TDParams.']) : $conf['bm.']['TDParams'];
			$controlTable->bmTDparams = isset($bmTDParams) ? $bmTDParams : 'valign="top"';
		}
		$conf['offset'] = isset($conf['offset.']) ? $this->cObj->stdWrap($conf['offset'], $conf['offset.']) : $conf['offset'];
		$conf['cMargins'] = isset($conf['cMargins.']) ? $this->cObj->stdWrap($conf['cMargins'], $conf['cMargins.']) : $conf['cMargins'];
		$theValue = $controlTable->start($conf['offset'], $conf['cMargins']);
		if (isset($conf['stdWrap.'])) {
			$theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
		}
		return $theValue;
	}

}


?>