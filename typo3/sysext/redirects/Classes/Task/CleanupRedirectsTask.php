<?php

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

namespace TYPO3\CMS\Redirects\Task;

use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Configuration\RedirectCleanupConfiguration;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\RedirectService;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * This class provides a scheduler task implementation to cleanup old redirects.
 * @internal This class is a specific Scheduler task implementation and is not part of the TYPO3's Core API.
 */
class CleanupRedirectsTask extends AbstractTask
{
    /**
     * @var array
     */
    protected $domains = [];

    /**
     * @var array
     */
    protected $statusCodes = [];

    /**
     * @var int
     */
    protected $days = 90;

    /**
     * @var int|null
     */
    protected $hitCount;

    /**
     * @var string|null
     */
    protected $path;

    /**
     * @var RedirectService
     */
    protected $redirectService;

    public function __construct(RedirectService $redirectService = null)
    {
        parent::__construct();
        $this->redirectService = $redirectService ?? GeneralUtility::makeInstance(
            RedirectService::class,
            GeneralUtility::makeInstance(RedirectCacheService::class),
            GeneralUtility::makeInstance(LinkService::class),
            GeneralUtility::makeInstance(SiteFinder::class)
        );
    }

    public function execute()
    {
        $redirectCleanupConfiguration = GeneralUtility::makeInstance(RedirectCleanupConfiguration::class);
        $redirectCleanupConfiguration
            ->setDomains($this->domains)
            ->setStatusCodes($this->statusCodes)
            ->setDays($this->days)
            ->setPath($this->path)
            ->setHitCount($this->hitCount);
        $this->redirectService->cleanupRedirectsByConfiguration($redirectCleanupConfiguration);
        return true;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function setDomains(array $domains): self
    {
        $this->domains = $domains;
        return $this;
    }

    public function getStatusCodes(): array
    {
        return $this->statusCodes;
    }

    public function setStatusCodes(array $statusCodes): self
    {
        $this->statusCodes = $statusCodes;
        return $this;
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function setDays(int $days): self
    {
        $this->days = $days;
        return $this;
    }

    public function getHitCount(): ?int
    {
        return $this->hitCount;
    }

    public function setHitCount(?int $hitCount): self
    {
        $this->hitCount = $hitCount;
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;
        return $this;
    }
}
