<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Felix Kopp <felix-source@phorax.com>
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
 * @package TYPO3
 * @subpackage beuser
 */
class Tx_Beuser_Controller_BackendUserController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @var Tx_Beuser_Domain_Model_ModuleData
	 */
	protected $moduleData;

	/**
	 * @var Tx_Beuser_Service_ModuleDataStorageService
	 * @inject
	 */
	protected $moduleDataStorageService;

	/**
	  * @var Tx_Beuser_Domain_Repository_BackendUserRepository
	  * @inject
	  */
	protected $backendUserRepository;

	/**
	 * @var Tx_Beuser_Domain_Repository_BackendUserGroupRepository
	 * @inject
	 */
	protected $backendUserGroupRepository;

	/**
	 * @var Tx_Beuser_Domain_Repository_BackendUserSessionRepository
	 * @inject
	 */
	protected $backendUserSessionRepository;

	/**
	 * Load and persist module data
	 *
	 * @param Tx_Extbase_MVC_RequestInterface $request
	 * @param Tx_Extbase_MVC_ResponseInterface $response
	 * @throws Tx_Extbase_MVC_Exception_StopAction
	 * @return void
	 */
	public function processRequest(Tx_Extbase_MVC_RequestInterface $request, Tx_Extbase_MVC_ResponseInterface $response) {
		$this->moduleData = $this->moduleDataStorageService->loadModuleData();

			// We "finally" persist the module data.
		try {
			parent::processRequest($request, $response);
			$this->moduleDataStorageService->persistModuleData($this->moduleData);
		} catch (Tx_Extbase_MVC_Exception_StopAction $e) {
			$this->moduleDataStorageService->persistModuleData($this->moduleData);
			throw $e;
		}
	}

	/**
	 * Displays all BackendUsers
	 * - Switch session to different user
	 *
	 * @param Tx_Beuser_Domain_Model_Demand $demand
	 * @return void
	 */
	public function indexAction(Tx_Beuser_Domain_Model_Demand $demand = NULL) {
		if ($demand === NULL) {
			$demand = $this->moduleData->getDemand();
		} else {
			$this->moduleData->setDemand($demand);
		}

			// Switch user permanently or only until logout
		if (t3lib_div::_GP('SwitchUser')) {
			$this->switchUser(t3lib_div::_GP('SwitchUser'), t3lib_div::_GP('switchBackUser'));
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
		$this->forward('index');
	}

	/**
	 * Terminate BackendUser session and logout corresponding client
	 * Redirects to onlineAction with message
	 *
	 * @param Tx_Beuser_Domain_Model_BackendUser $backendUser
	 * @param string $sessionId
	 * @return void
	 */
	protected function terminateBackendUserSessionAction(Tx_Beuser_Domain_Model_BackendUser $backendUser, $sessionId) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'be_sessions',
			'ses_userid = "' . $backendUser->getUid() . '"' .
				' AND ses_id = "' . $sessionId . '"' .
				' LIMIT 1'
		);

		if ($GLOBALS['TYPO3_DB']->sql_affected_rows() == 1) {
			$message = 'Session successfully terminated.';
			$this->flashMessageContainer->add($message, '', t3lib_FlashMessage::OK);
		}

		$this->forward('online');
	}

	/**
	 * Switches to a given user (SU-mode) and then redirects to the start page of the backend to refresh the navigation etc.
	 *
	 * @param array $switchUser BE-user record that will be switched to
	 * @param boolean $switchBack
	 * @return void
	 */
	protected function switchUser($switchUser, $switchBack = FALSE) {
		$targetUser = t3lib_BEfunc::getRecord('be_users', $switchUser);
		if (is_array($targetUser) && $GLOBALS['BE_USER']->isAdmin()) {
			$updateData['ses_userid'] = $targetUser['uid'];

				// User switchback or replace current session?
			if ($switchBack) {
				$updateData['ses_backuserid'] = intval($GLOBALS['BE_USER']->user['uid']);
			}
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_sessions', 'ses_id=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['BE_USER']->id, 'be_sessions') . ' AND ses_name=' . $GLOBALS['TYPO3_DB']->fullQuoteStr(t3lib_beUserAuth::getCookieName(), 'be_sessions') . ' AND ses_userid=' . intval($GLOBALS['BE_USER']->user['uid']), $updateData);

			$redirectUrl = $GLOBALS['BACK_PATH'] . 'index.php' . ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] ? '' : '?commandLI=1');
			t3lib_utility_Http::redirect($redirectUrl);
		}
	}

}

?>