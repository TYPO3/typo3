<?php
namespace TYPO3\CMS\Install\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Steffen Gebert <steffen.gebert@typo3.org>
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

/**
 * Backend module of the 'install' extension, which automatically enables the
 * Install Tool, if it's accessed by an authenticated Backend user.
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class InstallToolModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * Entry point for the backend module
	 *
	 * @return void
	 */
	public function main() {
		/** @var $installToolService \TYPO3\CMS\Install\EnableFileService */
		$installToolService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\EnableFileService');
		if ($installToolService->checkInstallToolEnableFile()) {
			// Install Tool is already enabled
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect('install/');
		} elseif ($this->isValidEnableRequest()) {
			// Install Tool should be enabled
			$installToolService->createInstallToolEnableFile();
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect('install/');
		} else {
			// ask the user to enable the Install Tool
			$this->showInstallToolEnableRequest();
		}
	}

	/**
	 * Checks if enabling install tool is requested and form token is correct
	 *
	 * @return bool
	 */
	protected function isValidEnableRequest() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('enableInstallTool') && \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()->validateToken(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('formToken'), 'installToolEnableToken');
	}

	/**
	 * Shows warning message about ENABLE_INSTALL_TOOL file and a button to create this file
	 *
	 * @return void
	 */
	protected function showInstallToolEnableRequest() {
		// Create instance of object for output of data
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->setModuleTemplate(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install') . 'mod/mod_template.html');
		$this->doc->form = '<form method="post" id="t3-install-form-unlock" action="">';
		$this->doc->addStyleSheet('install', 'stylesheets/install/install.css');
		$this->doc->addStyleSheet('mod-install', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('install') . 'mod/mod_styles.css');
		$markers = ($buttons = array());
		$markers['CONTENT'] = $this->renderMessage();
		$content = $this->doc->moduleBody('', $buttons, $markers);
		$this->doc->postCode = '<input type="hidden" name="enableInstallTool" value="1" />' . \TYPO3\CMS\Backend\Form\FormEngine::getHiddenTokenField('installToolEnableToken');
		echo $this->doc->render('', $content);
	}

	/**
	 * Renders the message and the activation button
	 *
	 * @return string
	 */
	protected function renderMessage() {
		/** @var $message \TYPO3\CMS\Core\Messaging\ErrorpageMessage */
		$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\ErrorpageMessage');
		$message->setTitle($GLOBALS['LANG']->sL('LLL:EXT:install/mod/locallang_mod.xlf:confirmUnlockInstallToolTitle'));
		$message->setSeverity(\TYPO3\CMS\Core\Messaging\ErrorpageMessage::WARNING);
		$message->setHtmlTemplate('/typo3/templates/install.html');
		$content = $GLOBALS['LANG']->sL('LLL:EXT:install/mod/locallang_mod.xlf:confirmUnlockInstallToolMessage') . '<button type="submit">' . $GLOBALS['LANG']->sL('LLL:EXT:install/mod/locallang_mod.xlf:confirmUnlockInstallToolButton') . '<span class="t3-install-form-button-icon-positive">&nbsp;</span></button>';
		$messageMarkers = array();
		$messageMarkers['###CONTENT###'] = $content;
		$message->setMarkers($messageMarkers);
		return $message->render();
	}

}


?>