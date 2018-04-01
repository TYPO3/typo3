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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * Get a list of all configured base uris of all sites
     *
     * @return array
     */
    public function getBaseUris(): array
    {
        $baseUrls = [];
        foreach ($this->sites as $site) {
            /** @var SiteLanguage $language */
            foreach ($site->getLanguages() as $language) {
                $baseUrls[$language->getBase()] = $language;
                if ($language->getLanguageId() === 0) {
                    $baseUrls[$site->getBase()] = $language;
                }
            }
        }
        return $baseUrls;
    }

    /**
     * Find a site by given root page id
     *
     * @param int $rootPageId
     * @return Site
     * @throws SiteNotFoundException
     */
    public function getSiteByRootPageId(int $rootPageId): Site
    {
        if (isset($this->mappingRootPageIdToIdentifier[$rootPageId])) {
            return $this->sites[$this->mappingRootPageIdToIdentifier[$rootPageId]];
        }
        throw new SiteNotFoundException('No site found for root page id ' . $rootPageId, 1521668882);
    }

    /**
     * Get a site language by given base URI
     *
     * @param string $uri
     * @return mixed|null
     */
    public function getSiteLanguageByBase(string $uri)
    {
        $baseUris = $this->getBaseUris();
        $bestMatchedUri = null;
        foreach ($baseUris as $base => $language) {
            if (strpos($uri, $base) === 0 && strlen($bestMatchedUri ?? '') < strlen($base)) {
                $bestMatchedUri = $base;
            }
        }
        $siteLanguage = $baseUris[$bestMatchedUri] ?? null;
        if ($siteLanguage instanceof Site) {
            $siteLanguage = $siteLanguage->getLanguageById(0);
        }
        return $siteLanguage;
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
     * @param array $alternativeRootLine
     * @return Site
     * @throws SiteNotFoundException
     */
    public function getSiteByPageId(int $pageId, array $alternativeRootLine = null): Site
    {
        if (is_array($alternativeRootLine)) {
            foreach ($alternativeRootLine as $pageInRootLine) {
                if ($pageInRootLine['uid'] > 0) {
                    try {
                        return $this->getSiteByRootPageId((int)$pageInRootLine['uid']);
                    } catch (SiteNotFoundException $e) {
                        // continue looping
                    }
                }
            }
        }
        // Do your own root line traversing
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('pid')->from('pages');
        $rootLinePageId = $pageId;
        while ($rootLinePageId > 0) {
            try {
                return $this->getSiteByRootPageId($rootLinePageId);
            } catch (SiteNotFoundException $e) {
                // get parent page ID
                $queryBuilder->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($rootLinePageId))
                );
                $rootLinePageId = (int)$queryBuilder->execute()->fetchColumn(0);
            }
        }
        throw new SiteNotFoundException('No site found in root line of page  ' . $pageId, 1521716622);
    }
}
