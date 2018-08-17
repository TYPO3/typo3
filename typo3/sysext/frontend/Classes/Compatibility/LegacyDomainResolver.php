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
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
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
 * @todo: would be nice to flush caches if sys_domain has been touched in DataHandler
 * @internal as this should ideally be wrapped inside the "main" site router in the future.
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
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * Whether a sys_domain like example.com should also match for my.blog.example.com
     *
     * @var bool
     */
    protected $recursiveDomainSearch;

    /**
     * @var RouteCollection
     */
    protected $routeCollection;

    /**
     * all entries in sys_domain
     * @var array
     */
    protected $allDomainRecords;

    /**
     * all entries in sys_domain grouped by page (pid)
     * @var array
     */
    protected $groupedDomainsPerPage;

    public function __construct()
    {
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core');
        $this->recursiveDomainSearch = (bool)$GLOBALS['TYPO3_CONF_VARS']['SYS']['recursiveDomainSearch'];
        $this->routeCollection = new RouteCollection();
        $this->populate();
    }

    /**
     * Builds up all domain records from DB and all routes
     */
    protected function populate()
    {
        if ($data = $this->cache->get('legacy-domains')) {
            // Due to the nature of PhpFrontend, the `<?php` and `#` wraps have to be removed
            $data = preg_replace('/^<\?php\s*|\s*#$/', '', $data);
            $data = unserialize($data, ['allowed_classes' => [Route::class, RouteCollection::class]]);
            $this->routeCollection = $data['routeCollection'];
            $this->allDomainRecords = $data['allDomainRecords'];
            $this->groupedDomainsPerPage = $data['groupedDomainsPerPage'];
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
                $this->allDomainRecords[(int)$row['uid']] = $row;
                $this->groupedDomainsPerPage[(int)$row['pid']][] = $row;
                if (!$row['hidden']) {
                    if (strpos($row['domainName'], '/') === false) {
                        $path = '';
                        list($host, $port) = explode(':', $row['domainName']);
                    } else {
                        $urlParts = parse_url($row['domainName']);
                        $path = trim($urlParts['path'], '/');
                        $host = $urlParts['host'];
                        $port = (string)$urlParts['port'];
                    }
                    $route = new Route(
                        $path . '/{next}',
                        ['pageId' => $row['pid']],
                        array_filter(['next' => '.*', 'port' => $port]),
                        ['utf8' => true],
                        $host ?? ''
                    );
                    $this->routeCollection->add('domain_' . $row['uid'], $route);
                }
            }

            $data = [
                'routeCollection' => $this->routeCollection,
                'allDomainRecords' => $this->allDomainRecords,
                'groupedDomainsPerPage' => $this->groupedDomainsPerPage
            ];
            $this->cache->set('legacy-domains', serialize($data), ['sys_domain'], 0);
        }
    }

    /**
     * Return the page ID (pid) of a sys_domain record, based on a request object, does the infamous
     * "recursive domain search", to also detect if the domain is like "abc.def.example.com" even if the
     * sys_domain entry is "example.com".
     *
     * @param ServerRequestInterface $request
     * @return int page ID
     */
    public function matchRequest(ServerRequestInterface $request): int
    {
        if (empty($this->allDomainRecords) || count($this->routeCollection) === 0) {
            return 0;
        }
        $context = new RequestContext('/', $request->getMethod(), $request->getUri()->getHost());
        $matcher = new UrlMatcher($this->routeCollection, $context);
        if ($this->recursiveDomainSearch) {
            $pageUid = 0;
            $host = explode('.', $request->getUri()->getHost());
            while (count($host)) {
                $context->setHost(implode('.', $host));
                try {
                    $result = $matcher->match($request->getUri()->getPath());
                    return (int)$result['pageId'];
                } catch (NoConfigurationException | ResourceNotFoundException $e) {
                    array_shift($host);
                }
            }
            return $pageUid;
        }
        try {
            $result = $matcher->match($request->getUri()->getPath());
            return (int)$result['pageId'];
        } catch (NoConfigurationException | ResourceNotFoundException $e) {
            // No domain record found
        }
        return 0;
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
            $pidInRootline = $pageInRootline['uid'];
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
