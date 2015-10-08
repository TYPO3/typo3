<?php
namespace TYPO3\CMS\Version\Task;

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
 * This class provides a wrapper around the autopublication
 * mechanism of workspaces, as a Scheduler task
 */
class AutoPublishTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * Method executed from the Scheduler.
     * Call on the workspace logic to publish workspaces whose publication date
     * is in the past
     *
     * @return void
     */
    public function execute()
    {
        // Load the workspace library class and instatiate it
        $autopubObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Version\Utility\WorkspacesUtility::class);
        // Publish the workspaces that need to be
        $autopubObj->autoPublishWorkspaces();
        // There's no feedback from the publishing process,
        // so there can't be any failure.
        // @todo This could certainly be improved.
        return true;
    }
}
