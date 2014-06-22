<?php
namespace TYPO3\CMS\Impexp\Hook;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * This class adds import export related JavaScript to the backend
 */
class BackendControllerHook {
	/**
	 * Adds ImportExport-specific JavaScript
	 *
	 * @param array $configuration
	 * @param \TYPO3\CMS\Backend\Controller\BackendController $backendController
	 * @return void
	 */
	public function addJavaScript(array $configuration, \TYPO3\CMS\Backend\Controller\BackendController $backendController) {
		$backendController->getPageRenderer()->addInlineSetting('ImportExport', 'moduleUrl', BackendUtility::getModuleUrl('xMOD_tximpexp'));
	}
}
