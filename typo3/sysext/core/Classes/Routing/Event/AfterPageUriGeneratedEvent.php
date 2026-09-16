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

namespace TYPO3\CMS\Core\Routing\Event;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

final class AfterPageUriGeneratedEvent
{
    public function __construct(
        private UriInterface $uri,
        private readonly array|string|int|Page $route,
        private readonly array $parameters,
        private readonly string $fragment,
        private readonly string $type,
        private readonly SiteLanguage $language,
        private readonly Site $site,
        private readonly int $pageId,
        private readonly PageArguments $pageArguments,
    ) {}

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function setUri(UriInterface $uri): void
    {
        $this->uri = $uri;
    }

    public function getRoute(): array|string|int|Page
    {
        return $this->route;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLanguage(): SiteLanguage
    {
        return $this->language;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getPageArguments(): PageArguments
    {
        return $this->pageArguments;
    }
}
