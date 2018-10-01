<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Compatibility;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Resolves sys_domain entries when a Request object is given,
 * or a pageId is given or a rootpage Id is given (= if there is a sys_domain record on that specific page).
 * Always keeps the sorting in line.
 *
 * @internal this functionality is for compatibility reasons and might be removed in TYPO3 v10.0.
 */
class LegacyDomainResolver implements SingletonInterface
{
    /**
     * Runtime cache of domains per processed page ids.
     *
     * @var array
     */
    protected $domainDataCache = [];

    /**
     * @var string
     */
    protected $cacheIdentifier = 'legacy-domains';

    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * all entries in sys_domain grouped by page (pid)
     * @var array
     */
    protected $groupedDomainsPerPage;

    public function __construct()
    {
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core');
        $this->populate();
    }

    /**
     * Builds up all domain records from DB and all routes
     */
    protected function populate()
    {
        if ($data = $this->cache->get($this->cacheIdentifier)) {
            // Due to the nature of PhpFrontend, the `<?php` and `#` wraps have to be removed
            $data = preg_replace('/^<\?php\s*|\s*#$/', '', $data);
            $this->groupedDomainsPerPage = json_decode($data, true);
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_domain');
            $queryBuilder->getRestrictions()->removeAll();
            $statement = $queryBuilder
                ->select('*')
                ->from('sys_domain')
                ->orderBy('sorting', 'ASC')
                ->execute();

            while ($row = $statement->fetch()) {
                $row['domainName'] = rtrim($row['domainName'], '/');
                $this->groupedDomainsPerPage[(int)$row['pid']][] = $row;
            }

            $this->cache->set($this->cacheIdentifier, json_encode($this->groupedDomainsPerPage));
        }
    }

    /**
     * @return array
     */
    public function getGroupedDomainsPerPage(): array
    {
        return $this->groupedDomainsPerPage ?? [];
    }

    /**
     * Obtains a sys_domain record that fits for a given page ID by traversing the rootline up and finding
     * a suitable page with sys_domain records.
     * As all sys_domains have been fetched already, the internal grouped list of sys_domains can be used directly.
     *
     * Usually used in the Frontend to find out the domain of a page to link to.
     *
     * Includes a runtime cache if a frontend request links to the same page multiple times.
     *
     * @param int $pageId Target page id
     * @param ServerRequestInterface|null $currentRequest if given, the domain record is marked with "isCurrentDomain"
     * @return array|null the sys_domain record if found
     */
    public function matchPageId(int $pageId, ServerRequestInterface $currentRequest = null): ?array
    {
        // Using array_key_exists() here, nice $result can be NULL
        // (happens, if there's no domain records defined)
        if (array_key_exists($pageId, $this->domainDataCache)) {
            return $this->domainDataCache[$pageId];
        }
        try {
            $this->domainDataCache[$pageId] = $this->resolveDomainEntry(
                $pageId,
                $currentRequest
            );
        } catch (RootLineException $e) {
            $this->domainDataCache[$pageId] = null;
        }
        return $this->domainDataCache[$pageId];
    }

    /**
     * Returns the full sys_domain record, based on a page record, which is assumed the "pid" of the sys_domain record.
     * Since ordering is taken into account, this is the first sys_domain record on that page Id.
     *
     * @param int $pageId
     * @return array|null
     */
    public function matchRootPageId(int $pageId): ?array
    {
        return !empty($this->groupedDomainsPerPage[$pageId]) ? reset($this->groupedDomainsPerPage[$pageId]) : null;
    }

    /**
     * @param int $pageId
     * @param ServerRequestInterface|null $currentRequest
     * @return array|null
     */
    protected function resolveDomainEntry(int $pageId, ?ServerRequestInterface $currentRequest): ?array
    {
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
        // walk the rootline downwards from the target page
        // to the root page, until a domain record is found
        foreach ($rootLine as $pageInRootline) {
            $pidInRootline = (int)$pageInRootline['uid'];
            if (empty($this->groupedDomainsPerPage[$pidInRootline])) {
                continue;
            }

            $domainEntriesOfPage = $this->groupedDomainsPerPage[$pidInRootline];
            foreach ($domainEntriesOfPage as $domainEntry) {
                if ($domainEntry['hidden']) {
                    continue;
                }
                // When no currentRequest is given, let's take the first non-hidden sys_domain page
                if ($currentRequest === null) {
                    return $domainEntry;
                }
                // Otherwise the check should match against the current domain (and set "isCurrentDomain")
                // Current domain is "forced", however, otherwise the first one is fine
                if ($this->domainNameMatchesCurrentRequest($domainEntry['domainName'], $currentRequest)) {
                    $result = $domainEntry;
                    $result['isCurrentDomain'] = true;
                    return $result;
                }
            }
        }
        return null;
    }

    /**
     * Whether the given domain name (potentially including a path segment) matches currently requested host or
     * the host including the path segment
     *
     * @param string $domainName
     * @param ServerRequestInterface|null $request
     * @return bool
     */
    protected function domainNameMatchesCurrentRequest($domainName, ServerRequestInterface $request): bool
    {
        /** @var NormalizedParams $normalizedParams */
        $normalizedParams = $request->getAttribute('normalizedParams');
        if (!($normalizedParams instanceof NormalizedParams)) {
            return false;
        }
        $currentDomain = $normalizedParams->getHttpHost();
        // remove the script filename from the path segment.
        $currentPathSegment = trim(preg_replace('|/[^/]*$|', '', $normalizedParams->getScriptName()));
        return $currentDomain === $domainName || $currentDomain . $currentPathSegment === $domainName;
    }
}
