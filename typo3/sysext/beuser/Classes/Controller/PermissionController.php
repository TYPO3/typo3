<?php
namespace TYPO3\CMS\Beuser\Controller;

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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Version\View\VersionView;

/**
 * Backend module page permissions
 *
 * @author Frank Nägler <typo3@naegler.net>
 */
class PermissionController extends ActionController {

	/**
	 * @var string prefix for session
	 */
	const SESSION_PREFIX = 'tx_Beuser_';

	/**
	 * @var int the current page id
	 */
	protected $id;

	/**
	 * @var int
	 */
	protected $returnId;

	/**
	 * @var int
	 */
	protected $depth;

	/**
	 * @var int
	 */
	protected $lastEdited;

	/**
	 * Number of levels to enable recursive settings for
	 *
	 * @var int
	 */
	protected $getLevels = 10;

	/**
	 * @var array
	 */
	protected $pageInfo = array();

	/**
	 * Initialize action
	 *
	 * @return void
	 */
	protected function initializeAction() {
		// determine id parameter
		$this->id = (int)GeneralUtility::_GP('id');
		if ($this->request->hasArgument('id')) {
			$this->id = (int)$this->request->getArgument('id');
		}

		// determine depth parameter
		$this->depth = ((int)GeneralUtility::_GP('depth') > 0)
			? (int) GeneralUtility::_GP('depth')
			: $this->getBackendUser()->getSessionData(self::SESSION_PREFIX . 'depth');
		if ($this->request->hasArgument('depth')) {
			$this->depth = (int)$this->request->getArgument('depth');
		}
		$this->getBackendUser()->setAndSaveSessionData(self::SESSION_PREFIX . 'depth', $this->depth);
		$this->lastEdited = GeneralUtility::_GP('lastEdited');
		$this->returnId = GeneralUtility::_GP('returnId');
		$this->pageInfo = BackendUtility::readPageAccess($this->id, ' 1=1');
	}

	/**
	 * Initializes view
	 *
	 * @param ViewInterface $view The view to be initialized
	 * @return void
	 */
	protected function initializeView(ViewInterface $view) {
		$view->assign(
			'previewUrl',
			BackendUtility::viewonclick(
				$this->pageInfo['uid'], $GLOBALS['BACK_PATH'],
				BackendUtility::BEgetRootLine($this->pageInfo['uid'])
			)
		);
	}

