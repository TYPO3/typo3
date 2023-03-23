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

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\InvestigateMutationsEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationSuggestion;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;

/**
 * Suggest potential resolutions for simple asset violations, e.g.
 * in case https://example.org/file.js could was blocked to be loaded,
 * it would exactly suggest this mutation for the given directive.
 */
class AssetHandler
{
    use HandlerTrait;

    public function __invoke(InvestigateMutationsEvent $event): void
    {
        // skip, in case there are mutations already
        if ($event->getMutationSuggestions() !== []) {
            return;
        }
        $effectiveDirective = $this->resolveEffectiveDirective($event->report);
        $blockedUri = $this->resolveBlockedUri($event->report);
        // skip in case `blocked-uri` is not actually a URI with hostname,
        // or directive is not reasonable to be mutated at all
        if ($effectiveDirective === null
            || $blockedUri === null
            || $blockedUri->getHost() === ''
            || !$effectiveDirective->isMutationReasonable()) {
            return;
        }

        $event->appendMutationSuggestions(
            ...$this->createSuggestions($effectiveDirective, $blockedUri)
        );
    }

    /**
     * @return list<MutationSuggestion>
     */
    private function createSuggestions(Directive $effectiveDirective, UriInterface $blockedUri): array
    {
        // @todo resolve URLs to current scope to 'self' instead of using the URL

        $suggestions = [];
        $hostUri = $blockedUri->withUserInfo('')->withQuery('')->withFragment('');
        // resolves 'https://example.org/'
        if ($hostUri->getScheme() !== '') {
            $suggestions[] = new MutationSuggestion(
                $this->createExtendingMutationCollection(
                    $effectiveDirective,
                    UriValue::fromUri($hostUri->withPath(''))
                ),
                self::class . '@hostWithScheme',
                3,
                'Assets from host'
            );
        }
        // resolves 'https://example.org/path/to/resource.js'
        if ($hostUri->getScheme() !== '' && $hostUri->getPath() !== '') {
            $suggestions[] = new MutationSuggestion(
                $this->createExtendingMutationCollection(
                    $effectiveDirective,
                    UriValue::fromUri($hostUri)
                ),
                self::class . '@completeUrl',
                2,
                'Asset from specific URL'
            );
        }
        // resolves '*.example.org'
        $suggestions[] = new MutationSuggestion(
            $this->createExtendingMutationCollection(
                $effectiveDirective,
                new UriValue('*.' . $hostUri->getHost())
            ),
            self::class . '@wildcardHost',
            1,
            'Asset from wildcard host'
        );
        return $suggestions;
    }

    private function createExtendingMutationCollection(Directive $effectiveDirective, UriValue $value): MutationCollection
    {
        return new MutationCollection(
            new Mutation(MutationMode::Extend, $effectiveDirective, $value)
        );
    }
}
