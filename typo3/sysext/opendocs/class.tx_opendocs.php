<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Benjamin Mack <mack@xnos.org>
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

	// load the language file
$GLOBALS['LANG']->includeLLFile('EXT:opendocs/locallang_opendocs.xml');

require_once(PATH_typo3.'interfaces/interface.backend_toolbaritem.php');


/**
 * Adding a list of all open documents of a user to the backend.php
 *
 * @author	Benjamin Mack <mack@xnos.org>
 * @package	TYPO3
 * @subpackage	opendocs
 */
class tx_opendocs implements backend_toolbarItem {

	/**
	 * reference back to the backend object
	 *
	 * @var	TYPO3backend
	 */
	private $backendReference;
	private $openDocs;
	private $recentDocs;
	private $EXTKEY = 'opendocs';


	/**
	 * constructor, loads the documents from the user control
	 *
	 * @param	TYPO3backend	TYPO3 backend object reference
	 */
	public function __construct(TYPO3backend &$backendReference = null) {
		$this->backendReference = $backendReference;
		$this->loadDocsFromUserSession();
	}


	/**
	 * checks whether the user has access to this toolbar item
	 *
	 * @return  boolean  true if user has access, false if not
	 */
	public function checkAccess() {
		$conf = $GLOBALS['BE_USER']->getTSConfig('backendToolbarItem.tx_opendocs.disabled');
		return ($conf['value'] == 1 ? false : true);
	}


	/**
	 * loads the opened and recently opened documents from the user 
	 *
	 * @return  void
	 */
	public function loadDocsFromUserSession() {
		list($this->openDocs,)  = $GLOBALS['BE_USER']->getModuleData('alt_doc.php','ses');
		$this->recentDocs       = $GLOBALS['BE_USER']->getModuleData('opendocs::recent');
	}


	/**
	 * renders the toolbar item and the empty menu
	 *
	 * @return	void
	 */
	public function render() {
		$this->addJavascriptToBackend();
		$this->addCssToBackend();
		$numDocs = count($this->openDocs);

			// return the toolbar item and an empty UL
		$output  = '<a href="#" class="toolbar-item">';
		$output .= '<span id="tx-opendocs-num">'.($numDocs > 0 ? $numDocs : '').'</span>';
		$output .= '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], t3lib_extMgm::extRelPath($this->EXTKEY).'opendocs.png', 'width="23" height="14"').' title="'.$GLOBALS['LANG']->getLL('toolbaritem',1).'" alt="" />';
		$output .= '</a>';
		$output .= '<div class="toolbar-item-menu" style="display: none;"></div>';

		return $output;
	}


