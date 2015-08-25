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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Install\Status\StatusUtility;
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\CMS\Install\SystemEnvironment\SetupCheck;

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
        $statusObjects = array_merge(
            GeneralUtility::makeInstance(Check::class)->getStatus(),
            GeneralUtility::makeInstance(SetupCheck::class)->getStatus()
        );
        /** @var \TYPO3\CMS\Install\Status\StatusUtility $statusUtility */
        $statusUtility = GeneralUtility::makeInstance(StatusUtility::class);
        $alerts = $statusUtility->filterBySeverity($statusObjects, 'alert');
        $this->view->assign('alerts', $alerts);

        return $this->view->render();
    }
}
