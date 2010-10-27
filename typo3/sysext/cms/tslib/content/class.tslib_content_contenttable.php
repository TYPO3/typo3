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
 * Contains CTABLE class object.
 *
 * $Id: class.tslib_content.php 7905 2010-06-13 14:42:33Z ohader $
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_ContentTable extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, CTABLE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 */
	public function render($conf = array()) {
		$controlTable = t3lib_div::makeInstance('tslib_controlTable');
		if ($conf['tableParams']) {
			$controlTable->tableParams = $conf['tableParams'];
		}
			// loads the pagecontent
		$controlTable->contentW = $conf['cWidth'];
		// loads the menues if any
		if (is_array($conf['c.'])) {
			$controlTable->content = $this->cObj->cObjGet($conf['c.'], 'c.');
			$controlTable->contentTDparams = isset($conf['c.']['TDParams']) ? $conf['c.']['TDParams'] : 'valign="top"';
		}
		if (is_array($conf['lm.'])) {
			$controlTable->lm = $this->cObj->cObjGet($conf['lm.'], 'lm.');
			$controlTable->lmTDparams = isset($conf['lm.']['TDParams']) ? $conf['lm.']['TDParams'] : 'valign="top"';
		}
		if (is_array($conf['tm.'])) {
			$controlTable->tm = $this->cObj->cObjGet($conf['tm.'], 'tm.');
			$controlTable->tmTDparams = isset($conf['tm.']['TDParams']) ? $conf['tm.']['TDParams'] : 'valign="top"';
		}
		if (is_array($conf['rm.'])) {
			$controlTable->rm = $this->cObj->cObjGet($conf['rm.'], 'rm.');
			$controlTable->rmTDparams = isset($conf['rm.']['TDParams']) ? $conf['rm.']['TDParams'] : 'valign="top"';
		}
		if (is_array($conf['bm.'])) {
			$controlTable->bm = $this->cObj->cObjGet($conf['bm.'], 'bm.');
			$controlTable->bmTDparams = isset($conf['bm.']['TDParams']) ? $conf['bm.']['TDParams'] : 'valign="top"';
		}
		return $controlTable->start($conf['offset'], $conf['cMargins']);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_contenttable.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_contenttable.php']);
}

?>
