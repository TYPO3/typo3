<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Redirects\EventListener;

use TYPO3\CMS\Backend\History\Event\AfterHistoryRollbackFinishedEvent;
use TYPO3\CMS\Backend\History\Event\BeforeHistoryRollbackStartEvent;
use TYPO3\CMS\Redirects\Hooks\DataHandlerSlugUpdateHook;

class RecordHistoryRollbackEventsListener
{
    public function afterHistoryRollbackFinishedEvent(AfterHistoryRollbackFinishedEvent $event): void
    {
        // Re-Enable hook to after rollback finished
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['redirects'] =
            DataHandlerSlugUpdateHook::class;
    }

    public function beforeHistoryRollbackStartEvent(BeforeHistoryRollbackStartEvent $event): void
    {
        // Disable hook to prevent slug change again on rollback
        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['redirects']);
    }
}
