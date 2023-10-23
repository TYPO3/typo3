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

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Context\Context;

/**
 * Listeners to this event will be able to modify the page preview
 * URI, which had been generated for a page in the frontend.
 */
final class AfterPagePreviewUriGeneratedEvent
{
    public function __construct(
        private UriInterface $previewUri,
        private readonly int $pageId,
        private readonly int $languageId,
        private readonly array $rootline,
        private readonly string $section,
        private readonly array $additionalQueryParameters,
        private readonly Context $context,
        private readonly array $options
    ) {}

    public function setPreviewUri(UriInterface $previewUri): void
    {
        $this->previewUri = $previewUri;
    }

    public function getPreviewUri(): UriInterface
    {
        return $this->previewUri;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function getRootline(): array
    {
        return $this->rootline;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function getAdditionalQueryParameters(): array
    {
        return $this->additionalQueryParameters;
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
