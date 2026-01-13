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

namespace TYPO3\CMS\Redirects\Data;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Data provider for source hosts in sys_redirect records
 *
 * @internal
 */
final readonly class SourceHostProvider
{
    public function __construct(
        private SiteFinder $siteFinder,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $cache,
    ) {}

    /**
     * Get all available hosts for current backend user.
     *
     * @return list<non-empty-string>
     */
    public function getHosts(bool $includeWildcard = false): array
    {
        $cacheIdentifier = 'RedirectsSourceHostProvider' . ($includeWildcard ? '-wildcard' : '');

        if (!$this->cache->has($cacheIdentifier)) {
            $this->cache->set($cacheIdentifier, $this->filterAllowedSourceHosts($includeWildcard));
        }

        return $this->cache->get($cacheIdentifier);
    }

    /**
     * @return list<non-empty-string>
     */
    private function filterAllowedSourceHosts(bool $includeWildcard): array
    {
        $backendUser = $this->getBackendUser();

        if ($includeWildcard) {
            $hosts = ['*'];
        } else {
            $hosts = [];
        }

        if ($backendUser->isAdmin()) {
            foreach ($this->siteFinder->getAllSites() as $site) {
                foreach ($site->getAllLanguages() as $language) {
                    $host = $language->getBase()->getHost();

                    if ($host !== '' && !in_array($host, $hosts, true)) {
                        $hosts[] = $host;
                    }
                }
            }
        } else {
            foreach ($backendUser->getWebmounts() as $pageId) {
                try {
                    $site = $this->siteFinder->getSiteByPageId($pageId);

                    foreach ($site->getAvailableLanguages($backendUser) as $language) {
                        $host = $language->getBase()->getHost();

                        if ($host !== '' && !in_array($host, $hosts, true)) {
                            $hosts[] = $host;
                        }
                    }
                } catch (SiteNotFoundException) {
                    // Ignore unavailable sites
                }
            }
        }

        sort($hosts, SORT_NATURAL);

        return $hosts;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
