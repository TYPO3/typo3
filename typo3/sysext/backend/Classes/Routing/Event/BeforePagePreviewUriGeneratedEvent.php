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

namespace TYPO3\CMS\Backend\Routing\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Context\Context;

/**
 * Listeners to this event will be able to modify the corresponding parameters, before
 * the page preview URI is being generated, when linking to a page in the frontend.
 */
final class BeforePagePreviewUriGeneratedEvent implements StoppableEventInterface
{
    private ?UriInterface $uri = null;

    public function __construct(
        private int $pageId,
        private int $languageId,
        private array $rootline,
        private string $section,
        private array $additionalQueryParameters,
        private readonly Context $context,
        private readonly array $options
    ) {
    }

    public function setPreviewUri(UriInterface $uri): void
    {
        $this->uri = $uri;
    }

    public function getPreviewUri(): ?UriInterface
    {
        return $this->uri;
    }

    public function isPropagationStopped(): bool
    {
        return $this->uri !== null;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function setPageId(int $pageId): void
    {
        $this->pageId = $pageId;
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function setLanguageId(int $languageId): void
    {
        $this->languageId = $languageId;
    }

    public function getRootline(): array
    {
        return $this->rootline;
    }

    public function setRootline(array $rootline): void
    {
        $this->rootline = $rootline;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function setSection(string $section): void
    {
        $this->section = $section;
    }

    public function getAdditionalQueryParameters(): array
    {
        return $this->additionalQueryParameters;
    }

    public function setAdditionalQueryParameters(array $additionalQueryParameters): void
    {
        $this->additionalQueryParameters = $additionalQueryParameters;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
