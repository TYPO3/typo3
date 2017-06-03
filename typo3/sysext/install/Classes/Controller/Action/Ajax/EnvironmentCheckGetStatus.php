<?php
declare(strict_types=1);
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
 * Get environment status
 */
class EnvironmentCheckGetStatus extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $statusObjects = array_merge(
            GeneralUtility::makeInstance(Check::class)->getStatus(),
            GeneralUtility::makeInstance(SetupCheck::class)->getStatus(),
            GeneralUtility::makeInstance(DatabaseCheck::class)->getStatus()
        );

        $this->view->assignMultiple([
            'success' => true,
            'status' => (new StatusUtility())->sortBySeverity($statusObjects),
        ]);
        return $this->view->render();
    }
}
