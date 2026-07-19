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

namespace TYPO3\CMS\Core\Messenger\EventListener;

use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\AddErrorDetailsStampListener as SymfonyAddErrorDetailsStampListener;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Registers the Symfony listener, which is an event subscriber, as PSR-14
 * listener in the order of its subscriber priority.
 *
 * @internal
 */
final readonly class AddErrorDetailsStampListener
{
    private SymfonyAddErrorDetailsStampListener $listener;

    public function __construct()
    {
        $this->listener = new SymfonyAddErrorDetailsStampListener();
    }

    #[AsEventListener(identifier: 'messenger-add-error-details-stamp', before: 'messenger-send-failed-for-retry')]
    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        $this->listener->onMessageFailed($event);
    }
}
