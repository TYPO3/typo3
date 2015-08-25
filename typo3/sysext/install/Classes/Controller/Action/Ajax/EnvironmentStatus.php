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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status\StatusUtility;
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck;
use TYPO3\CMS\Install\SystemEnvironment\SetupCheck;

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
        // Count of failed checks to be displayed in the left navigation menu
        $statusObjects = array_merge(
            GeneralUtility::makeInstance(Check::class)->getStatus(),
            GeneralUtility::makeInstance(SetupCheck::class)->getStatus(),
            GeneralUtility::makeInstance(DatabaseCheck::class)->getStatus()
        );
        /** @var \TYPO3\CMS\Install\Status\StatusUtility $statusUtility */
        $statusUtility = GeneralUtility::makeInstance(StatusUtility::class);
        $errors = $statusUtility->filterBySeverity($statusObjects, 'error');

        return count($errors);
    }
}
