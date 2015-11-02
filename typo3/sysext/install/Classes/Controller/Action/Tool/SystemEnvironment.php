<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Install\Status\StatusUtility;
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck;

/**
 * Show system environment check results
 */
class SystemEnvironment extends Action\AbstractAction
{
    /**
     * Executes the tool
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        /** @var $statusCheck Check */
        $statusCheck = $this->objectManager->get(Check::class);
        $statusObjects = $statusCheck->getStatus();

        /** @var $statusCheck DatabaseCheck */
        $databaseStatusCheck = $this->objectManager->get(DatabaseCheck::class);
        $statusObjects = array_merge($statusObjects, $databaseStatusCheck->getStatus());

        /** @var $statusUtility StatusUtility */
        $statusUtility = $this->objectManager->get(StatusUtility::class);
        $sortedStatusObjects = $statusUtility->sortBySeverity($statusObjects);
        $this->view->assign('statusObjectsBySeverity', $sortedStatusObjects);

        return $this->view->render();
    }
}
