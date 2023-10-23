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

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * This class contains data on the journey through the auto-create-redirects
 * path. It may be also included as information in a PSR-14 event, if needed.
 *
 * @internal This class is a specific data container for slug service handling and is not part of the public TYPO3 API.
 */
final class SlugRedirectChangeItem
{
    public function __construct(
        private readonly int $defaultLanguagePageId,
        private readonly int $pageId,
        private readonly Site $site,
        private readonly SiteLanguage $siteLanguage,
        private readonly array $original,
        private readonly RedirectSourceCollection $sourcesCollection,
        private readonly ?array $changed = null,
    ) {}

    public function getDefaultLanguagePageId(): int
    {
        return $this->defaultLanguagePageId;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getOriginal(): array
    {
        return $this->original;
    }

    public function getChanged(): ?array
    {
        return $this->changed;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function getSiteLanguage(): SiteLanguage
    {
        return $this->siteLanguage;
    }

    public function getSourcesCollection(): RedirectSourceCollection
    {
        return $this->sourcesCollection;
    }

    public function withChanged(array $changed): self
    {
        return new self(
            defaultLanguagePageId: $this->defaultLanguagePageId,
            pageId: $this->pageId,
            site: $this->site,
            siteLanguage: $this->siteLanguage,
            original: $this->original,
            sourcesCollection: $this->sourcesCollection,
            changed: $changed,
        );
    }

    public function withSourcesCollection(RedirectSourceCollection $sourcesCollection): self
    {
        return new self(
            defaultLanguagePageId: $this->defaultLanguagePageId,
            pageId: $this->pageId,
            site: $this->site,
            siteLanguage: $this->siteLanguage,
            original: $this->original,
            sourcesCollection: $sourcesCollection,
            changed: $this->changed,
        );
    }
}
