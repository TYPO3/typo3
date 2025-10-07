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

namespace TYPO3\CMS\Frontend\EventListener;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyPreparedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware\PolicyBag;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

#[AsEventListener('typo3/cms-frontend/content-security-policy/avoid-nonce')]
final class AvoidContentSecurityPolicyNonceEventListener
{
    public function __invoke(PolicyPreparedEvent $event): void
    {
        // skip, since the behavior already has been modified
        if ($event->policyBag->behavior->useNonce !== null) {
            return;
        }
        // skip, since the frontend request is not supposed to be fully cacheable
        if (!$this->isCacheableFrontendRequest($event->request)) {
            return;
        }
        if ($event->policyBag->nonce->count() === 0
            || $this->isEmptyResponse($event->response)
            || $this->nonceProxyIsAvoidable($event->policyBag)
        ) {
            $event->policyBag->behavior->useNonce = false;
        }
    }

    private function isCacheableFrontendRequest(ServerRequestInterface $request): bool
    {
        if (!ApplicationType::fromRequest($request)->isFrontend()) {
            return false;
        }
        $cacheInstruction = $request->getAttribute('frontend.cache.instruction');
        $controller = $request->getAttribute('frontend.controller');
        return $controller instanceof TypoScriptFrontendController
            && $cacheInstruction instanceof CacheInstruction
            && $cacheInstruction->isCachingAllowed()
            && !$controller->isINTincScript();
    }

    private function isEmptyResponse(null|string|ResponseInterface $response): bool
    {
        // skip, since it cannot be determined
        if ($response === null) {
            return false;
        }
        if (is_string($response)) {
            return $response === '';
        }
        return $response->getBody()->isReadable()
            && $response->getBody()->getSize() === 0;
    }

    private function nonceProxyIsAvoidable(PolicyBag $policyBag): bool
    {
        $nonce = $policyBag->nonce;
        foreach ($policyBag->dispositionMap->keys() as $disposition) {
            $policy = $policyBag->getPolicy($disposition);
            if ($policy->isEmpty()) {
                continue;
            }
            foreach (SourceKeyword::nonceProxy->getApplicableDirectives() as $directive) {
                $collection = $policy->get($directive);
                // directive is not present or does not contain `nonce-proxy`
                if ($collection === null || !$collection->contains(SourceKeyword::nonceProxy)) {
                    continue;
                }
                // stop in case this directive contains `strict-dynamic`, or the nonce for that directive was consumed
                // for inline assets, and there is no alternative (weak) `'unsafe-inline'` source in the policy
                if ($collection->contains(SourceKeyword::strictDynamic)
                    || (!$collection->contains(SourceKeyword::unsafeInline)
                        && $this->familyConsumedInlineNonce($directive, $nonce))
                ) {
                    return false;
                }
            }
        }
        return true;
    }

    private function familyConsumedInlineNonce(Directive $directive, ConsumableNonce $nonce): bool
    {
        foreach ($directive->getFamily() as $member) {
            if ($nonce->countInline($member) > 0) {
                return true;
            }
        }
        return false;
    }
}
