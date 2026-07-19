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

namespace TYPO3Tests\TestMessageHandler\Failure;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Messenger\Event\ResolveFailureTransportEvent;

/**
 * Overrides the resolved failure transport for the receivers configured in the
 * static $overrides map (receiver name => failure transport name). Inert while
 * the map is empty, so it does not affect other tests.
 */
final class OverrideFailureTransportListener
{
    /**
     * @var array<string, string|null>
     */
    public static array $overrides = [];

    #[AsEventListener]
    public function __invoke(ResolveFailureTransportEvent $event): void
    {
        if (array_key_exists($event->getReceiverName(), self::$overrides)) {
            $event->setFailureTransportName(self::$overrides[$event->getReceiverName()]);
        }
    }
}
