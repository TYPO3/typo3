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

namespace TYPO3\CMS\Redirects\EventListener;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent;
use TYPO3\CMS\Redirects\RedirectUpdate\PlainSlugReplacementRedirectSource;
use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;

/**
 * Event listener to create plain slug replacement.
 * @internal Internal use for ext:redirects, not part of Public API. May vanish at any given time.
 */
final class AddPlainSlugReplacementSource
{
    /**
     * @var list<PageRepository::DOKTYPE_*>
     */
    private array $ignoredDokTypes = [
        PageRepository::DOKTYPE_SPACER,
        PageRepository::DOKTYPE_SYSFOLDER,
        PageRepository::DOKTYPE_RECYCLER,
    ];

    public function __invoke(SlugRedirectChangeItemCreatedEvent $event): void
    {
        $changeItem = $event->getSlugRedirectChangeItem();
        // Do not create a redirect source for ignored doktypes
        if (in_array((int)($changeItem->getOriginal()['doktype'] ?? 0), $this->ignoredDokTypes, true)) {
            return;
        }

        // We create a plain slug replacement source, which mirrors the behaviour since first implementation. This
        // may vanish anytime. Introducing an event here opens up the possibility to add custom source definitions, for
        // example doing a real URI building to cover route decorators and enhancers, or creating redirects for more
        // than only one source.
        $changeItem = $changeItem->withSourcesCollection(
            new RedirectSourceCollection(
                new PlainSlugReplacementRedirectSource(
                    host: $changeItem->getSiteLanguage()->getBase()->getHost() ?: '*',
                    path: rtrim($changeItem->getSiteLanguage()->getBase()->getPath(), '/') . $changeItem->getOriginal()['slug'],
                    targetLinkParameters: []
                ),
                ...array_values($changeItem->getSourcesCollection()->all())
            )
        );
        $event->setSlugRedirectChangeItem($changeItem);
    }
}
