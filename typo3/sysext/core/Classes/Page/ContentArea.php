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

namespace TYPO3\CMS\Core\Page;

/**
 * Contains information about a specific column in a page layout,
 * including the content elements to be rendered (depending on the context)
 *
 * @internal This is not part of TYPO3 Core API.
 */
final class ContentArea implements \IteratorAggregate
{
    public function __construct(
        private readonly string $identifier,
        private readonly string $name,
        private readonly int $colPos,
        private readonly ContentSlideMode $slideMode,
        private readonly array $allowedContentTypes,
        private readonly array $disallowedContentTypes,
        private readonly array $configuration,
        private array $records,
    ) {}

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColPos(): int
    {
        return $this->colPos;
    }

    public function getSlideMode(): ContentSlideMode
    {
        return $this->slideMode;
    }

    public function getAllowedContentTypes(): array
    {
        return $this->allowedContentTypes;
    }

    public function getDisallowedContentTypes(): array
    {
        return $this->disallowedContentTypes;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @internal Only to be used for AfterContentHasBeenFetchedEvent
     */
    public function withRecords(array $records): self
    {
        $self = clone $this;
        $self->records = $records;
        return $self;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->records);
    }
}
