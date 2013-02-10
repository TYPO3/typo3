<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Francois Suter, <francois.suter@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Controller for configuration related actions.
 *
 * @author Francois Suter <francois.suter@typo3.org>
 */
class UpdateScriptController extends \TYPO3\CMS\Extensionmanager\Controller\AbstractController {

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
		$updateScriptUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\UpdateScriptUtility');
		$updateScriptResult = $updateScriptUtility->executeUpdateIfNeeded($extensionKey);
		$this->view
			->assign('updateScriptResult', $updateScriptResult)
			->assign('extensionKey', $extensionKey);
	}
}


?>