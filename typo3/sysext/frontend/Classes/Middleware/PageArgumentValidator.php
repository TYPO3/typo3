<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
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
     * @var TypoScriptFrontendController
     */
    protected $controller;

    /**
     * @param TypoScriptFrontendController|null $controller
     */
    public function __construct(TypoScriptFrontendController $controller = null)
    {
        $this->controller = $controller ?? $GLOBALS['TSFE'];
        $this->cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
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
        $pageNotFoundOnValidationError = (bool)($GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] ?? true);
        $pageArguments = $request->getAttribute('routing', null);
        if ($this->controller->no_cache && !$pageNotFoundOnValidationError) {
            // No need to test anything if caching was already disabled.
        } else {
            // Evaluate the cache hash parameter or dynamic arguments when coming from a Site-based routing
            if ($pageArguments instanceof PageArguments) {
                $queryParams = $pageArguments->getDynamicArguments();
            } else {
                $queryParams = $request->getQueryParams();
            }
            if (!empty($queryParams) && !$this->evaluateCacheHashParameter($queryParams, $pageNotFoundOnValidationError)) {
                // cHash was given, but nothing to be calculated, so let's do a redirect to the current page
                // but without the cHash
                if ($this->controller->cHash && empty($this->controller->cHash_array)) {
                    $uri = $request->getUri();
                    unset($queryParams['cHash']);
                    $uri = $uri->withQuery(HttpUtility::buildQueryString($queryParams));
                    return new RedirectResponse($uri, 308);
                }
                return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    'Request parameters could not be validated (&cHash comparison failed)',
                    ['code' => PageAccessFailureReasons::CACHEHASH_COMPARISON_FAILED]
                );
            }
        }
        return $handler->handle($request);
    }

    /**
     * Calculates a hash string based on additional parameters in the url.
     *
     * Calculated hash is stored in $this->controller->cHash_array.
     * This is used to cache pages with more parameters than just id and type.
     *
     * @see TypoScriptFrontendController::reqCHash()
     * @param array<string, string> $queryParams GET parameters
     * @param bool $pageNotFoundOnCacheHashError see $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError']
     * @return bool if false, then a PageNotFound response is triggered
     */
    protected function evaluateCacheHashParameter(array $queryParams, bool $pageNotFoundOnCacheHashError): bool
    {
        if ($this->controller->cHash !== '') {
            // Make sure we use the page uid and not the page alias
            $queryParams['id'] = $this->controller->id;
            $relevantParameters = $this->cacheHashCalculator->getRelevantParameters(HttpUtility::buildQueryString($queryParams));
            $this->controller->cHash_array = $relevantParameters;
            // cHash was given, but nothing to be calculated, so cHash is unset and all is good.
            if (empty($relevantParameters)) {
                $this->logger->notice('The incoming cHash "' . $this->controller->cHash . '" is given but not needed. cHash is unset');
                return false;
            }
            $calculatedCacheHash = $this->cacheHashCalculator->calculateCacheHash($relevantParameters);
            if (!hash_equals($calculatedCacheHash, $this->controller->cHash)) {
                // Early return to trigger the error controller
                if ($pageNotFoundOnCacheHashError) {
                    return false;
                }
                $this->controller->no_cache = true;
                $this->getTimeTracker()->setTSlogMessage('The incoming cHash "' . $this->controller->cHash . '" and calculated cHash "' . $calculatedCacheHash . '" did not match, so caching was disabled. The fieldlist used was "' . implode(',', array_keys($this->controller->cHash_array)) . '"', 2);
            }
            // No cHash is set, check if that is correct
        } elseif ($this->cacheHashCalculator->doParametersRequireCacheHash(HttpUtility::buildQueryString($queryParams))) {
            // Will disable caching
            $this->controller->reqCHash();
        }
        return true;
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
