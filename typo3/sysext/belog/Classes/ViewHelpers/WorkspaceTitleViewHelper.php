<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Get workspace title from workspace id
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class WorkspaceTitleViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\CMS\Belog\Domain\Repository\WorkspaceRepository
	 * @inject
	 */
	protected $workspaceRepository = NULL;

	/**
	 * Resolve workspace title from UID.
	 *
	 * @param integer $uid UID of the workspace
	 * @return string username or UID
	 */
	public function render($uid) {
		if ($uid === 0) {
			return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('live', $this->controllerContext->getRequest()->getControllerExtensionName());
		}
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
			return '';
		}
		/** @var $workspace \TYPO3\CMS\Belog\Domain\Model\Workspace */
		$workspace = $this->workspaceRepository->findByUid($uid);
		if ($workspace !== NULL) {
			$title = $workspace->getTitle();
		} else {
			$title = '';
		}
		return $title;
	}

}

?>