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

namespace TYPO3\CMS\Core\Configuration;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader;
use TYPO3\CMS\Core\Configuration\Parser\PageTsConfigParser;
use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\ConditionMatcherInterface;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Main entry point for fetching page TSconfig for frontend and backend.
 *
 * @deprecated since TYPO3 v12, will be removed with v13. Use PageTsConfigFactory instead.
 *             When removing, also remove entries in core Services.yaml and usage in TypoScriptFrontendController.
 */
class PageTsConfig
{
    protected FrontendInterface $cache;
    protected PageTsConfigLoader $loader;
    protected PageTsConfigParser $parser;

    public function __construct(FrontendInterface $cache, PageTsConfigLoader $loader, PageTsConfigParser $parser)
    {
        trigger_error('Class ' . __CLASS__ . ' will be removed with TYPO3 v13.0. Use PageTsConfigFactory instead.', E_USER_DEPRECATED);
        $this->cache = $cache;
        $this->loader = $loader;
        $this->parser = $parser;
    }

    /**
     * Load, parse and match all page TSconfig for a given page from a root line.
     */
    public function getForRootLine(array $rootLine, ?Site $site, ConditionMatcherInterface $conditionMatcher): array
    {
        return $this->parser->parse(
            $this->loader->load($rootLine),
            $conditionMatcher,
            $site
        );
    }

    /**
     * Fetch and compile all page TSconfig for a given page from a root line,
     * but also overloads user-specific "page." properties which is possible too.
     *
     * This then caches a specific version away during runtime to avoid multiple overloads.
     */
    public function getWithUserOverride(int $pageId, array $rootLine, ?Site $site, ConditionMatcherInterface $conditionMatcher, BackendUserAuthentication $user = null): array
    {
        $pagesTsConfigIdToHash = $this->cache->get('pagesTsConfigIdToHash' . $pageId);
        if ($pagesTsConfigIdToHash !== false) {
            return $this->cache->get('pagesTsConfigHashToContent' . $pagesTsConfigIdToHash);
        }

        $tsConfig = $this->getForRootLine($rootLine, $site, $conditionMatcher);
        $cacheHash = md5((string)json_encode($tsConfig));

        // Get user TSconfig overlay, if no backend user is logged-in, this needs to be checked as well
        if ($user) {
            $userTsConfig = $user->getTSConfig()['page.'] ?? [];
            if (!empty($userTsConfig)) {
                // Override page TSconfig with user TSconfig
                $tsConfig = array_replace_recursive($tsConfig, $userTsConfig);
                $cacheHash .= '_user' . $user->user['uid'];
            }
        }

        // Many pages end up with the same TsConfig. To reduce memory usage, the cache
        // entries are a linked list: One or more pids point to content hashes which then
        // contain the cached content.
        $this->cache->set('pagesTsConfigHashToContent' . $cacheHash, $tsConfig, ['pagesTsConfig']);
        $this->cache->set('pagesTsConfigIdToHash' . $pageId, $cacheHash, ['pagesTsConfig']);
        return $tsConfig;
    }
}
