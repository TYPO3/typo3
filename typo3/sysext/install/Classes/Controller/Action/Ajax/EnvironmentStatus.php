<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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
 * Environment status check for errors
 */
class EnvironmentStatus extends AbstractAjaxAction
{
    /**
     * Get environment status errors
     *
     * @return string
     */
    protected function executeAction()
    {
        /** @var \TYPO3\CMS\Install\Status\StatusUtility $statusUtility */
        $statusUtility = $this->objectManager->get(\TYPO3\CMS\Install\Status\StatusUtility::class);

        // Count of failed environment checks to be displayed in the left navigation menu
        $environmentStatus = $this->objectManager->get(\TYPO3\CMS\Install\SystemEnvironment\Check::class)->getStatus();
        $environmentErrors = $statusUtility->filterBySeverity($environmentStatus, 'error');

        // Count of failed database checks to be displayed in the left navigation menu
        $databaseStatus = $this->objectManager->get(\TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck::class)->getStatus();
        $databaseErrors = $statusUtility->filterBySeverity($databaseStatus, 'error');

        return count($environmentErrors) + count($databaseErrors);
    }
}
