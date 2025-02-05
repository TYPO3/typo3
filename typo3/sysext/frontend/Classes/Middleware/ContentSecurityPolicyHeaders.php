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
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\DispositionMapFactory;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Adds Content-Security-Policy headers to response.
 *
 * @internal
 */
final readonly class ContentSecurityPolicyHeaders implements MiddlewareInterface
{
    public function __construct(
        private RequestId $requestId,
        private LoggerInterface $logger,
        #[Autowire(service: 'cache.assets')]
        private FrontendInterface $cache,
        private PolicyProvider $policyProvider,
        private DispositionMapFactory $dispositionMapFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        $dispositionMap = $this->dispositionMapFactory->buildDispositionMap(
            $site instanceof Site ? ($site->getConfiguration()['contentSecurityPolicies'] ?? []) : []
        );
        // return early in case CSP shall not be used
        if ($dispositionMap->keys() === []) {
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

        foreach ($dispositionMap as $disposition => $dispositionConfiguration) {
            $policy = $this->policyProvider->provideFor($scope, $disposition, $request);
            if ($policy->isEmpty()) {
                continue;
            }
            $reportingUrl = $this->policyProvider->getReportingUrlFor($scope, $request, $dispositionConfiguration);
            if ($reportingUrl !== null) {
                $policy = $policy->report(UriValue::fromUri($reportingUrl));
            }
            $response = $response->withHeader(
                $disposition->getHttpHeaderName(),
                $policy->compile($this->requestId->nonce, $this->cache)
            );
        }
        return $response;
    }
}
