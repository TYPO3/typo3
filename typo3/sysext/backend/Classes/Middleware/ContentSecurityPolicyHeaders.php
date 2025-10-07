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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\Behavior;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\DispositionConfiguration;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Disposition;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware\PolicyBag;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware\ResponseService;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Type\Map;

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
        private ResponseService $responseService,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $scope = Scope::backend();
        $nonce = $this->requestId->nonce;
        $request = $request->withAttribute('nonce', $nonce);
        $response = $handler->handle($request);
        $behavior = new Behavior();
        $disposition = Disposition::enforce;
        $dispositionMap = new Map();
        $dispositionMap[$disposition] = new DispositionConfiguration(
            true,
            true,
            $GLOBALS['TYPO3_CONF_VARS'][$scope->type->abbreviate()]['contentSecurityPolicyReportingUrl'],
        );
        $policyBag = new PolicyBag($scope, $dispositionMap, $behavior, $nonce);

        if ($response->hasHeader('Content-Security-Policy') || $response->hasHeader('Content-Security-Policy-Report-Only')) {
            $this->logger->info('Content-Security-Policy not enforced due to existence of custom header', [
                'scope' => (string)$scope,
                'uri' => (string)$request->getUri(),
            ]);
            return $response;
        }
        $this->policyProvider->prepare($policyBag, $request, $response);
        $policy = $policyBag->getPolicy($disposition);
        if ($policy->isEmpty()) {
            return $response;
        }
        if ($behavior->useNonce === false) {
            $response = $this->responseService->dropNonceFromHtmlResponse($response, $nonce);
        }
        return $response->withHeader(
            $disposition->getHttpHeaderName(),
            $policy->compile($this->requestId->nonce, $behavior, $this->cache)
        );
    }
}
