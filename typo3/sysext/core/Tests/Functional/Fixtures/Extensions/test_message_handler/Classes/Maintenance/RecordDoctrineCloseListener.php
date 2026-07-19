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

namespace TYPO3Tests\TestMessageHandler\Maintenance;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Messenger\Event\BeforeMessengerDoctrineCloseEvent;

final class RecordDoctrineCloseListener
{
    #[AsEventListener(identifier: 'test-message-handler/record-doctrine-close')]
    public function __invoke(BeforeMessengerDoctrineCloseEvent $event): void
    {
        DoctrineMaintenanceRecorder::$closedConnections[] = $event->getConnectionName();
    }
}
