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

namespace TYPO3Tests\TestMessageHandler\Message;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Bound to two transports via the repeatable AsMessageHandler attribute - must
 * run for messages received from either transport, but not from any other.
 */
#[AsMessageHandler(fromTransport: 'transport_a')]
#[AsMessageHandler(fromTransport: 'transport_b')]
final readonly class KnightsMultiTransportHandler
{
    public function __invoke(Knights $message): void
    {
        $message->names[] = 'Merlin';
    }
}
