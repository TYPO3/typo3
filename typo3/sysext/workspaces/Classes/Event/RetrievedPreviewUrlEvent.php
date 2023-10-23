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

namespace TYPO3\CMS\Workspaces\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\UriInterface;

/**
 * Used to generate or adjust preview URLs being shown in workspaces backend module.
 */
final class RetrievedPreviewUrlEvent implements StoppableEventInterface
{
    private bool $stopped = false;

    public function __construct(
        private readonly string $tableName,
        private readonly int $uid,
        private ?UriInterface $previewUri,
        private readonly array $contextData
    ) {}

    public function stop(): void
    {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }

    public function getPreviewUri(): ?UriInterface
    {
        return $this->previewUri;
    }

    public function setPreviewUri(UriInterface $previewUri): void
    {
        $this->previewUri = $previewUri;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getContextData(): array
    {
        return $this->contextData;
    }
}
