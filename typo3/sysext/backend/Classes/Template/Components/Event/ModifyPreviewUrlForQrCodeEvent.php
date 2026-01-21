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

namespace TYPO3\CMS\Backend\Template\Components\Event;

/**
 * Listeners to this event are able to modify the preview URL used for QR codes
 * in the backend. This allows extensions to provide alternative URLs, for example
 * workspace-aware preview URLs that work without backend authentication.
 */
final class ModifyPreviewUrlForQrCodeEvent
{
    private ?string $previewUrl = null;

    public function __construct(
        private readonly int $pageId,
        private readonly int $languageId,
        private readonly ?string $fallbackUrl,
    ) {}

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function getFallbackUrl(): ?string
    {
        return $this->fallbackUrl;
    }

    public function getPreviewUrl(): ?string
    {
        return $this->previewUrl ?? $this->fallbackUrl;
    }

    public function setPreviewUrl(?string $previewUrl): void
    {
        $this->previewUrl = $previewUrl;
    }
}
