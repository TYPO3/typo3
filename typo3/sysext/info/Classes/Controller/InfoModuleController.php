<?php
namespace TYPO3\CMS\Info\Controller;

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

use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for the Web > Info module
 * This class creates the framework to which other extensions can connect their sub-modules
 */
class InfoModuleController extends BaseScriptClass {

	/**
	 * @var array
	 */
	public $pageinfo;

	/**
	 * Document Template Object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected $backendUser;

	/**
	 * @var \TYPO3\CMS\Lang\LanguageService
	 */
	protected $languageService;

	/**
	 * The name of the module
	 *
	 * @var string
	 */
	protected $moduleName = 'web_info';

	/**
	 * @var IconFactory
	 */
	protected $iconFactory;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		$this->languageService = $GLOBALS['LANG'];
		$this->languageService->includeLLFile('EXT:lang/locallang_mod_web_info.xlf');

		$this->backendUser = $GLOBALS['BE_USER'];

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
		$this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);

		// The page will show only if there is a valid page and if this page
		// may be viewed by the user
		$this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo);
		if ($this->id && $access || $this->backendUser->user['admin'] && !$this->id) {
			if ($this->backendUser->user['admin'] && !$this->id) {
				$this->pageinfo = array('title' => '[root-level]', 'uid' => 0, 'pid' => 0);
			}
			$this->doc->setModuleTemplate('EXT:info/Resources/Private/Templates/info.html');

			// JavaScript
			$this->doc->postCode = $this->doc->wrapScriptTags('if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';');
			// Setting up the context sensitive menu:
			$this->doc->getContextMenuCode();
			$this->doc->form = '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl($this->moduleName)) .
				'" method="post" name="webinfoForm" class="form-inline form-inline-spaced">';
			$vContent = $this->doc->getVersionSelector($this->id, 1);
			if ($vContent) {
				$this->content .= $this->doc->section('', $vContent);
			}
			$this->extObjContent();
			// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers = array(
				'CSH' => $docHeaderButtons['csh'],
				'FUNC_MENU' => BackendUtility::getFuncMenu(
					$this->id,
					'SET[function]',
					$this->MOD_SETTINGS['function'],
					$this->MOD_MENU['function']
				),
				'CONTENT' => $this->content
			);
			// Build the <body> for the module
			$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		} else {
			// If no access or if ID == zero
			$this->content = $this->doc->header($this->languageService->getLL('title'));
		}
		// Renders the module page
		$this->content = $this->doc->render($this->languageService->getLL('title'), $this->content);
	}

	/**
	 * Print module content (from $this->content)
	 *
	 * @return void
	 */
	public function printContent() {
		$this->content = $this->doc->insertStylesAndJS($this->content);
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
		$buttons['csh'] = BackendUtility::cshItem('_MOD_web_info', '');
		// View page
		$buttons['view'] = '<a href="#" ' .
			'onclick="' . htmlspecialchars(
				BackendUtility::viewOnClick($this->pageinfo['uid'], '',
					BackendUtility::BEgetRootLine($this->pageinfo['uid']))
			) . '" ' .
			'title="' . $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) . '">' .
			$this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL) .
			'</a>';
		// Shortcut
		if ($this->backendUser->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon(
				'id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit',
				implode(',', array_keys($this->MOD_MENU)), $this->moduleName);
		}
		return $buttons;
	}

}
