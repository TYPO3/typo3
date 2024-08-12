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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;

/**
 * Adds Content-Security-Policy headers to response.
 *
 * @internal
 */
final readonly class ContentSecurityPolicyHeaders implements MiddlewareInterface
{
    public function __construct(
        private Features $features,
        private RequestId $requestId,
        private LoggerInterface $logger,
        #[Autowire(service: 'cache.assets')]
        private FrontendInterface $cache,
        private PolicyProvider $policyProvider,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        // return early in case CSP shall not be used
        if (!$this->features->isFeatureEnabled('security.frontend.enforceContentSecurityPolicy')
            || ($site !== null && !($site->getConfiguration()['contentSecurityPolicies']['enable'] ?? true))
        ) {
            return $handler->handle($request);
        }
        // make sure, the nonce value is set before processing the remaining middlewares
        $request = $request->withAttribute('nonce', $this->requestId->nonce);
        $response = $handler->handle($request);

        $scope = Scope::frontendSite($site);
        if ($response->hasHeader('Content-Security-Policy') || $response->hasHeader('Content-Security-Policy-Report-Only')) {
            $this->logger->info('Content-Security-Policy not enforced due to existence of custom header', [
                'scope' => (string)$scope,
                'uri' => (string)$request->getUri(),
            ]);
            return $response;
        }

        $policy = $this->policyProvider->provideFor($scope, $request);
        if ($policy->isEmpty()) {
            return $response;
        }
        $reportingUri = $this->policyProvider->getReportingUrlFor($scope, $request);
        if ($reportingUri !== null) {
            $policy = $policy->report(UriValue::fromUri($reportingUri));
        }
        return $response->withHeader('Content-Security-Policy', $policy->compile($this->requestId->nonce, $this->cache));
    }
}
