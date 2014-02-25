<?php
namespace TYPO3\CMS\T3editor\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Tobias Liebig <mail_typo3@etobi.de>
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

/**
 * File edit hook for t3editor
 *
 * @author Tobias Liebig <mail_typo3@etobi.de>
 */
class FileEditHook {

	/**
	 * @var \TYPO3\CMS\T3editor\T3editor
	 */
	protected $t3editor = NULL;

	/**
	 * @var string
	 */
	protected $ajaxSaveType = 'TypoScriptTemplateInformationModuleFunctionController';

	/**
	 * @return \TYPO3\CMS\T3editor\T3editor
	 */
	protected function getT3editor() {
		if ($this->t3editor == NULL) {
			$this->t3editor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\T3editor\\T3editor')->setAjaxSaveType($this->ajaxSaveType);
		}
		return $this->t3editor;
	}

	/**
	 * Hook-function: inject t3editor JavaScript code before the page is compiled
	 * called in file_edit.php:SC_file_edit->main
	 *
	 * @param array $parameters
	 * @param \TYPO3\CMS\Backend\Controller\File\EditFileController $pObj
	 */
	public function preOutputProcessingHook($parameters, $pObj) {
		$t3editor = $this->getT3editor();
		$t3editor->setModeByFile($parameters['target']);
		if (!$t3editor->isEnabled() || !$t3editor->getMode()) {
			return;
		}
		$parameters['content'] = str_replace('<!--###POSTJSMARKER###-->', '<!--###POSTJSMARKER###-->' . $t3editor->getModeSpecificJavascriptCode(), $parameters['content']);
	}

	/**
	 * Hook-function: inject t3editor JavaScript code before the page is compiled
	 * called in \TYPO3\CMS\Backend\Template\DocumentTemplate:startPage
	 *
	 * @param array $parameters
	 * @param \TYPO3\CMS\Backend\Template\DocumentTemplate $pObj
	 * @see \TYPO3\CMS\Backend\Template\DocumentTemplate::startPage
	 */
	public function preStartPageHook($parameters, $pObj) {
		if (preg_match('/typo3\\/file_edit\\.php/', $_SERVER['SCRIPT_NAME'])) {
			$t3editor = $this->getT3editor();
			if (!$t3editor->isEnabled()) {
				return;
			}
			$pObj->JScode .= $t3editor->getJavascriptCode($pObj);
			$pObj->loadJavascriptLib(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3editor') . 'res/jslib/fileedit.js');
		}
	}

	/**
	 * Hook-function:
	 * called in file_edit.php:SC_file_edit->main
	 *
	 * @param array $parameters
	 * @param \TYPO3\CMS\Backend\Controller\File\EditFileController $pObj
	 */
	public function postOutputProcessingHook($parameters, $pObj) {
		$t3editor = $this->getT3editor();
		if (!$t3editor->isEnabled() || !$t3editor->getMode()) {
			return;
		}
		$attributes = 'rows="30" ' . 'wrap="off" ' . $pObj->doc->formWidth(48, TRUE, 'width:98%;height:60%');
		$title = $GLOBALS['LANG']->getLL('file') . ' ' . htmlspecialchars($pObj->target);
		$outCode = $t3editor->getCodeEditor('file[editfile][0][data]', 'fixed-font enable-tab', '$1', $attributes, $title, array(
			'target' => (int)$pObj->target
		));
		$parameters['pageContent'] = preg_replace('/\\<textarea .*name="file\\[editfile\\]\\[0\\]\\[data\\]".*\\>([^\\<]*)\\<\\/textarea\\>/mi', $outCode, $parameters['pageContent']);
	}

	/**
	 * @param array $parameters
	 * @param mixed $pObj
	 *
	 * @return boolean TRUE if successful
	 */
	public function save($parameters, $pObj) {
		$savingsuccess = FALSE;
		if ($parameters['type'] == $this->ajaxSaveType) {
			$tceFile = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\File\\FileController');
			$tceFile->processAjaxRequest(array(), $parameters['ajaxObj']);
			$result = $parameters['ajaxObj']->getContent('result');
			$savingsuccess = is_array($result) && $result['editfile'][0];
		}
		return $savingsuccess;
	}

}
