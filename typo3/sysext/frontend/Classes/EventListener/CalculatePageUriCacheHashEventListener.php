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

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Routing\Event\AfterPageUriGeneratedEvent;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

/**
 * Calculates the "cHash" query argument for a generated page URI and appends it, reproducing
 * the calculation PageRouter itself performed before this event existed. The hash is calculated
 * from the event's PageArguments, i.e. from the parameters PageRouter::generateUri() was called
 * with, not from the URI's query string: a query argument added by another listener is not
 * covered by the hash.
 */
#[AsEventListener('typo3/cms-frontend/routing/calculate-page-uri-cache-hash')]
final readonly class CalculatePageUriCacheHashEventListener
{
    public function __construct(
        private CacheHashCalculator $cacheHashCalculator,
    ) {}

    public function __invoke(AfterPageUriGeneratedEvent $event): void
    {
        $pageArguments = $event->getPageArguments();
        $dynamicArguments = $pageArguments->getDynamicArguments();
        if ($dynamicArguments === []) {
            return;
        }

        $hashParameters = $dynamicArguments;
        $hashParameters['id'] = $event->getPageId();
        $hashQueryString = http_build_query($hashParameters, '', '&', PHP_QUERY_RFC3986);
        $relevantParameters = $this->cacheHashCalculator->getRelevantParameters($hashQueryString);
        $cacheHash = $this->cacheHashCalculator->calculateCacheHash($relevantParameters);
        if ($cacheHash === '') {
            return;
        }

        $queryArguments = $pageArguments->getQueryArguments();
        $queryArguments['cHash'] = $cacheHash;
        $event->setUri($event->getUri()->withQuery(http_build_query($queryArguments, '', '&', PHP_QUERY_RFC3986)));
    }
}
