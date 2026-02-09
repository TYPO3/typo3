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
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\Behavior;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\DispositionMapFactory;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware\PolicyBag;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware\ResponseService;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
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
        private ResponseService $responseService,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->applyContentSecurityPolicy($request, $handler);
    }

    /**
     * Apply Content-Security-Policy headers to an error response that bypassed
     * the normal middleware stack (e.g. responses from ErrorController).
     */
    public function applyToResponse(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->applyContentSecurityPolicy($request, $response);
    }

    private function applyContentSecurityPolicy(ServerRequestInterface $request, ResponseInterface|RequestHandlerInterface $subject): ResponseInterface
    {
        $site = $request->getAttribute('site');
        $dispositionMap = $this->dispositionMapFactory->buildDispositionMap(
            $site instanceof Site ? ($site->getConfiguration()['contentSecurityPolicies'] ?? []) : []
        );
        // return early in case CSP shall not be used
        if ($dispositionMap->keys() === []) {
            return $subject instanceof RequestHandlerInterface ? $subject->handle($request) : $subject;
        }
        $scope = Scope::frontendSite($site);
        $behavior = new Behavior();
        $nonce = $this->requestId->nonce;
        $policyBag = new PolicyBag($scope, $dispositionMap, $behavior, $nonce);
        // make sure, the nonce value is set before processing the remaining components
        $request = $request
            ->withAttribute('nonce', $nonce)
            ->withAttribute('csp.policyBag', $policyBag);
        $response = $subject instanceof RequestHandlerInterface ? $subject->handle($request) : $subject;
        if ($response->hasHeader('Content-Security-Policy') || $response->hasHeader('Content-Security-Policy-Report-Only')) {
            if ($subject instanceof RequestHandlerInterface) {
                $this->logger->info('Content-Security-Policy not enforced due to existence of custom header', [
                    'scope' => (string)$scope,
                    'uri' => (string)$request->getUri(),
                ]);
            }
            return $response;
        }

        $processedEarlier = $policyBag->hasPolicies();
        $this->policyProvider->prepare($policyBag, $request, $response);
        foreach ($dispositionMap as $disposition => $dispositionConfiguration) {
            $policy = $policyBag->getPolicy($disposition);
            if ($policy->isEmpty()) {
                continue;
            }
            $response = $response->withHeader(
                $disposition->getHttpHeaderName(),
                $policy->compile($this->requestId->nonce, $behavior, $this->cache)
            );
        }
        if (!$processedEarlier && $behavior->useNonce === false) {
            $response = $this->responseService->dropNonceFromHtmlResponse($response, $nonce);
        }
        return $response;
    }
}
