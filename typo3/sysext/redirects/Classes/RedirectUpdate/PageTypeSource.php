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

namespace TYPO3\CMS\Redirects\RedirectUpdate;

final class PageTypeSource implements RedirectSourceInterface
{
    public function __construct(
        private readonly string $host,
        private readonly string $path,
        private readonly int $pageType,
        private readonly array $targetLinkParameters,
    ) {}

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPageType(): int
    {
        return $this->pageType;
    }

    public function getTargetLinkParameters(): array
    {
        return $this->targetLinkParameters;
    }
}
