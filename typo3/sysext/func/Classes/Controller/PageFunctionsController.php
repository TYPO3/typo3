<?php
namespace TYPO3\CMS\Func\Controller;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * Script Class for the Web > Functions module
 * This class creates the framework to which other extensions can connect their sub-modules
 */
class PageFunctionsController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * @var array
	 * @internal
	 */
	public $pageinfo;

	/**
	 * Document Template Object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * The name of the module
	 *
	 * @var string
	 */
	protected $moduleName = 'web_func';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_web_func.xlf');
		$this->MCONF = array(
			'name' => $this->moduleName,
		);
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
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
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
			$title = $this->getLanguageService()->getLL('title');
			$message = $this->getLanguageService()->getLL('clickAPage_content');
			$view = GeneralUtility::makeInstance(StandaloneView::class);
			$view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:func/Resources/Private/Templates/InfoBox.html'));
			$view->assignMultiple(array(
				'title' => $title,
				'message' => $message,
				'state' => InfoboxViewHelper::STATE_INFO
			));
			$this->content = $view->render();

			// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers['CSH'] = $docHeaderButtons['csh'];
			$markers['CONTENT'] = $this->content;
		}
		// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		// Renders the module page
		$this->content = $this->doc->render($this->getLanguageService()->getLL('title'), $this->content);
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
		$buttons['csh'] = BackendUtility::cshItem('_MOD_web_func', '');
		if ($this->id && is_array($this->pageinfo)) {
			// View page
			$buttons['view'] = '<a href="#" '
				. 'onclick="' . htmlspecialchars(BackendUtility::viewOnClick($this->pageinfo['uid'], $GLOBALS['BACK_PATH'], BackendUtility::BEgetRootLine($this->pageinfo['uid']))) . '" '
				. 'title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) . '">'
				. \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-view') . '</a>';
			// Shortcut
			if ($this->getBackendUser()->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->moduleName);
			}
		}
		return $buttons;
	}

	/**
	 * Returns LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}


}
