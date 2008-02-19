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
	public function __construct(TYPO3backend &$backendReference) {
		$this->backendReference = $backendReference;

		list($this->openDocs,)  = $GLOBALS['BE_USER']->getModuleData('alt_doc.php','ses');
		$this->recentDocs       = $GLOBALS['BE_USER']->getModuleData('opendocs::recent');
	}

	/**
	 * checks whether the user has access to this toolbar item
	 *
	 * @return  boolean  true if user has access, false if not
	 */
	public function checkAccess() {
			// FIXME - needs proper access check
		return true;
	}

	/**
	 * renders the toolbar item and the empty menu
	 *
	 * @return	void
	 */
	public function render() {
		$this->addJavascriptToBackend();
		$this->addCssToBackend();

			// return the toolbar item and an empty UL
		$output  = '<a href="#" class="toolbar-item">';
		$output .= '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], t3lib_extMgm::extRelPath($this->EXTKEY).'opendocs.png', 'width="23" height="14"').' title="'.$GLOBALS['LANG']->getLL('toolbaritem',1).'" alt="" />';
		$output .= '</a>';
		$output .= '<ul class="toolbar-item-menu" style="display: none;"></ul>';

		return $output;
	}


	/**
	 * returns the opened documents list as an array
	 *
	 * @return	array	all open documents as list-items
	 */
	public function getOpenDocuments() {
		$docs = array();

			// Traverse the list of open documents:
		if (is_array($this->openDocs)) {
			foreach($this->openDocs as $md5k => $lnk) {
				$docs[] = '<li><a target="content" href="'.htmlspecialchars('alt_doc.php?'.$lnk[2]).'">'.htmlspecialchars(strip_tags(t3lib_div::htmlspecialchars_decode($lnk[0]))).'</a></li>';
			}
		}
		return $docs;
	}


	/**
	 * returns the recent documents list as an array
	 *
	 * @return	array	all recent documents as list-items
	 */
	public function getRecentDocuments() {
		$docs = array();

		if (is_array($this->recentDocs)) {
			$docs[] = '<li class="menu-item-div">'.$GLOBALS['LANG']->getLL('recent_docs',1).'</li>';

				// Traverse the list of open documents:
			foreach($this->recentDocs as $md5k => $lnk) {
				$docs[] = '<li><a target="content" href="'.htmlspecialchars('alt_doc.php?'.$lnk[2]).'">'.htmlspecialchars(strip_tags(t3lib_div::htmlspecialchars_decode($lnk[0]))).'</a></li>';
			}
		}
		return $docs;
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




	/**
	 * returns the opened documents list for the AJAX call formatted as HTML list
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function renderBackendMenuContents($params, &$ajaxObj) {
		$itms = $this->getOpenDocuments();
		$itmsRecent = $this->getRecentDocuments();


			// if there are "recent documents" in the list, add them
		if (count($itmsRecent)) {
			$itms = array_merge($itms, $itmsRecent);
		}


		if (count($itms)) {
			$ajaxObj->addContent('opendocs', implode('', $itms));
		} else {
			$ajaxObj->addContent('opendocs', '<li>'.$GLOBALS['LANG']->getLL('no_docs',1).'</li>');
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['EXT:opendocs/class.tx_opendocs.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['EXT:opendocs/class.tx_opendocs.php']);
}
?>
