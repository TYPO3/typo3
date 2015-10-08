<?php
namespace TYPO3\CMS\Workspaces\Task;

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
 * This class provides a task to cleanup ol preview links.
 */
class CleanupPreviewLinkTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * Cleanup old preview links.
     * endtime < $GLOBALS['EXEC_TIME']
     *
     * @return bool
     */
    public function execute()
    {
        $GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_preview', 'endtime < ' . (int)$GLOBALS['EXEC_TIME']);
        return true;
    }
}
