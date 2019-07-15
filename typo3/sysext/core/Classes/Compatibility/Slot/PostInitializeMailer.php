<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Compatibility\Slot;

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

use TYPO3\CMS\Core\Mail\Event\AfterMailerInitializationEvent;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Bridge to provide backwards-compatibility for executing the SignalSlot dispatcher event.
 */
class PostInitializeMailer
{
    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function __invoke(AfterMailerInitializationEvent $event): void
    {
        $this->dispatcher->dispatch(Mailer::class, 'postInitializeMailer', [$event->getMailer()]);
    }
}
