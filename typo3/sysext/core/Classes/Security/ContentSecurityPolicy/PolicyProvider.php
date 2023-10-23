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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Provide a Content-Security-Policy representation for a given scope (e.g. backend, frontend, frontend.my-site).
 *
 * @internal
 */
final class PolicyProvider
{
    protected const REPORTING_URI = '@http-reporting';

    public function __construct(
        private readonly RequestId $requestId,
        private readonly SiteFinder $siteFinder,
        private readonly PolicyRegistry $policyRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
        protected readonly MutationRepository $mutationRepository,
    ) {}

    /**
     * Provides the complete, dynamically mutated policy to be used in HTTP responses.
     */
    public function provideFor(Scope $scope): Policy
    {
        // @todo add policy cache per scope
        $defaultPolicy = new Policy();
        $mutationCollections = iterator_to_array(
            $this->mutationRepository->findByScope($scope),
            false
        );
        // add temporary(!) mutations that were collected during processing this request
        if ($this->policyRegistry->hasMutationCollections()) {
            $mutationCollections = array_merge(
                $mutationCollections,
                $this->policyRegistry->getMutationCollections()
            );
        }
        // apply all mutations to current policy
        $currentPolicy = $defaultPolicy->mutate(...$mutationCollections);
        // allow other components to modify the current policy individually via PSR-14 event
        $event = new PolicyMutatedEvent($scope, $defaultPolicy, $currentPolicy, ...$mutationCollections);
        $this->eventDispatcher->dispatch($event);
        return $event->getCurrentPolicy();
    }

    public function getReportingUrlFor(Scope $scope, ServerRequestInterface $request): ?UriInterface
    {
        $value = $GLOBALS['TYPO3_CONF_VARS'][$scope->type->abbreviate()]['contentSecurityPolicyReportingUrl'] ?? null;
        if (!empty($value) && is_string($value)) {
            try {
                return new Uri($value);
            } catch (\InvalidArgumentException) {
                return null;
            }
        }
        $uriBase = $this->getDefaultReportingUriBase($scope, $request);
        return $uriBase->withQuery($uriBase->getQuery() . '&requestTime=' . $this->requestId->microtime);
    }

    /**
     * Returns the URI base, for better partitioning it should be extended by `&requestTime=...`
     */
    public function getDefaultReportingUriBase(Scope $scope, ServerRequestInterface $request, bool $absolute = true): UriInterface
    {
        $normalizedParams = $request->getAttribute('normalizedParams') ?? NormalizedParams::createFromRequest($request);
        // resolve URI from current site language or site default language in frontend scope
        if ($scope->isFrontendSite()) {
            $site = $this->resolveSite($scope);
            $siteLanguage = $request->getAttribute('siteLanguage');
            $siteLanguage = $siteLanguage instanceof SiteLanguage ? $siteLanguage : $site->getDefaultLanguage();
            $uri = $siteLanguage->getBase();
            $uri = $uri->withPath(rtrim($uri->getPath(), '/') . '/');
            // otherwise fall back to current request URI
        } else {
            $uri = new Uri($normalizedParams->getSitePath());
        }
        // add `typo3/` path in backend scope
        if ($scope->type->isBackend()) {
            $uri = $uri->withPath($uri->getPath() . 'typo3/');
        }
        // prefix current require scheme, host, port in case it's not given
        if ($absolute && ($uri->getScheme() === '' || $uri->getHost() === '')) {
            $current = new Uri($normalizedParams->getSiteUrl());
            $uri = $uri
                ->withScheme($current->getScheme())
                ->withHost($current->getHost())
                ->withPort($current->getPort());
        } elseif (!$absolute && $uri->getScheme() !== '' && $uri->getHost() !== '') {
            $uri = $uri->withScheme('')->withHost('')->withPort(null);
        }
        // `/en/@http-reporting?csp=report` (relative)
        // `https://ip12.anyhost.it:8443/en/@http-reporting?csp=report` (absolute)
        return $uri->withPath($uri->getPath() . self::REPORTING_URI)->withQuery('csp=report');
    }

    private function resolveSite(Scope $scope): Site
    {
        return $scope->site ?? $this->siteFinder->getSiteByIdentifier($scope->siteIdentifier);
    }
}
