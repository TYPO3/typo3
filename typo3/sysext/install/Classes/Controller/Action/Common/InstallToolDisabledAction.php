<?php
namespace TYPO3\CMS\Install\Controller\Action\Common;

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

/**
 * Welcome page
 */
class InstallToolDisabledAction extends Action\AbstractAction
{
    /**
     * Executes the action
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        /** @var \TYPO3\CMS\Install\SystemEnvironment\Check $statusCheck */
        $statusCheck = $this->objectManager->get(\TYPO3\CMS\Install\SystemEnvironment\Check::class);
        $statusObjects = $statusCheck->getStatus();
        /** @var \TYPO3\CMS\Install\Status\StatusUtility $statusUtility */
        $statusUtility = $this->objectManager->get(\TYPO3\CMS\Install\Status\StatusUtility::class);
        $alerts = $statusUtility->filterBySeverity($statusObjects, 'alert');
        $this->view->assign('alerts', $alerts);
        return $this->view->render(!empty($alerts));
    }
}
