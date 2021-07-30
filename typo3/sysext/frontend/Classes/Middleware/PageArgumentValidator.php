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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * This middleware validates given request parameters against the common "cHash" functionality.
 */
class PageArgumentValidator implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * The cHash Service class used for cHash related functionality
     *
     * @var CacheHashCalculator
     */
    protected $cacheHashCalculator;

    /**
     * @var TimeTracker
     */
    protected $timeTracker;

    /**
     * @var bool will be used to set $TSFE->no_cache later-on
     */
    protected $disableCache = false;

    public function __construct(
        CacheHashCalculator $cacheHashCalculator,
        TimeTracker $timeTracker
    ) {
        $this->cacheHashCalculator = $cacheHashCalculator;
        $this->timeTracker = $timeTracker;
    }

    /**
     * Validates the &cHash parameter against the other $queryParameters / GET parameters
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->disableCache = (bool)$request->getAttribute('noCache', false);
        $pageNotFoundOnValidationError = (bool)($GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] ?? true);
        /** @var PageArguments $pageArguments */
        $pageArguments = $request->getAttribute('routing', null);
        if (!($pageArguments instanceof PageArguments)) {
            // Page Arguments must be set in order to validate. This middleware only works if PageArguments
            // is available, and is usually combined with the Page Resolver middleware
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Page Arguments could not be resolved',
                ['code' => PageAccessFailureReasons::INVALID_PAGE_ARGUMENTS]
            );
        }
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter'] ?? true) {
            $cachingDisabledByRequest = false;
        } else {
            $cachingDisabledByRequest = $pageArguments->getArguments()['no_cache'] ?? $request->getParsedBody()['no_cache'] ?? false;
        }
        if (($cachingDisabledByRequest || $this->disableCache) && !$pageNotFoundOnValidationError) {
            // No need to test anything if caching was already disabled.
            return $handler->handle($request);
        }
        // Evaluate the cache hash parameter or dynamic arguments when coming from a Site-based routing
        $cHash = (string)($pageArguments->getArguments()['cHash'] ?? '');
        $queryParams = $pageArguments->getDynamicArguments();
        if ($cHash !== '' || !empty($queryParams)) {
            $relevantParametersForCacheHashArgument = $this->getRelevantParametersForCacheHashCalculation($pageArguments);
            if ($cHash !== '') {
                if (empty($relevantParametersForCacheHashArgument)) {
                    // cHash was given, but nothing to be calculated, so let's do a redirect to the current page
                    // but without the cHash
                    $this->logger->notice('The incoming cHash "{hash}" is given but not needed. cHash is unset', ['hash' => $cHash]);
                    $uri = $request->getUri();
                    unset($queryParams['cHash']);
                    $uri = $uri->withQuery(HttpUtility::buildQueryString($queryParams));
                    return new RedirectResponse($uri, 308);
                }
                if (!$this->evaluateCacheHashParameter($cHash, $relevantParametersForCacheHashArgument, $pageNotFoundOnValidationError)) {
                    return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'Request parameters could not be validated (&cHash comparison failed)',
                        ['code' => PageAccessFailureReasons::CACHEHASH_COMPARISON_FAILED]
                    );
                }
                // No cHash given but was required
            } elseif (!$this->evaluateQueryParametersWithoutCacheHash($queryParams, $pageNotFoundOnValidationError)) {
                return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    'Request parameters could not be validated (&cHash empty)',
                    ['code' => PageAccessFailureReasons::CACHEHASH_EMPTY]
                );
            }
        }

        $request = $request->withAttribute('noCache', $this->disableCache);
        return $handler->handle($request);
    }

    /**
     * Filters out the arguments that are necessary for calculating cHash
     *
     * @param PageArguments $pageArguments
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
    protected function evaluateCacheHashParameter(string $cHash, array $relevantParameters, bool $pageNotFoundOnCacheHashError): bool
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
        $this->disableCache = true;
        $this->timeTracker->setTSlogMessage('The incoming cHash "' . $cHash . '" and calculated cHash "' . $calculatedCacheHash . '" did not match, so caching was disabled. The fieldlist used was "' . implode(',', array_keys($relevantParameters)) . '"', LogLevel::ERROR);
        return true;
    }

    /**
     * No cHash is set but there are query parameters, check if that is correct
     *
     * Should only be called if NO cHash parameter is given.
     *
     * @param array<string, string|array> $dynamicArguments
     * @param bool $pageNotFoundOnCacheHashError
     * @return bool
     */
    protected function evaluateQueryParametersWithoutCacheHash(array $dynamicArguments, bool $pageNotFoundOnCacheHashError): bool
    {
        if (!$this->cacheHashCalculator->doParametersRequireCacheHash(HttpUtility::buildQueryString($dynamicArguments))) {
            return true;
        }
        // cHash is required, but not given, so trigger a 404
        if ($pageNotFoundOnCacheHashError) {
            return false;
        }
        // Caching is disabled now (but no 404)
        $this->disableCache = true;
        $this->timeTracker->setTSlogMessage('TSFE->reqCHash(): No &cHash parameter was sent for GET vars though required so caching is disabled', LogLevel::ERROR);
        return true;
    }
}
