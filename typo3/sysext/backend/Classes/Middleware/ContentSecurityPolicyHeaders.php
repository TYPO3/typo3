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

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
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
final class ContentSecurityPolicyHeaders implements MiddlewareInterface
{
    public function __construct(
        private readonly Features $features,
        private readonly RequestId $requestId,
        private readonly LoggerInterface $logger,
        private readonly FrontendInterface $cache,
        private readonly PolicyProvider $policyProvider,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('nonce', $this->requestId->nonce);
        $response = $handler->handle($request);

        if (!$this->features->isFeatureEnabled('security.backend.enforceContentSecurityPolicy')) {
            return $response;
        }

        $scope = Scope::backend();
        if ($response->hasHeader('Content-Security-Policy') || $response->hasHeader('Content-Security-Policy-Report-Only')) {
            $this->logger->info('Content-Security-Policy not enforced due to existence of custom header', [
                'scope' => (string)$scope,
                'uri' => (string)$request->getUri(),
            ]);
            return $response;
        }

        $policy = $this->policyProvider->provideFor($scope);
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
