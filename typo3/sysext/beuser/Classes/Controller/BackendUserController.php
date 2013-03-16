<?php
namespace TYPO3\CMS\Beuser\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Felix Kopp <felix-source@phorax.com>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Backend module user administration controller
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class BackendUserController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\CMS\Beuser\Domain\Model\ModuleData
	 */
	protected $moduleData;

	/**
	 * @var \TYPO3\CMS\Beuser\Service\ModuleDataStorageService
	 * @inject
	 */
	protected $moduleDataStorageService;

	/**
	 * @var \TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository
	 * @inject
	 */
	protected $backendUserRepository;

	/**
	 * @var \TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository
	 * @inject
	 */
	protected $backendUserGroupRepository;

	/**
	 * @var \TYPO3\CMS\Beuser\Domain\Repository\BackendUserSessionRepository
	 * @inject
	 */
	protected $backendUserSessionRepository;

	/**
	 * Load and persist module data
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 */
	public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {
		$this->moduleData = $this->moduleDataStorageService->loadModuleData();
		// We "finally" persist the module data.
		try {
			parent::processRequest($request, $response);
			$this->moduleDataStorageService->persistModuleData($this->moduleData);
		} catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
			$this->moduleDataStorageService->persistModuleData($this->moduleData);
			throw $e;
		}
	}

	/**
	 * Initialize actions
	 *
	 * @return void
	 * @throws \RuntimeException
	 */
	public function initializeAction() {
		// @TODO: Extbase backend modules relies on frontend TypoScript for view, persistence
		// and settings. Thus, we need a TypoScript root template, that then loads the
		// ext_typoscript_setup.txt file of this module. This is nasty, but can not be
		// circumvented until there is a better solution in extbase.
		// For now we throw an exception if no settings are detected.
		if (empty($this->settings)) {
			throw new \RuntimeException('No settings detected. This module can not work then. This usually happens if there is no frontend TypoScript template with root flag set. ' . 'Please create a frontend page with a TypoScript root template.', 1344375003);
		}
	}

	/**
	 * Displays all BackendUsers
	 * - Switch session to different user
	 *
	 * @param \TYPO3\CMS\Beuser\Domain\Model\Demand $demand
	 * @return void
	 */
	public function indexAction(\TYPO3\CMS\Beuser\Domain\Model\Demand $demand = NULL) {
		if ($demand === NULL) {
			$demand = $this->moduleData->getDemand();
		} else {
			$this->moduleData->setDemand($demand);
		}
		// Switch user permanently or only until logout
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SwitchUser')) {
			$this->switchUser(
				\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SwitchUser'),
				\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('switchBackUser')
			);
		}
		$compareUserList = $this->moduleData->getCompareUserList();
		$this->view->assign('demand', $demand);
		$this->view->assign('returnUrl', 'mod.php?M=tools_BeuserTxBeuser');
		$this->view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
		$this->view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);
		$this->view->assign('backendUsers', $this->backendUserRepository->findDemanded($demand));
		$this->view->assign('backendUserGroups', array_merge(array(''), $this->backendUserGroupRepository->findAll()->toArray()));
		$this->view->assign('compareUserList', !empty($compareUserList) ? $this->backendUserRepository->findByUidList($compareUserList) : '');
	}

	/**
	 * Views all currently logged in BackendUsers and their sessions
	 *
	 * @return void
	 */
	public function onlineAction() {
		$onlineUsersAndSessions = array();
		$onlineUsers = $this->backendUserRepository->findOnline();
		foreach ($onlineUsers as $onlineUser) {
			$onlineUsersAndSessions[] = array(
				'backendUser' => $onlineUser,
				'sessions' => $this->backendUserSessionRepository->findByBackendUser($onlineUser)
			);
		}
		$this->view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
		$this->view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);
		$this->view->assign('onlineUsersAndSessions', $onlineUsersAndSessions);
		$this->view->assign('currentSessionId', $GLOBALS['BE_USER']->user['ses_id']);
	}

	/**
	 * Compare backend users from demand
	 *
	 * @return void
	 */
	public function compareAction() {
		$compareUserList = $this->moduleData->getCompareUserList();
		$this->view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
		$this->view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);
		$this->view->assign('compareUserList', !empty($compareUserList) ? $this->backendUserRepository->findByUidList($compareUserList) : '');
	}

	/**
	 * Attaches one backend user to the compare list
	 *
	 * @param integer $uid
	 * @retun void
	 */
	public function addToCompareListAction($uid) {
		$this->moduleData->attachUidCompareUser($uid);
		$this->moduleDataStorageService->persistModuleData($this->moduleData);
		$this->forward('index');
	}

	/**
	 * Removes given backend user to the compare list
	 *
	 * @param integer $uid
	 * @retun void
	 */
	public function removeFromCompareListAction($uid) {
		$this->moduleData->detachUidCompareUser($uid);
		$this->moduleDataStorageService->persistModuleData($this->moduleData);
		$this->forward('index');
	}

	/**
	 * Terminate BackendUser session and logout corresponding client
	 * Redirects to onlineAction with message
	 *
	 * @param \TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser
	 * @param string $sessionId
	 * @return void
	 */
	protected function terminateBackendUserSessionAction(\TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser, $sessionId) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'be_sessions',
			'ses_userid = "' . intval($backendUser->getUid()) . '" AND ses_id = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($sessionId, 'be_sessions') . ' LIMIT 1'
		);
		if ($GLOBALS['TYPO3_DB']->sql_affected_rows() == 1) {
			$message = 'Session successfully terminated.';
			$this->flashMessageContainer->add($message, '', \TYPO3\CMS\Core\Messaging\FlashMessage::OK);
		}
		$this->forward('online');
	}

	/**
	 * Switches to a given user (SU-mode) and then redirects to the start page of the backend to refresh the navigation etc.
	 *
	 * @param string $switchUser BE-user record that will be switched to
	 * @param boolean $switchBack
	 * @return void
	 */
	protected function switchUser($switchUser, $switchBack = FALSE) {
		$targetUser = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_users', $switchUser);
		if (is_array($targetUser) && $GLOBALS['BE_USER']->isAdmin()) {
			$updateData['ses_userid'] = $targetUser['uid'];
			// User switchback or replace current session?
			if ($switchBack) {
				$updateData['ses_backuserid'] = intval($GLOBALS['BE_USER']->user['uid']);
			}

			$whereClause = 'ses_id=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['BE_USER']->id, 'be_sessions');
			$whereClause .= ' AND ses_name=' . $GLOBALS['TYPO3_DB']->fullQuoteStr(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::getCookieName(), 'be_sessions');
			$whereClause .= ' AND ses_userid=' . intval($GLOBALS['BE_USER']->user['uid']);

			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'be_sessions',
				$whereClause,
				$updateData
			);
			$redirectUrl = $GLOBALS['BACK_PATH'] . 'index.php' . ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] ? '' : '?commandLI=1');
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
		}
	}

}

?>