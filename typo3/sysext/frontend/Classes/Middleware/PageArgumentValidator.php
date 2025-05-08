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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * This middleware validates given request parameters against the common "cHash" functionality.
 */
readonly class PageArgumentValidator implements MiddlewareInterface
{
    public function __construct(
        private CacheHashCalculator $cacheHashCalculator,
        private LoggerInterface $logger,
    ) {}

    /**
     * Validates the &cHash parameter against the other $queryParameters / GET parameters
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
        $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);
        $pageNotFoundOnValidationError = (bool)($GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] ?? true);
        $pageArguments = $request->getAttribute('routing');
        if (!($pageArguments instanceof PageArguments)) {
            // Page Arguments must be set in order to validate. This middleware only works if PageArguments
            // is available, and is usually combined with the Page Resolver middleware
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Page Arguments could not be resolved',
                ['code' => PageAccessFailureReasons::INVALID_PAGE_ARGUMENTS]
            );
        }
        if (!($GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter'] ?? true)
            && ($pageArguments->getArguments()['no_cache'] ?? $request->getParsedBody()['no_cache'] ?? false)
        ) {
            $cacheInstruction->disableCache('EXT:frontend: Caching disabled by no_cache query argument.');
        }
        if (!$cacheInstruction->isCachingAllowed() && !$pageNotFoundOnValidationError) {
            // No need to test anything if caching was already disabled.
            return $handler->handle($request);
        }
        // Evaluate the cache hash parameter or dynamic arguments when coming from a Site-based routing
        $cHash = '';
        if (isset($pageArguments->getArguments()['cHash']) && is_scalar($pageArguments->getArguments()['cHash'])) {
            $cHash = (string)($pageArguments->getArguments()['cHash']);
        }
        $queryParams = $pageArguments->getDynamicArguments();
        if ($cHash !== '' || !empty($queryParams)) {
            $relevantParametersForCacheHashArgument = $this->getRelevantParametersForCacheHashCalculation($pageArguments);
            if ($cHash !== '') {
                if (empty($relevantParametersForCacheHashArgument)) {
                    // cHash was given, but nothing to be calculated, so let's do a redirect to the current page but without the cHash
                    $this->logger->notice('The incoming cHash "{hash}" is given but not needed. cHash is unset', ['hash' => $cHash]);
                    $uri = $request->getUri();
                    unset($queryParams['cHash']);
                    $uri = $uri->withQuery(HttpUtility::buildQueryString($queryParams));
                    return new RedirectResponse($uri, 308);
                }
                if (!$this->evaluateCacheHashParameter($cacheInstruction, $cHash, $relevantParametersForCacheHashArgument, $pageNotFoundOnValidationError)) {
                    return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'Request parameters could not be validated (&cHash comparison failed)',
                        ['code' => PageAccessFailureReasons::CACHEHASH_COMPARISON_FAILED]
                    );
                }
                // No cHash given but was required
            } elseif (!$this->evaluatePageArgumentsWithoutCacheHash($cacheInstruction, $pageArguments, $pageNotFoundOnValidationError)) {
                return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    'Request parameters could not be validated (&cHash empty)',
                    ['code' => PageAccessFailureReasons::CACHEHASH_EMPTY]
                );
            }
        }

        return $handler->handle($request);
    }

    /**
     * Filters out the arguments that are necessary for calculating cHash
     *
     * @return array<string, string>
     */
    protected function getRelevantParametersForCacheHashCalculation(PageArguments $pageArguments): array
    {
        $queryParams = $pageArguments->getDynamicArguments();
        $queryParams['id'] = $pageArguments->getPageId();
        return $this->cacheHashCalculator->getRelevantParameters(HttpUtility::buildQueryString($queryParams));
    }

    /**
     * Calculates a hash string based on additional parameters in the url.
     * This is used to cache pages with more parameters than just id and type.
     *
     * @param string $cHash the chash to check
     * @param array<string, string> $relevantParameters GET parameters necessary for cHash calculation
     * @param bool $pageNotFoundOnCacheHashError see $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError']
     * @return bool if false, then a PageNotFound response is triggered
     */
    protected function evaluateCacheHashParameter(CacheInstruction $cacheInstruction, string $cHash, array $relevantParameters, bool $pageNotFoundOnCacheHashError): bool
    {
        $calculatedCacheHash = $this->cacheHashCalculator->calculateCacheHash($relevantParameters);
        if (hash_equals($calculatedCacheHash, $cHash)) {
            return true;
        }
        // Early return to trigger the error controller
        if ($pageNotFoundOnCacheHashError) {
            return false;
        }
        // Caching is disabled now (but no 404)
        $cacheInstruction->disableCache('EXT:frontend: Incoming cHash "' . $cHash . '" and calculated cHash "' . $calculatedCacheHash . '" did not match.' .
            ' The field list used was "' . implode(',', array_keys($relevantParameters)) . '". Caching is disabled.');
        return true;
    }

    /**
     * No cHash is set but there are query parameters, check if that is correct
     *
     * Should only be called if NO cHash parameter is given.
     *
     * @param array<string, string|array> $dynamicArguments
     */
    protected function evaluateQueryParametersWithoutCacheHash(CacheInstruction $cacheInstruction, array $dynamicArguments, bool $pageNotFoundOnCacheHashError): bool
    {
        if (!$this->cacheHashCalculator->doParametersRequireCacheHash(HttpUtility::buildQueryString($dynamicArguments))) {
            return true;
        }
        // cHash is required, but not given, so trigger a 404
        if ($pageNotFoundOnCacheHashError) {
            return false;
        }
        // Caching is disabled now (but no 404)
        $cacheInstruction->disableCache('EXT:frontend: No cHash query argument was sent for GET vars though required. Caching is disabled.');
        return true;
    }

    /**
     * No cHash is set but there are query parameters, then calculate a possible cHash from the given
     * query parameters and see if a cHash is returned (similar to comparing this).
     *
     * Is only called if NO cHash parameter is given.
     */
    protected function evaluatePageArgumentsWithoutCacheHash(CacheInstruction $cacheInstruction, PageArguments $pageArguments, bool $pageNotFoundOnCacheHashError): bool
    {
        // legacy behaviour
        if (!($GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['enforceValidation'] ?? false)) {
            return $this->evaluateQueryParametersWithoutCacheHash($cacheInstruction, $pageArguments->getDynamicArguments(), $pageNotFoundOnCacheHashError);
        }
        $relevantParameters = $this->getRelevantParametersForCacheHashCalculation($pageArguments);
        // There are parameters that would be needed for the current page, but no cHash is given.
        // Thus, a "page not found" error is thrown - as configured via "pageNotFoundOnCHashError".
        if (!empty($relevantParameters) && $pageNotFoundOnCacheHashError) {
            return false;
        }
        // There are no parameters that require a cHash.
        // We end up here when the site was called with an `id` param, e.g. https://example.org/index?id=123.
        // Avoid disabling caches in this case.
        if (empty($relevantParameters)) {
            return true;
        }
        // Caching is disabled now (but no 404)
        $cacheInstruction->disableCache('EXT:frontend: No cHash query argument was sent for given query parameters. Caching is disabled');
        return true;
    }
}
