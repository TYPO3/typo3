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
    /**
     * @var string
     */
    private $tableName;

    /**
     * @var int
     */
    private $uid;

    /**
     * @var UriInterface|null
     */
    private $previewUri;

    /**
     * @var array
     */
    private $contextData;

    /**
     * @var bool
     */
    private $stopped = false;

    public function __construct(string $tableName, int $uid, ?UriInterface $previewUri, array $contextData)
    {
        $this->tableName = $tableName;
        $this->uid = $uid;
        $this->previewUri = $previewUri;
        $this->contextData = $contextData;
    }

    public function stop(): void
    {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }

    /**
     * @return UriInterface|null
     */
    public function getPreviewUri(): ?UriInterface
    {
        return $this->previewUri;
    }

    /**
     * @param UriInterface $previewUri
     */
    public function setPreviewUri(UriInterface $previewUri): void
    {
        $this->previewUri = $previewUri;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * @return array
     */
    public function getContextData(): array
    {
        return $this->contextData;
    }
}
