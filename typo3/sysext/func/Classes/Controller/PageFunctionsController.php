<?php
namespace TYPO3\CMS\Func\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Script Class for the Web > Functions module
 * This class creates the framework to which other extensions can connect their sub-modules
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class PageFunctionsController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * @Internal
	 * @todo Define visibility
	 */
	public $pageinfo;

	/**
	 * @todo Define visibility
	 */
	public $fileProcessor;

	/**
	 * Document Template Object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	/**
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_mod_web_func.xlf');
		$GLOBALS['BE_USER']->modAccess($GLOBALS['MCONF'], TRUE);
	}

	/**
	 * Initialize module header etc and call extObjContent function
	 *
	 * @return void
	 */
	public function main() {
		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo);
		// Template markers
		$markers = array(
			'CSH' => '',
			'FUNC_MENU' => '',
			'CONTENT' => ''
		);
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:func/Resources/Private/Templates/func.html');
		// Main
		if ($this->id && $access) {
			// JavaScript
			$this->doc->postCode = $this->doc->wrapScriptTags('if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';');
			// Setting up the context sensitive menu:
			$this->doc->getContextMenuCode();
			$this->doc->form = '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('web_func')) . '" method="post"><input type="hidden" name="id" value="' . htmlspecialchars($this->id) . '" />';
			$vContent = $this->doc->getVersionSelector($this->id, TRUE);
			if ($vContent) {
				$this->content .= $this->doc->section('', $vContent);
			}
			$this->extObjContent();
			// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers['CSH'] = $docHeaderButtons['csh'];
			$markers['FUNC_MENU'] = BackendUtility::getFuncMenu(
				$this->id,
				'SET[function]',
				$this->MOD_SETTINGS['function'],
				$this->MOD_MENU['function']
			);
			$markers['CONTENT'] = $this->content;
		} else {
			// If no access or if ID == zero
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$GLOBALS['LANG']->getLL('clickAPage_content'),
				$GLOBALS['LANG']->getLL('title'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::INFO
			);
			$this->content = $flashMessage->render();
			// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers['CSH'] = $docHeaderButtons['csh'];
			$markers['CONTENT'] = $this->content;
		}
		// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		// Renders the module page
		$this->content = $this->doc->render($GLOBALS['LANG']->getLL('title'), $this->content);
	}

	/**
	 * Print module content (from $this->content)
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'view' => '',
			'shortcut' => ''
		);
		// CSH
		$buttons['csh'] = BackendUtility::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH'], '', TRUE);
		if ($this->id && is_array($this->pageinfo)) {
			// View page
			$buttons['view'] = '<a href="#" '
				. 'onclick="' . htmlspecialchars(BackendUtility::viewOnClick($this->pageinfo['uid'], $GLOBALS['BACK_PATH'], BackendUtility::BEgetRootLine($this->pageinfo['uid']))) . '" '
				. 'title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) . '">'
				. \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-view') . '</a>';
			// Shortcut
			if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
			}
		}
		return $buttons;
	}

}
