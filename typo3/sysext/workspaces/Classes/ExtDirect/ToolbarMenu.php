<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

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
