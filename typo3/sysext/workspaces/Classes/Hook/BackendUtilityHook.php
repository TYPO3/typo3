<?php
namespace TYPO3\CMS\Workspaces\Hook;

/**
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
/**
 * Befunc service
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class BackendUtilityHook implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Gets a singleton instance of this object.
	 *
	 * @return \TYPO3\CMS\Workspaces\Hook\BackendUtilityHook
	 */
	static public function getInstance() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(__CLASS__);
	}

	/**
	 * Hooks into the \TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick and redirects to the workspace preview
	 * only if we're in a workspace and if the frontend-preview is disabled.
	 *
	 * @param $pageUid
	 * @param $backPath
	 * @param $rootLine
	 * @param $anchorSection
	 * @param $viewScript
	 * @param $additionalGetVars
	 * @param $switchFocus
	 * @return void
	 */
	public function preProcess(&$pageUid, $backPath, $rootLine, $anchorSection, &$viewScript, $additionalGetVars, $switchFocus) {
		if ($GLOBALS['BE_USER']->workspace !== 0) {
			$viewScript = $this->getWorkspaceService()->generateWorkspaceSplittedPreviewLink($pageUid);
		}
	}

	/**
	 * Gets an instance of the workspaces service.
	 *
	 * @return \TYPO3\CMS\Workspaces\Service\WorkspaceService
	 */
	protected function getWorkspaceService() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
	}

	/**
	 * Use that hook to show a info message in case someone starts editing
	 * a staged element
	 *
	 * @param $params
	 * @param $form
	 * @return boolean
	 */
	public function makeEditForm_accessCheck($params, &$form) {
		if ($GLOBALS['BE_USER']->workspace !== 0 && $GLOBALS['TCA'][$params['table']]['ctrl']['versioningWS']) {
			$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($params['table'], $params['uid']);
			if (abs($record['t3ver_stage']) > \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_EDIT_ID) {
				$stages = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\StagesService');
				$stageName = $stages->getStageTitle($record['t3ver_stage']);
				$editingName = $stages->getStageTitle(\TYPO3\CMS\Workspaces\Service\StagesService::STAGE_EDIT_ID);
				$message = $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:info.elementAlreadyModified');
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', sprintf($message, $stageName, $editingName), '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO, TRUE);
				/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
				$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
				/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
				$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
				$defaultFlashMessageQueue->enqueue($flashMessage);
			}
		}
		return $params['hasAccess'];
	}

}