	/**
	 * returns the recent documents list as an array
	 *
	 * @return	array	all recent documents as list-items
	 */
	public function renderMenuEntry($itm, $md5sum, $isRecentDoc = false) {
		$table = $itm[3]['table'];
		$uid   = $itm[3]['uid'];
		$rec   = t3lib_BEfunc::getRecordWSOL($table, $uid);
		$label = htmlspecialchars(strip_tags(t3lib_div::htmlspecialchars_decode($itm[0])));
		$icon  = '<img src="'.t3lib_iconWorks::getIcon($table, $rec).'" alt="'.$label.'" />';
		$link  = $GLOBALS['BACK_PATH'].'alt_doc.php?'.$itm[2];

		if ($isRecentDoc) {
			$entry = '
				<tr id="opendocs-'.$table.'-'.$uid.'" class="recentdoc">
					<td class="opendocs-icon">'.$icon.'</td>
					<td class="opendocs-label" colspan="2" id="opendocs-label-'.$table.'-'.$uid.'"><a href="'.$link.'" target="content" onclick="TYPO3BackendOpenDocs.hideMenu();">'.$label.'</a></td>
				</tr>';
		} else {
			$closeIcon = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/closedok.gif', 'width="16" height="16"').' title="Close Document" alt="" />';
			$entry = '
				<tr id="opendocs-'.$table.'-'.$uid.'" class="opendoc">
					<td class="opendocs-icon">'.$icon.'</td>
					<td class="opendocs-label" id="opendocs-label-'.$table.'-'.$uid.'"><a href="'.$link.'" target="content" onclick="TYPO3BackendOpenDocs.hideMenu();">'.$label.'</a></td>
					<td class="opendocs-close" onclick="return TYPO3BackendOpenDocs.closeDocument(\''.$md5sum.'\');">'.$closeIcon.'</td>
				</tr>';
		}
		return $entry;
	}



	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return ' id="open-documents-menu"';
	}


	/**
	 * adds the neccessary javascript to the backend
	 *
	 * @return	void
	 */
	private function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile(t3lib_extMgm::extRelPath($this->EXTKEY).'opendocs.js');
	}


	/**
	 * adds the neccessary CSS to the backend
	 *
	 * @return	void
	 */
	private function addCssToBackend() {
		$this->backendReference->addCssFile('opendocs', t3lib_extMgm::extRelPath($this->EXTKEY).'opendocs.css');
	}


	/*******************
	 ***    HOOKS    ***
	 *******************/

	/**
	 * called as a hook in t3lib_BEfunc::setUpdateSignal, calls a JS function to change
	 * the number of opened documents
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function updateNumberOfOpenDocsHook(&$params, &$ref) {
		$params['JScode'] = '
			if (top && top.TYPO3BackendOpenDocs) {
				top.TYPO3BackendOpenDocs.updateNumberOfDocs('.count($this->openDocs).', false);
			}
		';
	}



	/******************
	 *** AJAX CALLS ***
	 ******************/

	/**
	 * returns the opened documents list for the AJAX call formatted as HTML list
	 *
	 * @param	array		array full of additional params, not used yet
	 * @param	TYPO3AJAX	Ajax request object
	 * @return	string		list item HTML attibutes
	 */
	public function renderBackendMenuContents($params, &$ajaxObj) {
		$itms = $this->openDocs;
		$itmsRecent = $this->recentDocs;
		$entries = array();

		if (count($itms)) {
			$entries[] = '<tr class="menu-item-div"><td colspan="3">'.$GLOBALS['LANG']->getLL('open_docs',1).'</td></tr>';
			foreach ($itms as $md5sum => $itm) {
				$entries[] = $this->renderMenuEntry($itm, $md5sum);
			}
		}



			// if there are "recent documents" in the list, add them
		if (count($itmsRecent)) {
			$entries[] = '<tr class="menu-item-div"><td colspan="3">'.$GLOBALS['LANG']->getLL('recent_docs',1).'</td></tr>';
			foreach ($itmsRecent as $md5sum => $itm) {
				$entries[] = $this->renderMenuEntry($itm, $md5sum, true);
			}
		}

		if (count($entries)) {
			$content = '<table class="opendocs-list">'.implode('', $entries).'</table>';
			$ajaxObj->addContent('opendocs', $content);
		} else {
			$ajaxObj->addContent('opendocs', '<div id="opendocs-nodocs">'.$GLOBALS['LANG']->getLL('no_docs',1).'</div>');
		}
	}



	/**
	 * closes a document in the session and 
	 * @param	array		array full of additional params, not used yet
	 * @param	TYPO3AJAX	Ajax request object
	 * @return	string		list item HTML attibutes
	 */
	public function closeDocument($params, &$ajaxObj) {
		$md5sum = t3lib_div::_GP('md5sum');
		if ($md5sum && isset($this->openDocs[$md5sum])) {

				// add the closing document to the recent documents
			$this->recentDocs = array_merge(array($md5sum => $this->openDocs[$md5sum]), $this->recentDocs);
			if (count($this->recentDocs) > 8) {
				$this->recentDocs = array_slice($this->recentDocs, 0, 8);
			}

				// remove it from the list of the open documents
			unset($this->openDocs[$md5sum]);

				// store it again
			list(,$docDat) = $GLOBALS['BE_USER']->getModuleData('alt_doc.php','ses');
			$GLOBALS['BE_USER']->pushModuleData('alt_doc.php', array($this->openDocs, $docDat));
			$GLOBALS['BE_USER']->pushModuleData('opendocs::recent', $this->recentDocs);
		}
		$this->renderBackendMenuContents($params, $ajaxObj);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['EXT:opendocs/class.tx_opendocs.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['EXT:opendocs/class.tx_opendocs.php']);
}
?>
