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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Processing;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\InvestigateMutationsEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationSuggestion;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;

/**
 * Suggests resolutions for Google-specific assets (e.g. Google Maps JS API).
 */
class GoogleMapsHandler
{
    use HandlerTrait;

    private const DOMAIN_NAMES = [
        'fonts.googleapis.com',
        'maps.googleapis.com',
        'fonts.gstatic.com',
        'maps.gstatic.com',
    ];

    private static MutationSuggestion $suggestion;
    private static Policy $policyNarrative;

    public function __construct()
    {
        if (!isset(self::$suggestion)) {
            self::$suggestion = $this->createGoogleMapsSuggestion();
            self::$policyNarrative = (new Policy())->mutate(self::$suggestion->collection);
        }
    }

    public function __invoke(InvestigateMutationsEvent $event): void
    {
        $effectiveDirective = $this->resolveEffectiveDirective($event->report);
        $blockedUri = $this->resolveBlockedUri($event->report);
        if ($effectiveDirective === null || $blockedUri === null || !$this->isInDomainNames($blockedUri->getHost())) {
            return;
        }
        // skip other handlers
        $event->stopPropagation();
        // clear mutations in case a resolution is contained (without inference) in current policy already
        if ($event->policy->contains(self::$policyNarrative)) {
            $event->setMutationSuggestions();
            return;
        }
        // otherwise create mutations for Google Maps JS API,
        // in case the policy narrative would cover (with inference) the current violation
        if (self::$policyNarrative->coversDirective($effectiveDirective, UriValue::fromUri($blockedUri))) {
            // override existing mutations (this handler seems to be more specific)
            $event->setMutationSuggestions(self::$suggestion);
        }
    }

    private function createGoogleMapsSuggestion(): MutationSuggestion
    {
        // see https://developers.google.com/maps/documentation/javascript/content-security-policy
        // @todo `Note that 'strict-dynamic' is present, so host-based allowlisting is disabled.`
        $collection = new MutationCollection(
            new Mutation(
                MutationMode::Extend,
                Directive::ScriptSrcElem,
                SourceKeyword::strictDynamic, // requires(!) Nonce everywhere
                SourceScheme::https, // thx Google!
                SourceKeyword::unsafeEval, // thx Google!
                SourceScheme::blob, // thx Google!
            ),
            new Mutation(
                MutationMode::Extend,
                Directive::ImgSrc,
                // @todo should be UriValue (which currently does not support scheme wildcards)
                new UriValue('https://*.googleapis.com'),
                new UriValue('https://*.gstatic.com'),
                new UriValue('*.google.com'),
                new UriValue('*.googleusercontent.com'),
            ),
            new Mutation(
                MutationMode::Extend,
                Directive::FrameSrc,
                new UriValue('*.google.com'),
            ),
            new Mutation(
                MutationMode::Extend,
                Directive::ConnectSrc,
                new UriValue('*.google.com'),
                new UriValue('https://*.googleapis.com'),
                new UriValue('https://*.gstatic.com'),
                SourceScheme::blob, // thx Google!
                SourceScheme::data, // thx Google!
            ),
            new Mutation(
                MutationMode::Extend,
                Directive::FontSrc,
                new UriValue('https://fonts.gstatic.com'),
            ),
            new Mutation(
                MutationMode::Extend,
                Directive::StyleSrcElem,
                SourceKeyword::nonceProxy,
                new UriValue('https://fonts.gstatic.com'),
                new UriValue('https://fonts.googleapis.com'),
            ),
            new Mutation(
                MutationMode::Extend,
                Directive::WorkerSrc,
                SourceScheme::blob,
            ),
        );
        return new MutationSuggestion($collection, self::class, 5, 'Google Maps');
    }

    private function isInDomainNames(string $hostName): bool
    {
        if (in_array($hostName, self::DOMAIN_NAMES, true)) {
            return true;
        }
        foreach (self::DOMAIN_NAMES as $domainName) {
            if (str_ends_with($hostName, '.' . $domainName)) {
                return true;
            }
        }
        return false;
    }
}
