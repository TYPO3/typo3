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

namespace TYPO3\CMS\Workspaces\MessageHandler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TYPO3\CMS\Workspaces\Messages\StageChangeMessage;
use TYPO3\CMS\Workspaces\Notification\StageChangeNotification;

/**
 * @internal
 */
#[AsMessageHandler]
final readonly class StageChangeNotificationHandler
{
    public function __construct(
        private StageChangeNotification $notificationService
    ) {}

    public function __invoke(StageChangeMessage $message): void
    {
        $this->notificationService->notifyStageChange($message);
    }
}
