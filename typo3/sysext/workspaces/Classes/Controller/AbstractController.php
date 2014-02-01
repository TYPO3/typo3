<?php
namespace TYPO3\CMS\Workspaces\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * Abstract action controller.
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var string Key of the extension this controller belongs to
	 */
	protected $extensionName = 'Workspaces';

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * @var integer
	 */
	protected $pageId;

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * @return void
	 */
	protected function initializeAction() {
		// @todo Evaluate how the intval() call can be used with Extbase validators/filters
		$this->pageId = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
		$icons = array(
			'language' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('flags-multiple'),
			'integrity' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('status-dialog-information'),
			'success' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('status-dialog-ok'),
			'info' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('status-dialog-information'),
			'warning' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('status-dialog-warning'),
			'error' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('status-dialog-error')
		);
		$this->pageRenderer->addInlineSetting('Workspaces', 'icons', $icons);
		$this->pageRenderer->addInlineSetting('Workspaces', 'id', $this->pageId);
		$this->pageRenderer->addInlineSetting('Workspaces', 'depth', $this->pageId === 0 ? 999 : 1);
		$this->pageRenderer->addInlineSetting('Workspaces', 'language', $this->getLanguageSelection());
		$this->pageRenderer->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('workspaces') . 'Resources/Public/StyleSheet/module.css');
		$this->pageRenderer->addInlineLanguageLabelArray(array(
			'title' => $GLOBALS['LANG']->getLL('title'),
			'path' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.path'),
			'table' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.table'),
			'depth' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_perm.xlf:Depth'),
			'depth_0' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
			'depth_1' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
			'depth_2' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
			'depth_3' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
			'depth_4' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_4'),
			'depth_infi' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
		));
		$this->pageRenderer->addInlineLanguageLabelFile('EXT:workspaces/Resources/Private/Language/locallang.xlf');
		$this->assignExtensionSettings();
	}

	/**
	 * Assigns additional Workspace settings to TYPO3.settings.Workspaces.extension
	 *
	 * @return void
	 */
	protected function assignExtensionSettings() {
		$extension = array(
			'AdditionalColumn' => array(
				'Definition' => array(),
				'Handler' => array(),
			),
		);

		$extension['AdditionalColumn']['Definition'] = $this->getAdditionalColumnService()->getDefinition();
		$extension['AdditionalColumn']['Handler'] = $this->getAdditionalColumnService()->getHandler();
		$this->pageRenderer->addInlineSetting('Workspaces', 'extension', $extension);
	}

	/**
	 * Processes a general request. The result can be returned by altering the given response.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, modified by this handler
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException if the controller doesn't support the current request type
	 * @return void
	 */
	public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {
		$this->template = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->pageRenderer = $this->template->getPageRenderer();
		$GLOBALS['SOBE'] = new \stdClass();
		$GLOBALS['SOBE']->doc = $this->template;
		parent::processRequest($request, $response);
		$pageHeader = $this->template->startpage($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:module.title'));
		$pageEnd = $this->template->endPage();
		$response->setContent($pageHeader . $response->getContent() . $pageEnd);
	}

	/**
	 * Gets the selected language.
	 *
	 * @return string
	 */
	protected function getLanguageSelection() {
		$language = 'all';
		$backendUser = $this->getBackendUser();
		if (isset($backendUser->uc['moduleData']['Workspaces'][$backendUser->workspace]['language'])) {
			$language = $backendUser->uc['moduleData']['Workspaces'][$backendUser->workspace]['language'];
		}
		return $language;
	}

	/**
	 * @return \TYPO3\CMS\Workspaces\Service\AdditionalColumnService
	 */
	protected function getAdditionalColumnService() {
		return $this->objectManager->get('TYPO3\\CMS\\Workspaces\\Service\\AdditionalColumnService');
	}

	/**
	 * @return \TYPO3\CMS\Workspaces\Service\AdditionalResourceService
	 */
	protected function getAdditionalResourceService() {
		return $this->objectManager->get('TYPO3\\CMS\\Workspaces\\Service\\AdditionalResourceService');
	}

	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

}
