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
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
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
     * Get all available hosts for current backend user, as resolved in the current context.
     *
     * @return list<non-empty-string>
     */
    public function getHosts(bool $includeWildcard = false): array
    {
        $cacheIdentifier = 'RedirectsSourceHostProvider' . ($includeWildcard ? '-wildcard' : '');

        if (!$this->cache->has($cacheIdentifier)) {
            $this->cache->set($cacheIdentifier, $this->filterAllowedSourceHosts($includeWildcard, false));
        }

        return $this->cache->get($cacheIdentifier);
    }

    /**
     * Get the hosts of all bases and base variants of the sites the current backend user
     * has access to, including the wildcard host, regardless of the variant resolved in
     * the current context.
     *
     * @return list<non-empty-string>
     */
    public function getAccessibleHosts(): array
    {
        $cacheIdentifier = 'RedirectsSourceHostProvider-accessible';

        if (!$this->cache->has($cacheIdentifier)) {
            $this->cache->set($cacheIdentifier, $this->filterAllowedSourceHosts(true, true));
        }

        return $this->cache->get($cacheIdentifier);
    }

    /**
     * @return list<non-empty-string>
     */
    private function filterAllowedSourceHosts(bool $includeWildcard, bool $includeAllBaseVariants): array
    {
        $backendUser = $this->getBackendUser();

        if ($includeWildcard) {
            $hosts = ['*'];
        } else {
            $hosts = [];
        }

        if ($backendUser->isAdmin()) {
            foreach ($this->siteFinder->getAllSites() as $site) {
                $hosts = $this->addSiteHosts($hosts, $site, $site->getAllLanguages(), $includeAllBaseVariants);
            }
        } else {
            foreach ($backendUser->getWebmounts() as $pageId) {
                try {
                    $site = $this->siteFinder->getSiteByPageId($pageId);
                    $hosts = $this->addSiteHosts($hosts, $site, $site->getAvailableLanguages($backendUser), $includeAllBaseVariants);
                } catch (SiteNotFoundException) {
                    // Ignore unavailable sites
                }
            }
        }

        sort($hosts, SORT_NATURAL);

        return $hosts;
    }

    /**
     * Adds the hosts of the bases of the given languages as resolved for the current
     * request, and optionally the hosts of all their bases and base variants.
     *
     * @param list<non-empty-string> $hosts
     * @param SiteLanguage[] $languages
     * @return list<non-empty-string>
     */
    private function addSiteHosts(array $hosts, Site $site, array $languages, bool $includeAllBaseVariants): array
    {
        $siteConfiguration = $site->getConfiguration();
        $siteBases = [
            $siteConfiguration['base'] ?? '',
            ...array_column($siteConfiguration['baseVariants'] ?? [], 'base'),
        ];
        $languageConfigurations = array_column($siteConfiguration['languages'] ?? [], null, 'languageId');

        foreach ($languages as $language) {
            $bases = [(string)$language->getBase()];
            $languageConfiguration = $languageConfigurations[$language->getLanguageId()] ?? [];
            $languageBases = $includeAllBaseVariants ? [
                $languageConfiguration['base'] ?? '',
                ...array_column($languageConfiguration['baseVariants'] ?? [], 'base'),
            ] : [];
            foreach ($languageBases as $languageBase) {
                if ($this->getHostFromBase((string)$languageBase) === '') {
                    // Language bases without host are prefixed with the site base
                    $bases = [...$bases, ...$siteBases];
                } else {
                    $bases[] = $languageBase;
                }
            }

            foreach ($bases as $base) {
                $host = $this->getHostFromBase((string)$base);

                if ($host !== '' && !in_array($host, $hosts, true)) {
                    $hosts[] = $host;
                }
            }
        }

        return $hosts;
    }

    private function getHostFromBase(string $base): string
    {
        // Bases like "www.domain.com/subpage" are treated as domain, see Site::sanitizeBaseUrl()
        if ($base !== '' && !str_contains($base, '//') && $base[0] !== '/' && str_contains($base, '.')) {
            $base = '//' . $base;
        }

        return (string)parse_url($base, PHP_URL_HOST);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
