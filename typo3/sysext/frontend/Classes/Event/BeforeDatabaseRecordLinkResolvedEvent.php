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

namespace TYPO3\CMS\Frontend\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Event dispatched before the DatabaseRecordLinkBuilder resolves a database record.
 *
 * This event is stoppable: If an event listener sets a record, the event propagation
 * will be stopped and the default record retrieval logic will be skipped.
 */
final class BeforeDatabaseRecordLinkResolvedEvent implements StoppableEventInterface
{
    public function __construct(
        public readonly array $linkDetails,
        public readonly string $databaseTable,
        public readonly array $typoscriptConfiguration,
        public readonly array $tsConfig,
        public readonly ServerRequestInterface $request,
        public ?array $record = null
    ) {}

    public function isPropagationStopped(): bool
    {
        return $this->record !== null;
    }
}
