<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

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
	 * First level cache of workspace titles
	 *
	 * @var array
	 */
	static protected $workspaceTitleRuntimeCache = array();

	/**
	 * Resolve workspace title from UID.
	 *
	 * @param integer $uid UID of the workspace
	 * @return string username or UID
	 */
	public function render($uid) {
		if (isset(static::$workspaceTitleRuntimeCache[$uid])) {
			return static::$workspaceTitleRuntimeCache[$uid];
		}

		if ($uid === 0) {
			static::$workspaceTitleRuntimeCache[$uid] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('live', $this->controllerContext->getRequest()->getControllerExtensionName());
		} elseif (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
			static::$workspaceTitleRuntimeCache[$uid] = '';
		} else {
			/** @var $workspace \TYPO3\CMS\Belog\Domain\Model\Workspace */
			$workspace = $this->workspaceRepository->findByUid($uid);
			// $workspace may be null, force empty string in this case
			static::$workspaceTitleRuntimeCache[$uid] = ($workspace === NULL) ? '' : $workspace->getTitle();
		}

		return static::$workspaceTitleRuntimeCache[$uid];
	}

}
