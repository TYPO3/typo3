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

namespace TYPO3\CMS\Core\Messenger\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Dispatched by the DoctrineCloseConnectionMiddleware before a database connection
 * is closed after a worker handled a received message. Listeners may skip closing a
 * specific connection, for example when it should be kept open across messages.
 */
final class BeforeMessengerDoctrineCloseEvent implements StoppableEventInterface
{
    private bool $skipped = false;

    public function __construct(
        private readonly string $connectionName,
    ) {}

    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    public function skip(): void
    {
        $this->skipped = true;
    }

    public function shouldSkip(): bool
    {
        return $this->skipped;
    }

    public function isPropagationStopped(): bool
    {
        return $this->skipped;
    }
}
