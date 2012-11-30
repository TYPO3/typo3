<?php
namespace TYPO3\CMS\SysNote\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Kai Vogel <kai.vogel@speedprogs.de>, Speedprogs.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Note controller
 *
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 */
class NoteController {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository
	 */
	protected $sysNoteRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository
	 */
	protected $backendUserRepository;

	/**
	 * @var string
	 */
	protected $extensionName = 'SysNote';

	/**
	 * @var string
	 */
	protected $controllerName = 'Note';

	/**
	 * Initialize controller
	 *
	 * @return void
	 */
	public function __construct() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\Extbase\\Object\\ObjectManager');
		$this->configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$this->backendUserRepository = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\BackendUserRepository');
		$this->sysNoteRepository = $this->objectManager->get('TYPO3\\CMS\\SysNote\\Domain\\Repository\\SysNoteRepository');
		$this->view = $this->objectManager->create('TYPO3\\CMS\\Fluid\\View\\TemplateView');
		$this->initializeView($this->view);
	}

	/**
	 * Render notes by single PID or PID list
	 *
	 * @param mixed $pids Single PID or comma separated list of PIDs
	 * @return string
	 */
	public function renderNotes($pids) {
		if (empty($pids) || empty($GLOBALS['BE_USER']->user['uid'])) {
			return '';
		}
		$author = $this->backendUserRepository->findByUid($GLOBALS['BE_USER']->user['uid']);
		$notes = $this->sysNoteRepository->findByPidsAndAuthor($pids, $author);
		$this->view->assign('notes', $notes);
		return $this->view->render('list');
	}

	/**
	 * Initialize view configuration
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view The view object
	 * @return void
	 */
	protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
		$configuration = $this->configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
			$this->extensionName
		);
		if (!empty($configuration['view']['templateRootPath'])) {
			$view->setTemplateRootPath(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($configuration['view']['templateRootPath']));
		}
		if (!empty($configuration['view']['layoutRootPath'])) {
			$view->setLayoutRootPath(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($configuration['view']['layoutRootPath']));
		}
		if (!empty($configuration['view']['partialRootPath'])) {
			$view->setPartialRootPath(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($configuration['view']['partialRootPath']));
		}
		$request = $this->objectManager->create('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Request');
		$request->setControllerExtensionName($this->extensionName);
		$request->setControllerName($this->controllerName);
		$controllerContext = $this->objectManager->create('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerContext');
		$controllerContext->setRequest($request);
		$view->setControllerContext($controllerContext);
		$view->initializeView();
		if (!empty($configuration['settings'])) {
			$view->assign('settings', $configuration['settings']);
		}
	}

}
?>