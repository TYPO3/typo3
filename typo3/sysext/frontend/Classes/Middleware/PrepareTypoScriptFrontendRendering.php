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
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Locking\ResourceMutex;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Cache\MetaDataState;
use TYPO3\CMS\Frontend\ContentObject\RegisterStack;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Event\AfterTypoScriptDeterminedEvent;
use TYPO3\CMS\Frontend\Event\BeforePageCacheIdentifierIsHashedEvent;
use TYPO3\CMS\Frontend\Event\ShouldUseCachedPageDataIfAvailableEvent;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;
use TYPO3\CMS\Frontend\Page\PageInformationCreationFailedException;
use TYPO3\CMS\Frontend\Page\PageInformationFactory;
use TYPO3\CMS\Frontend\Page\PageParts;

/**
 * This important middleware prepares a lot of the heavy lifting.
 *
 * It is all about page determination, caching, locking, various request attributes and
 * TypoScript calculation. All these aspects depends on each other, this middleware determines
 * if the requested page has to be fully or partially rendered, determines cache state and sets
 * up system by adding request attributes and a setting up a couple of singletons.
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
        private TimeTracker $timeTracker,
        private PageInformationFactory $pageInformationFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Make sure frontend.preview aspect is given from now on
        if (!$this->context->hasAspect('frontend.preview')) {
            $this->context->setAspect('frontend.preview', new PreviewAspect());
        }

        // Verify crucial request attributes exist at this point
        if (!$request->getAttribute('routing') instanceof PageArguments || !$request->getAttribute('normalizedParams') instanceof NormalizedParams) {
            throw new \RuntimeException('Request attribute "routing" or "normalizedParams" not found. Error in previous middleware.', 1703150865);
        }

        $site = $request->getAttribute('site');
        // Cache instruction attribute may have been set by previous middlewares
        $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
        $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
        $language = $request->getAttribute('language') ?? $site->getDefaultLanguage();
        $routing = $request->getAttribute('routing');

        if ($this->context->getPropertyFromAspect('frontend.preview', 'isPreview', false)) {
            // Disable cache if this is a preview
            $cacheInstruction->disableCache('EXT:frontend: Disabled cache due to enabled frontend.preview aspect isPreview.');
        }
        // Make sure cache instruction attribute is always set from now on
        $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);
        // Did above code or some previous call disable cache?
        $isCachingAllowed = $cacheInstruction->isCachingAllowed();

        // Create and add PageInformation
        try {
            $this->timeTracker->push('Create PageInformation');
            $pageInformation = $this->pageInformationFactory->create($request);
        } catch (PageInformationCreationFailedException $exception) {
            return $exception->getResponse();
        } finally {
            $this->timeTracker->pull();
        }
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $sysTemplateRows = $pageInformation->getSysTemplateRows();

        // Init and add register and page parts
        $request = $request->withAttribute('frontend.register.stack', new RegisterStack());
        $pageParts = new PageParts();
        // Init "last changed" with "tstamp" of the page record, or SYS_LASTCHANGED if it's younger
        $lastChanged = (int)$pageInformation->getPageRecord()['tstamp'];
        if ($lastChanged < (int)$pageInformation->getPageRecord()['SYS_LASTCHANGED']) {
            $lastChanged = (int)$pageInformation->getPageRecord()['SYS_LASTCHANGED'];
        }
        $pageParts->setLastChanged($lastChanged);
        $request = $request->withAttribute('frontend.page.parts', $pageParts);

        // Create FrontendTypoScript with essential info for page cache identifier
        $conditionMatcherVariables = $this->prepareConditionMatcherVariables($request);
        $frontendTypoScript = $this->frontendTypoScriptFactory->createSettingsAndSetupConditions(
            $site,
            $sysTemplateRows,
            $conditionMatcherVariables,
            $isCachingAllowed ? $this->typoScriptCache : null,
        );

        // Set up cache relevant information
        $isUsingPageCacheAllowed = $this->eventDispatcher->dispatch(new ShouldUseCachedPageDataIfAvailableEvent($request, $isCachingAllowed))->shouldUseCachedPageData();
        $pageCacheIdentifier = $this->createPageCacheIdentifier($request, $frontendTypoScript);
        $cacheDataCollector->setPageCacheIdentifier($pageCacheIdentifier);

        // Get page cache row or lock rendering
        $pageCacheRow = null;
        if (!$isUsingPageCacheAllowed) {
            // Caching not allowed. We'll rebuild the page. Lock this.
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
                        // Got cache row, some other process did the work for us, release our lock again.
                        $this->lock->releaseLock('pages');
                    }
                }
                // Keep lock, we are the one generating the page now and fill cache.
            }
        }

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        if (is_array($pageCacheRow)) {
            // Got page from cache. Set up system state with it.
            $pageParts->setPageContentWasLoadedFromCache();
            $pageParts->setPageCacheGeneratedTimestamp($pageCacheRow['pageCacheGeneratedTimestamp']);
            $pageParts->setPageCacheExpireTimestamp($pageCacheRow['pageCacheExpireTimestamp']);
            foreach ($pageCacheRow['INTincScript'] as $intIncScript) {
                $pageParts->addNotCachedContentElement($intIncScript);
            }
            $pageParts->setPageTitle($pageCacheRow['pageTitleCache']);
            $pageParts->setContent($pageCacheRow['content']);
            $pageParts->setHttpContentType($pageCacheRow['contentType']);
            $pageParts->setPageRendererSubstitutionHash($pageCacheRow['pageRendererSubstitutionHash']);
            if ($pageCacheRow['pageRendererState'] ?? false) {
                $pageRendererState = unserialize($pageCacheRow['pageRendererState'], ['allowed_classes' => [Locale::class]]);
                $pageRenderer->updateState($pageRendererState);
            }
            if ($pageCacheRow['assetCollectorState'] ?? false) {
                $assetCollectorState = unserialize($pageCacheRow['assetCollectorState'], ['allowed_classes' => false]);
                $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
                $assetCollector->updateState($assetCollectorState);
            }
            // Restore the current tags and add them to the CacheTageCollector
            $lifetime = $pageParts->getPageCacheExpireTimestamp() - $GLOBALS['EXEC_TIME'];
            $cacheTags = array_map(fn(string $cacheTag) => new CacheTag($cacheTag, $lifetime), $pageCacheRow['cacheTags'] ?? []);
            $cacheDataCollector->addCacheTags(...$cacheTags);
            // Restore meta-data state
            if (is_array($pageCacheRow['metaDataState'] ?? null)) {
                GeneralUtility::makeInstance(MetaDataState::class)->updateState($pageCacheRow['metaDataState']);
            }
        } else {
            // Init FE PageRenderer defaults when this page needs to be generated
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $language = $request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage();
            if ($language->hasCustomTypo3Language()) {
                $locale = GeneralUtility::makeInstance(Locales::class)->createLocale($language->getTypo3Language());
            } else {
                $locale = $language->getLocale();
            }
            $pageRenderer->setLanguage($locale);
            $pageParts->setPageRendererSubstitutionHash(md5(StringUtility::getUniqueId()));
            $pageParts->setPageCacheGeneratedTimestamp($GLOBALS['EXEC_TIME']);
        }
        // Processing of page cache row done. Do not use this variable anymore.
        unset($pageCacheRow);

        try {
            $needsFullSetup = !$pageParts->hasPageContentBeenLoadedFromCache() || $pageParts->hasNotCachedContentElements();
            $pageType = $routing->getPageType();
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
            if ($setupConfigAst->getChildByName('no_cache')?->getValue()) {
                // Disable cache if config.no_cache is set!
                $cacheInstruction = $request->getAttribute('frontend.cache.instruction');
                $cacheInstruction->disableCache('EXT:frontend: Disabled cache due to TypoScript "config.no_cache = 1"');
            }
            $this->eventDispatcher->dispatch(new AfterTypoScriptDeterminedEvent($frontendTypoScript));

            $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);

            // b/w compat
            $GLOBALS['TYPO3_REQUEST'] = $request;

            return $handler->handle($request);
        } finally {
            // Whatever happens in a middleware below, this finally is called, even when exceptions
            // are raised by a lower one. This ensures locks are released no matter what.
            $this->lock->releaseLock('pages');
        }
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
