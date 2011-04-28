<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Steffen Kamper <info@sk-typo3.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib . 'class.tslib_pibase.php');


/**
 * Plugin 'RSS from Content' for the 'contentrss' extension.
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 * @package	TYPO3
 * @subpackage	tx_contentrss
 */
class tx_contentrss_pi1 extends tslib_pibase {
	public $prefixId      = 'tx_contentrss_pi1';		// Same as class name
	public $scriptRelPath = 'pi1/class.tx_contentrss_pi1.php';	// Path to this script relative to the extension dir.
	public $extKey        = 'contentrss';	// The extension key.
	public $pi_checkCHash = TRUE;
	public $versioningEnabled = false;
	public $sys_language_mode;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content, $conf)	{
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$type = $GLOBALS['TSFE']->type;

		// type must be myType
		$myType = intval($this->conf['typeNum']);
		if ($type == 0 || $type != $myType) {
			// wrong type - print message with correct link
			return sprintf($this->pi_getLL('wrong_pagetype'), $this->cObj->typoLink($this->pi_getLL('this_link'), array('parameter' => $GLOBALS['TSFE']->id . ',' . $myType)));
		} else {
			// validate settings
			$id = $GLOBALS['TSFE']->id;
			// fetch tt_content record
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'pages,recursive',
				'tt_content',
				'list_type=\'contentrss_pi1\' AND pid=' . $id . $this->cObj->enableFields('tt_content')
			);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				return $this->pi_getLL('no_pages_selected');
			}
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$this->conf['pages'] = $row['pages'];
			$this->conf['recursive'] = $row['recursive'];
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}

		// init vars
		$pidList = $this->pi_getPidList($this->conf['pages'], $this->conf['recursive']);

		// get language and version infos
		$this->sys_language_mode = $this->conf['sys_language_mode'] ? $this->conf['sys_language_mode'] : $GLOBALS['TSFE']->sys_language_mode;
		if (t3lib_extMgm::isLoaded('version')) {
			$this->versioningEnabled = TRUE;
		}

		// fetch Content elements
		$where = 'pid IN (' . $pidList .') AND tx_contentrss_excluderss=0';
		$orderBy = $this->conf['orderField'];
		$limit = $this->conf['limit'] ? intval($this->conf['limit']) : 10;

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tt_content',
			$where . $this->cObj->enableFields('tt_content'),
			'',
			$orderBy,
			$limit
		);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0) {
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			return $this->pi_getLL('no_content_found');
		}

		$contentRows = array();
		$allowedTypes = t3lib_div::trimExplode(',', $this->conf['allowedTypes'], TRUE);
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			if ($row['CType'] != 'list' && in_array($row['CType'], $allowedTypes)) {  
				// we have normal content elements
				// get language overlay
				if ($GLOBALS['TSFE']->sys_language_content) {
					$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tt_content', $row, $GLOBALS['TSFE']->sys_language_content, $this->sys_language_mode == 'strict' ? 'hideNonTranslated' : '');
				}
				if ($this->versioningEnabled) {
					// get workspaces Overlay
					$GLOBALS['TSFE']->sys_page->versionOL('tt_content', $row);
					// fix pid for record from workspace
					$GLOBALS['TSFE']->sys_page->fixVersioningPid('tt_content', $row);
				}
				$contentRows[] = $row;
			} elseif ($row['CType'] == 'list' && in_array($row['CType'], $allowedTypes) && $row['list_type']) {
				// it's a plugin, look for registered function
				$listType = $row['listType'];
				if ($GLOBALS['extConf'][$this->extKey][$row['list_type']]['contentPreview']) {
					// call registered function
					$row['bodytext'] = t3lib_div::callUserFunction($GLOBALS['extConf'][$this->extKey]['contentRSS'][$row['list_type']]['contentPreview'], $row);
					$contentRows[] = $row;
				}
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $this->compileRows($contentRows);
	}

	/**
	 * compile the rows and fill template
	 *
	 * @param	array		$rows: contain all rows for output
	 * @return	complete XML output
	 */
	protected function compileRows($rows) {

		$template = $this->conf['rssTemplate'];
		$rowSubpart = $this->cObj->getSubpart($template, '###CONTENTROWS###');
		$rowHeader = $this->cObj->getSubpart($template, '###HEADER###');

		$rowContent = '';
		foreach($rows as $row) {
			// Allow HTML Output eg for atom
			$content = $this->conf['stripHTML'] ? strip_tags($this->pi_RTEcssText($row['bodytext'])) : $this->pi_RTEcssText($row['bodytext']);
			$markerArray = array(
				'###TITLE###' => $row['header'] ? $row['header'] : '[no title]',
				'###LINK###' => $this->conf['siteLink'] . $this->cObj->typoLink_URL(array('parameter' => $row['pid'], 'section' => 'c' . $row['uid'])),
				'###CONTENT###' => t3lib_div::fixed_lgd($content, $this->conf['contentLength']),
				'###AUTHOR###' => $row['author'],
				'###DATE###' => date($this->conf['dateFormat'], $row[$this->conf['orderField']])
			);
			$rowContent .= $this->cObj->substituteMarkerArrayCached($rowSubpart, $markerArray, array(), array());
		}
		$subpartArray['###CONTENTROWS###'] = $rowContent;

		$markerArray = array(
			'###XML_DECLARATION###' => $this->conf['xmlDeclaration'],
			'###SITE_TITLE###' => $this->conf['siteTitle'],
			'###SITE_LINK###' => $this->conf['siteLink'],
			'###SITE_DESCRIPTION###' => $this->conf['siteDescription'],
			'###SITE_LANG###' => $this->conf['siteLang'],
			'###IMG###' => $this->conf['siteImage'],
			'###IMG_W###' => $this->conf['siteImageW'],
			'###IMG_H###' => $this->conf['siteImageH'],
			'###COPYRIGHT###' => $this->conf['rssCopyright'],
			'###WEBMASTER###' => $this->conf['rssWebmaster'],
			'###MANAGINGEDITOR###' => $this->conf['rssManagingEditor'],
			'###LASTBUILD###' => date($this->conf['dateFormat'])

		);
		return $this->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray, array());
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contentrss/pi1/class.tx_contentrss_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contentrss/pi1/class.tx_contentrss_pi1.php']);
}

?>