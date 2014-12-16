<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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
 * Controller for configuration related actions.
 *
 * @author Francois Suter <francois.suter@typo3.org>
 */
class UpdateScriptController extends AbstractController {

	/**
	 * Show the content of the update script (if any).
	 *
	 * @param string $extensionKey Extension key
	 * @return void
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 */
	public function showAction($extensionKey) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Request for update script', 'extensionmanager', 0, $extensionKey);

		/** @var $updateScriptUtility \TYPO3\CMS\Extensionmanager\Utility\UpdateScriptUtility */
		$updateScriptUtility = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\UpdateScriptUtility::class);
		$updateScriptResult = $updateScriptUtility->executeUpdateIfNeeded($extensionKey);
		$this->view
			->assign('updateScriptResult', $updateScriptResult)
			->assign('extensionKey', $extensionKey);
	}

}
