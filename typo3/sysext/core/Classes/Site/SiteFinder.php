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

namespace TYPO3\CMS\Core\Site;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationChangedEvent;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Is used in backend and frontend for all places where to read / identify sites and site languages.
 */
readonly class SiteFinder
{
    private const CACHE_IDENTIFIER_ROOT_ID_TO_IDENTIFIER = 'sites-root-id-to-identifier';

    public function __construct(
        private SiteConfiguration $siteConfiguration,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
    ) {}

    /**
     * Return a list of all configured sites
     *
     * @return Site[]
     */
    public function getAllSites(bool $useCache = true): array
    {
        return $this->siteConfiguration->getAllExistingSites($useCache);
    }

    /**
     * Find a site by given root page id
     *
     * @param int $rootPageId the page ID (default language)
     * @throws SiteNotFoundException
     * @internal only for usage in some places for managing Site Configuration, might be removed without further notice
     */
    public function getSiteByRootPageId(int $rootPageId): Site
    {
        $mapping = $this->getRootPageIdToIdentifierMapping();
        $sites = $this->siteConfiguration->getAllExistingSites();
        if (isset($mapping[$rootPageId]) && $sites[$mapping[$rootPageId]] instanceof Site) {
            return $sites[$mapping[$rootPageId]];
        }
        throw new SiteNotFoundException('No site found for root page id ' . $rootPageId, 1521668882);
    }

    /**
     * Find a site by given identifier
     *
     * @throws SiteNotFoundException
     */
    public function getSiteByIdentifier(string $identifier): Site
    {
        $sites = $this->siteConfiguration->getAllExistingSites();
        if (isset($sites[$identifier])) {
            return $sites[$identifier];
        }
        throw new SiteNotFoundException('No site found for identifier ' . $identifier, 1521716628);
    }

    /**
     * Traverses the rootline of a page up until a Site was found.
     *
     * @param string|null $mountPointParameter
     * @throws SiteNotFoundException
     */
    public function getSiteByPageId(int $pageId, ?array $rootLine = null, ?string $mountPointParameter = null): Site
    {
        if ($pageId === 0) {
            // page uid 0 has no root line. We don't need to ask the root line resolver to know that.
            $rootLine = [];
        }
        if (!is_array($rootLine)) {
            try {
                $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId, (string)$mountPointParameter)->get();
            } catch (PageNotFoundException) {
                // Usually when a page was hidden or disconnected
                // This could be improved by handing in a Context object and decide whether hidden pages
                // Should be linkable too
                $rootLine = [];
            }
        }
        $sites = $this->siteConfiguration->getAllExistingSites();
        $mapping = $this->getRootPageIdToIdentifierMapping();
        foreach ($rootLine as $pageInRootLine) {
            if (isset($mapping[(int)$pageInRootLine['uid']]) && $sites[$mapping[(int)$pageInRootLine['uid']]] instanceof Site) {
                return $sites[$mapping[(int)$pageInRootLine['uid']]];
            }
        }
        throw new SiteNotFoundException('No site found in root line of page ' . $pageId, 1521716622);
    }

    #[AsEventListener(event: SiteConfigurationChangedEvent::class)]
    public function siteConfigurationChanged(): void
    {
        $this->runtimeCache->remove(self::CACHE_IDENTIFIER_ROOT_ID_TO_IDENTIFIER);
    }

    private function getRootPageIdToIdentifierMapping(): array
    {
        $mapping = $this->runtimeCache->get(self::CACHE_IDENTIFIER_ROOT_ID_TO_IDENTIFIER);
        if (is_array($mapping)) {
            return $mapping;
        }
        $sites = $this->siteConfiguration->getAllExistingSites();
        $mapping = [];
        foreach ($sites as $identifier => $site) {
            $mapping[$site->getRootPageId()] = $identifier;
        }
        $this->runtimeCache->set(self::CACHE_IDENTIFIER_ROOT_ID_TO_IDENTIFIER, $mapping);
        return $mapping;
    }
}