	/**
	 * Index action
	 *
	 * @return void
	 */
	public function indexAction() {
		if (!$this->id) {
			$this->pageInfo = array('title' => '[root-level]', 'uid' => 0, 'pid' => 0);
		}

		$this->view->assign('versionSelector', $this->getVersionSelector($this->id, TRUE));
		if ($this->getBackendUser()->workspace != 0) {
			// Adding section with the permission setting matrix:
			$this->addFlashMessage(
				LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarningText', 'beuser'),
				LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarning', 'beuser'),
				FlashMessage::WARNING
			);
		}

		// depth options
		$depthOptions = array();
		$url = $this->uriBuilder->reset()->setArguments(array(
			'action' => 'index',
			'depth' => '__DEPTH__',
			'id' => $this->id
		))->buildBackendUri();
		foreach (array(1, 2, 3, 4, 10) as $depthLevel) {
			$depthOptions[$depthLevel] = $depthLevel . ' ' . LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:levels', 'beuser');
		}
		$this->view->assign('depthBaseUrl', $url);
		$this->view->assign('depth', $this->depth);
		$this->view->assign('depthOptions', $depthOptions);

		$beUserArray = BackendUtility::getUserNames();
		$beGroupArray = BackendUtility::getGroupNames();

		/** @var PageTreeView */
		$tree = GeneralUtility::makeInstance(PageTreeView::class);
		$tree->init();
		$tree->addField('perms_user', TRUE);
		$tree->addField('perms_group', TRUE);
		$tree->addField('perms_everybody', TRUE);
		$tree->addField('perms_userid', TRUE);
		$tree->addField('perms_groupid', TRUE);
		$tree->addField('hidden');
		$tree->addField('fe_group');
		$tree->addField('starttime');
		$tree->addField('endtime');
		$tree->addField('editlock');

		// Creating top icon; the current page
		$html = IconUtility::getSpriteIconForRecord('pages', $this->pageInfo);
		$tree->tree[] = array('row' => $this->pageInfo, 'HTML' => $html);

		// Create the tree from $this->id:
		$tree->getTree($this->id, $this->depth, '');

		// Traverse tree:
		$treeData = array();
		foreach ($tree->tree as $data) {
			$viewDataRow = array();
			$pageId = $data['row']['uid'];
			$viewData['pageId'] = $pageId;

			// User/Group names:
			if ($beUserArray[$data['row']['perms_userid']]) {
				$userName = $beUserArray[$data['row']['perms_userid']]['username'];
			} else {
				$userName = ($data['row']['perms_userid'] ? $data['row']['perms_userid'] : '');
			}

			if ($data['row']['perms_userid'] && !$beUserArray[$data['row']['perms_userid']]) {
				$userName = PermissionAjaxController::renderOwnername(
					$pageId,
					$data['row']['perms_userid'],
					htmlspecialchars(GeneralUtility::fixed_lgd_cs($userName, 20)),
					FALSE
				);
			} else {
				$userName = PermissionAjaxController::renderOwnername(
					$pageId,
					$data['row']['perms_userid'],
					htmlspecialchars(GeneralUtility::fixed_lgd_cs($userName, 20))
				);
			}
			$viewDataRow['userName'] = $userName;

			if ($beGroupArray[$data['row']['perms_groupid']]) {
				$groupName = $beGroupArray[$data['row']['perms_groupid']]['title'];
			} else {
				$groupName = $data['row']['perms_groupid'] ? $data['row']['perms_groupid'] : '';
			}

			if ($data['row']['perms_groupid'] && !$beGroupArray[$data['row']['perms_groupid']]) {
				$groupName = PermissionAjaxController::renderGroupname(
					$pageId,
					$data['row']['perms_groupid'],
					htmlspecialchars(GeneralUtility::fixed_lgd_cs($groupName, 20)),
					FALSE
				);
			} else {
				$groupName = PermissionAjaxController::renderGroupname(
					$pageId, $data['row']['perms_groupid'],
					htmlspecialchars(GeneralUtility::fixed_lgd_cs($groupName, 20))
				);
			}
			$viewDataRow['groupName'] = $groupName;

			$viewData['html'] = $this->getControllerDocumentTemplate()->wrapClickMenuOnIcon($data['HTML'], 'pages', $data['row']['uid'])
				. htmlspecialchars(GeneralUtility::fixed_lgd_cs($data['row']['title'], 20));
			$viewData['id'] = $data['row']['_ORIG_uid'] ? $data['row']['_ORIG_uid'] : $pageId;

			$viewData['userPermissions'] = ($pageId ?
				PermissionAjaxController::renderPermissions($data['row']['perms_user'], $pageId, 'user') .
				' ' . $userName : '');
			$viewData['groupPermissions'] = ($pageId ?
				PermissionAjaxController::renderPermissions($data['row']['perms_group'], $pageId, 'group') .
				' ' . $groupName : '');
			$viewData['otherPermissions'] = ($pageId ? ' ' .
				PermissionAjaxController::renderPermissions($data['row']['perms_everybody'], $pageId, 'everybody') : '');

			$viewData['editLock'] = ($data['row']['editlock']) ? TRUE : FALSE;

			$treeData[] = $viewData;
		}
		$this->view->assign('viewTree', $treeData);

		// CSH for permissions setting
		$this->view->assign('cshItem', BackendUtility::cshItem('xMOD_csh_corebe', 'perm_module', $GLOBALS['BACK_PATH']));
	}

