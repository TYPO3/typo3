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

namespace TYPO3\CMS\Frontend\Middleware;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Locking\ResourceMutex;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Cache\MetaDataState;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Event\AfterTypoScriptDeterminedEvent;
use TYPO3\CMS\Frontend\Event\BeforePageCacheIdentifierIsHashedEvent;
use TYPO3\CMS\Frontend\Event\ShouldUseCachedPageDataIfAvailableEvent;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Initialize TypoScript, get page content from cache if possible, lock
 * rendering if needed and create more TypoScript data if needed.
 *
 * @internal this middleware might get removed later.
 */
final readonly class PrepareTypoScriptFrontendRendering implements MiddlewareInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private FrontendTypoScriptFactory $frontendTypoScriptFactory,
        #[Autowire(service: 'cache.typoscript')]
        private PhpFrontend $typoScriptCache,
        #[Autowire(service: 'cache.pages')]
        private FrontendInterface $pageCache,
        private ResourceMutex $lock,
        private Context $context,
        private LoggerInterface $logger,
        private ErrorController $errorController,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        $sysTemplateRows = $request->getAttribute('frontend.page.information')->getSysTemplateRows();
        $isCachingAllowed = $request->getAttribute('frontend.cache.instruction')->isCachingAllowed();

        // Create FrontendTypoScript with essential info for page cache identifier
        $conditionMatcherVariables = $this->prepareConditionMatcherVariables($request);
        $frontendTypoScript = $this->frontendTypoScriptFactory->createSettingsAndSetupConditions(
            $site,
            $sysTemplateRows,
            $conditionMatcherVariables,
            $isCachingAllowed ? $this->typoScriptCache : null,
        );

        $isUsingPageCacheAllowed = $this->eventDispatcher
            ->dispatch(new ShouldUseCachedPageDataIfAvailableEvent($request, $isCachingAllowed))
            ->shouldUseCachedPageData();
        $pageCacheIdentifier = $this->createPageCacheIdentifier($request, $frontendTypoScript);

        $pageCacheRow = null;
        if (!$isUsingPageCacheAllowed) {
            // Caching is not allowed. We'll rebuild the page. Lock this.
            $this->lock->acquireLock('pages', $pageCacheIdentifier);
        } else {
            // Try to get a page cache row.
            $pageCacheRow = $this->pageCache->get($pageCacheIdentifier);
            if (!is_array($pageCacheRow)) {
                // Nothing in the cache, we acquire an exclusive lock now.
                // There are two scenarios when locking: We're either the first process acquiring this lock. This means we'll
                // "immediately" get it and can continue with page rendering. Or, another process acquired the lock already. In
                // this case, the below call will wait until the lock is released again. The other process then probably wrote
                // a page cache entry, which we can use.
                // To handle the second case - if our process had to wait for another one creating the content for us - we
                // simply query the page cache again to see if there is a page cache now.
                $hadToWaitForLock = $this->lock->acquireLock('pages', $pageCacheIdentifier);
                // From this point on we're the only one working on that page.
                if ($hadToWaitForLock) {
                    // Query the cache again to see if the data is there meanwhile: We did not get the lock
                    // immediately, chances are high the other process created a page cache for us.
                    // There is a small chance the other process actually pageCache->set() the content,
                    // but pageCache->get() still returns false, for instance when a database returned "done"
                    // for the INSERT, but SELECT still does not return the new row - may happen in multi-head
                    // DB instances, and with some other distributed cache backends as well. The worst that
                    // can happen here is the page generation is done too often, which we accept as trade-off.
                    $pageCacheRow = $this->pageCache->get($pageCacheIdentifier);
                    if (is_array($pageCacheRow)) {
                        // We have the content, some other process did the work for us, release our lock again.
                        $this->lock->releaseLock('pages');
                    }
                }
                // Keep the lock set, because we are the ones generating the page now and filling the cache.
            }
        }

        $controller = $request->getAttribute('frontend.controller');
        $controller->newHash = $pageCacheIdentifier;
        $pageContentWasLoadedFromCache = false;
        if (is_array($pageCacheRow)) {
            $controller->config['INTincScript'] = $pageCacheRow['INTincScript'];
            $controller->config['INTincScript_ext'] = $pageCacheRow['INTincScript_ext'];
            $controller->config['pageTitleCache'] = $pageCacheRow['pageTitleCache'];
            $pageParts = $request->getAttribute('frontend.page.parts');
            $pageParts->setContent($pageCacheRow['content']);
            $pageParts->setHttpContentType($pageCacheRow['contentType']);
            $controller->cacheGenerated = $pageCacheRow['tstamp'];
            $controller->pageContentWasLoadedFromCache = true;
            $pageContentWasLoadedFromCache = true;

            // Restore the current tags and add them to the CacheTageCollector
            $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
            $lifetime = $pageCacheRow['expires'] - $GLOBALS['EXEC_TIME'];
            $cacheTags = array_map(fn(string $cacheTag) => new CacheTag($cacheTag, $lifetime), $pageCacheRow['cacheTags'] ?? []);
            $cacheDataCollector->addCacheTags(...$cacheTags);

            // Restore meta-data state
            if (is_array($pageCacheRow['metaDataState'] ?? null)) {
                GeneralUtility::makeInstance(MetaDataState::class)->updateState($pageCacheRow['metaDataState']);
            }
        }

        try {
            $needsFullSetup = !$pageContentWasLoadedFromCache || $controller->isINTincScript();
            $pageType = $request->getAttribute('routing')->getPageType();
            $frontendTypoScript = $this->frontendTypoScriptFactory->createSetupConfigOrFullSetup(
                $needsFullSetup,
                $frontendTypoScript,
                $site,
                $sysTemplateRows,
                $conditionMatcherVariables,
                $pageType,
                $isCachingAllowed ? $this->typoScriptCache : null,
                $request,
            );
            if ($needsFullSetup && !$frontendTypoScript->hasPage()) {
                $this->logger->error('No page configured for type={type}. There is no TypoScript object of type PAGE with typeNum={type}.', ['type' => $pageType]);
                return $this->errorController->internalErrorAction(
                    $request,
                    'No page configured for type=' . $pageType . '.',
                    ['code' => PageAccessFailureReasons::RENDERING_INSTRUCTIONS_NOT_CONFIGURED]
                );
            }
            $setupConfigAst = $frontendTypoScript->getConfigTree();
            if ($pageContentWasLoadedFromCache && ($setupConfigAst->getChildByName('debug')?->getValue() || !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']))) {
                // Prepare X-TYPO3-Debug-Cache HTTP header
                $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
                $timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
                $controller->debugInformationHeader = 'Cached page generated ' . date($dateFormat . ' ' . $timeFormat, $controller->cacheGenerated)
                    . '. Expires ' . date($dateFormat . ' ' . $timeFormat, $pageCacheRow['expires']);
            }
            if ($setupConfigAst->getChildByName('no_cache')?->getValue()) {
                // Disable cache if config.no_cache is set!
                $cacheInstruction = $request->getAttribute('frontend.cache.instruction');
                $cacheInstruction->disableCache('EXT:frontend: Disabled cache due to TypoScript "config.no_cache = 1"');
            }
            $this->eventDispatcher->dispatch(new AfterTypoScriptDeterminedEvent($frontendTypoScript));
            $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);

            // b/w compat
            $GLOBALS['TYPO3_REQUEST'] = $request;

            $response = $handler->handle($request);
        } finally {
            // Whatever happens in a below middleware, this finally is called, even when exceptions
            // are raised by a lower middleware. This ensures locks are released no matter what.
            $this->lock->releaseLock('pages');
        }

        return $response;
    }

    /**
     * Data available in TypoScript "condition" matching.
     */
    private function prepareConditionMatcherVariables(ServerRequestInterface $request): array
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $topDownRootLine = $pageInformation->getRootLine();
        $localRootline = $pageInformation->getLocalRootLine();
        ksort($topDownRootLine);
        return [
            'request' => $request,
            'pageId' => $pageInformation->getId(),
            'page' => $pageInformation->getPageRecord(),
            'fullRootLine' => $topDownRootLine,
            'localRootLine' => $localRootline,
            'site' => $request->getAttribute('site'),
            'siteLanguage' => $request->getAttribute('language'),
            'tsfe' => $request->getAttribute('frontend.controller'),
        ];
    }

    /**
     * This creates a hash used as page cache entry identifier and as page generation lock.
     * When multiple requests try to render the same page that will result in the same page cache entry,
     * this lock allows creation by one request which typically puts the result into page cache, while
     * the other requests wait until this finished and re-use the result.
     */
    private function createPageCacheIdentifier(ServerRequestInterface $request, FrontendTypoScript $frontendTypoScript): string
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageId = $pageInformation->getId();
        $pageArguments = $request->getAttribute('routing');
        $site = $request->getAttribute('site');

        $dynamicArguments = [];
        $queryParams = $pageArguments->getDynamicArguments();
        if (!empty($queryParams) && ($pageArguments->getArguments()['cHash'] ?? false)) {
            // Fetch arguments relevant for creating the page cache identifier from the PageArguments object.
            // Excluded parameters are not taken into account when calculating the hash base.
            $queryParams['id'] = $pageArguments->getPageId();
            // @todo: Make CacheHashCalculator and CacheHashConfiguration stateless and get it injected.
            $dynamicArguments = GeneralUtility::makeInstance(CacheHashCalculator::class)
                ->getRelevantParameters(HttpUtility::buildQueryString($queryParams));
        }

        $pageCacheIdentifierParameters = [
            'id' => $pageId,
            'type' => $pageArguments->getPageType(),
            'groupIds' => implode(',', $this->context->getAspect('frontend.user')->getGroupIds()),
            'MP' => $pageInformation->getMountPoint(),
            'site' => $site->getIdentifier(),
            // Ensure the language base is used for the hash base calculation as well, otherwise TypoScript and page-related rendering
            // is not cached properly as we don't have any language-specific conditions anymore
            'siteBase' => (string)$request->getAttribute('language', $site->getDefaultLanguage())->getBase(),
            // additional variation trigger for static routes
            'staticRouteArguments' => $pageArguments->getStaticArguments(),
            // dynamic route arguments (if route was resolved)
            'dynamicArguments' => $dynamicArguments,
            'sysTemplateRows' => $pageInformation->getSysTemplateRows(),
            'constantConditionList' => $frontendTypoScript->getSettingsConditionList(),
            'setupConditionList' => $frontendTypoScript->getSetupConditionList(),
        ];
        $pageCacheIdentifierParameters = $this->eventDispatcher
            ->dispatch(new BeforePageCacheIdentifierIsHashedEvent($request, $pageCacheIdentifierParameters))
            ->getPageCacheIdentifierParameters();

        return $pageId . '_' . hash('xxh3', serialize($pageCacheIdentifierParameters));
    }
}
