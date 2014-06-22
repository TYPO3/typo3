<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

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
 * ExtDirect toolbar menu
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class ToolbarMenu {

	/**
	 * @param $parameter
	 * @return array
	 */
	public function toggleWorkspacePreviewMode($parameter) {
		$newState = $GLOBALS['BE_USER']->user['workspace_preview'] ? '0' : '1';
		$GLOBALS['BE_USER']->setWorkspacePreview($newState);
		return array('newWorkspacePreviewState' => $newState);
	}

	/**
	 * @param $parameter
	 * @return array
	 */
	public function setWorkspace($parameter) {
		$workspaceId = (int)$parameter->workSpaceId;
		$pageId = (int)$parameter->pageId;

		$GLOBALS['BE_USER']->setWorkspace($workspaceId);

		while ($pageId) {
			$page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $pageId, '*',
				' AND pages.t3ver_wsid IN (0, ' . $workspaceId . ')');
			if ($page) {
				if ($GLOBALS['BE_USER']->doesUserHaveAccess($page, 1)) {
					break;
				}
			} else {
				$page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $pageId);
			}
			$pageId = $page['pid'];
		}

		return array(
			'title' => \TYPO3\CMS\Workspaces\Service\WorkspaceService::getWorkspaceTitle($workspaceId),
			'id' => $workspaceId,
			'page' => (isset($page['uid']) && ($parameter->pageId == $page['uid'])) ? NULL : (int)$page['uid']
		);
	}

}