	/**
	 * Edit action
	 *
	 * @return void
	 */
	public function editAction() {
		$this->view->assign('id', $this->id);
		$this->view->assign('depth', $this->depth);

		if (!$this->id) {
			$this->pageInfo = array('title' => '[root-level]', 'uid' => 0, 'pid' => 0);
		}
		if ($this->getBackendUser()->workspace != 0) {
			// Adding FlashMessage with the permission setting matrix:
			$this->addFlashMessage(
				LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarningText', 'beuser'),
				LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarning', 'beuser'),
				FlashMessage::WARNING
			);
		}
		// Get usernames and groupnames
		$beGroupArray = BackendUtility::getListGroupNames('title,uid');
		$beUserArray  = BackendUtility::getUserNames();

		// Owner selector
		$beUserDataArray = array(0 => LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectNone', 'beuser'));
		foreach ($beUserArray as $uid => &$row) {
			$beUserDataArray[$uid] = $row['username'];
		}
		$beUserDataArray[-1] = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectUnchanged', 'beuser');
		$this->view->assign('currentBeUser', $this->pageInfo['perms_userid']);
		$this->view->assign('beUserData', $beUserDataArray);

		// Group selector
		$beGroupDataArray = array(0 => LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectNone', 'beuser'));
		foreach ($beGroupArray as $uid => $row) {
			$beGroupDataArray[$uid] = $row['title'];
		}
		$beGroupDataArray[-1] = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectUnchanged', 'beuser');
		$this->view->assign('currentBeGroup', $this->pageInfo['perms_groupid']);
		$this->view->assign('beGroupData', $beGroupDataArray);
		$this->view->assign('pageInfo', $this->pageInfo);
		$this->view->assign('returnId', $this->returnId);
		$this->view->assign('recursiveSelectOptions', $this->getRecursiveSelectOptions());
	}

	/**
	 * Update action
	 *
	 * @param array $data
	 * @param array $mirror
	 * @return void
	 */
	protected function updateAction(array $data, array $mirror) {
		if (!empty($data['pages'])) {
			foreach ($data['pages'] as $pageUid => $properties) {
				// if the owner and group field shouldn't be touched, unset the option
				if ((int)$properties['perms_userid'] === -1) {
					unset($properties['perms_userid']);
				}
				if ((int)$properties['perms_groupid'] === -1) {
					unset($properties['perms_groupid']);
				}
				$this->getDatabaseConnection()->exec_UPDATEquery(
					'pages',
					'uid = ' . (int)$pageUid,
					$properties
				);
				if (!empty($mirror['pages'][$pageUid])) {
					$mirrorPages = GeneralUtility::trimExplode(',', $mirror['pages'][$pageUid]);
					foreach ($mirrorPages as $mirrorPageUid) {
						$this->getDatabaseConnection()->exec_UPDATEquery(
							'pages',
							'uid = ' . (int)$mirrorPageUid,
							$properties
						);
					}
				}
			}
		}
		$this->redirect('index', NULL, NULL, array('id' => $this->returnId, 'depth' => $this->depth));
	}

	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return DocumentTemplate
	 */
	protected function getControllerDocumentTemplate() {
		return $GLOBALS['TBE_TEMPLATE'];
	}

	/**
	 * Finding tree and offer setting of values recursively.
	 *
	 * @return array
	 */
	protected function getRecursiveSelectOptions() {
		// Initialize tree object:
		$tree = GeneralUtility::makeInstance(PageTreeView::class);
		$tree->init();
		$tree->addField('perms_userid', TRUE);
		$tree->makeHTML = 0;
		$tree->setRecs = 1;
		// Make tree:
		$tree->getTree($this->id, $this->getLevels, '');
		$options = array();
		$options[''] = '';
		// If there are a hierarchy of page ids, then...
		if ($this->getBackendUser()->user['uid'] && count($tree->orig_ids_hierarchy)) {
			// Init:
			$labelRecursive = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:recursive', 'beuser');
			$labelLevels = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:levels', 'beuser');
			$labelPagesAffected = LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:pages_affected', 'beuser');
			$theIdListArr = array();
			// Traverse the number of levels we want to allow recursive
			// setting of permissions for:
			for ($a = $this->getLevels; $a > 0; $a--) {
				if (is_array($tree->orig_ids_hierarchy[$a])) {
					foreach ($tree->orig_ids_hierarchy[$a] as $theId) {
						$theIdListArr[] = $theId;
					}
					$lKey = $this->getLevels - $a + 1;
					$options[implode(',', $theIdListArr)] = $labelRecursive . ' ' . $lKey . ' ' . $labelLevels .
						' (' . count($theIdListArr) . ' ' . $labelPagesAffected . ')';
				}
			}
		}
		return $options;
	}

	/**
	 * Creates the version selector for the page id inputted.
	 * Requires the core version management extension, "version" to be loaded.
	 *
	 * @param int $id Page id to create selector for.
	 * @param bool $noAction If set, there will be no button for swapping page.
	 * @return string
	 */
	protected function getVersionSelector($id, $noAction = FALSE) {
		if (
			ExtensionManagementUtility::isLoaded('version') &&
			!ExtensionManagementUtility::isLoaded('workspaces')
		) {
			$versionView = GeneralUtility::makeInstance(VersionView::class);
			return $versionView->getVersionSelector($id, $noAction);
		}
		return '';
	}

}
