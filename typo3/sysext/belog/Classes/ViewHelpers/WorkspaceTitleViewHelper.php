<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

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

/**
 * Get workspace title from workspace id
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @internal
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
	 * @param int $uid UID of the workspace
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
