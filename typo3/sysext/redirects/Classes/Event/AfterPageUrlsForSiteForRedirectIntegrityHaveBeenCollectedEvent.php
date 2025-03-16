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

namespace TYPO3\CMS\Redirects\Event;

use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * This event is fired in \TYPO3\CMS\Redirects\Service\IntegrityService->getAllPageUrlsForSite() to
 * gather URLs of subpages for a given site.
 */
final class AfterPageUrlsForSiteForRedirectIntegrityHaveBeenCollectedEvent
{
    public function __construct(
        private readonly Site $site,
        private array $pageUrls = [],
    ) {}

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setPageUrls(array $pageUrls): void
    {
        $this->pageUrls = $pageUrls;
    }

    public function getPageUrls(): array
    {
        return $this->pageUrls;
    }
}
