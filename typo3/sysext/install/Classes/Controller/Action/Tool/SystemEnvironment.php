<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Install\Controller\Action;

/**
 * Show system environment check results
 */
class SystemEnvironment extends Action\AbstractAction {

	/**
	 * Executes the tool
	 *
	 * @return string Rendered content
	 */
	protected function executeAction() {
		/** @var $statusCheck \TYPO3\CMS\Install\SystemEnvironment\Check */
		$statusCheck = $this->objectManager->get('TYPO3\\CMS\\Install\\SystemEnvironment\\Check');
		$statusObjects = $statusCheck->getStatus();

		/** @var $statusUtility \TYPO3\CMS\Install\Status\StatusUtility */
		$statusUtility = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\StatusUtility');
		$sortedStatusObjects = $statusUtility->sortBySeverity($statusObjects);
		$this->view->assign('statusObjectsBySeverity', $sortedStatusObjects);

		return $this->view->render();
	}

}
