<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Site;

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

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Is used in backend and frontend for all places where to read / identify sites and site languages.
 */
class SiteFinder
{
    /**
     * @var Site[]
     */
    protected $sites = [];

    /**
     * Short-hand to quickly fetch a site based on a rootPageId
     *
     * @var array
     */
    protected $mappingRootPageIdToIdentifier = [];

    /**
     * Fetches all existing configurations as Site objects
     */
    public function __construct()
    {
        $reader = GeneralUtility::makeInstance(SiteConfiguration::class, Environment::getConfigPath() . '/sites');
        $sites = $reader->resolveAllExistingSites();
        foreach ($sites as $identifier => $site) {
            $this->sites[$identifier] = $site;
            $this->mappingRootPageIdToIdentifier[$site->getRootPageId()] = $identifier;
        }
    }

    /**
     * Return a list of all configured sites
     *
     * @return Site[]
     */
    public function getAllSites(): array
    {
        return $this->sites;
    }

    /**
     * Find a site by given root page id
     *
     * @param int $rootPageId the page ID (default language)
     * @return Site
     * @throws SiteNotFoundException
     * @internal only for usage in some places for managing Site Configuration, might be removed without further notice
     */
    public function getSiteByRootPageId(int $rootPageId): Site
    {
        if (isset($this->mappingRootPageIdToIdentifier[$rootPageId])) {
            return $this->sites[$this->mappingRootPageIdToIdentifier[$rootPageId]];
        }
        throw new SiteNotFoundException('No site found for root page id ' . $rootPageId, 1521668882);
    }

    /**
     * Find a site by given identifier
     *
     * @param string $identifier
     * @return Site
     * @throws SiteNotFoundException
     */
    public function getSiteByIdentifier(string $identifier): Site
    {
        if (isset($this->sites[$identifier])) {
            return $this->sites[$identifier];
        }
        throw new SiteNotFoundException('No site found for identifier ' . $identifier, 1521716628);
    }

    /**
     * Traverses the rootline of a page up until a Site was found.
     *
     * @param int $pageId
     * @param array $rootLine
     * @return Site
     * @throws SiteNotFoundException
     */
    public function getSiteByPageId(int $pageId, array $rootLine = null): Site
    {
        if ($pageId === 0) {
            // page uid 0 has no root line. We don't need to ask the root line resolver to know that.
            $rootLine = [];
        }
        if (!is_array($rootLine)) {
            try {
                $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
            } catch (PageNotFoundException $e) {
                // Usually when a page was hidden or disconnected
                // This could be improved by handing in a Context object and decide whether hidden pages
                // Should be linkeable too
                $rootLine = [];
            }
        }
        foreach ($rootLine as $pageInRootLine) {
            if (isset($this->mappingRootPageIdToIdentifier[(int)$pageInRootLine['uid']])) {
                return $this->sites[$this->mappingRootPageIdToIdentifier[(int)$pageInRootLine['uid']]];
            }
        }
        throw new SiteNotFoundException('No site found in root line of page ' . $pageId, 1521716622);
    }
}
