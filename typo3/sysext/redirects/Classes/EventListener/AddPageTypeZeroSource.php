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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent;
use TYPO3\CMS\Redirects\RedirectUpdate\PageTypeSource;
use TYPO3\CMS\Redirects\RedirectUpdate\PlainSlugReplacementRedirectSource;
use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;
use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceInterface;

/**
 * Event listener which build a source using site router for page type "0" and add it as PageTypeSource to
 * the collection. Eventually existing PlainSlugReplacement source will be removed, if it would provide the
 * same source definition as the generated PageTypeSource.
 *
 * @internal only to be used within redirects, not part of TYPO3 Core API.
 */
final class AddPageTypeZeroSource
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
        // Create full resolved uri for page type zero.
        $changeItem = $event->getSlugRedirectChangeItem();
        // Do not create a redirect source for ignored doktypes
        if (in_array((int)($changeItem->getOriginal()['doktype'] ?? 0), $this->ignoredDokTypes, true)) {
            return;
        }

        try {
            $pageTypeZeroSource = $this->createPageTypeZeroSource(
                $changeItem->getPageId(),
                $changeItem->getSite(),
                $changeItem->getSiteLanguage(),
            );
        } catch (UnableToLinkToPageException) {
            // Could not properly link to page. Nothing left to do, so return directly.
            return;
        }
        $sources = $changeItem->getSourcesCollection()->all();
        // If page type zero source results in the same uri, plain slug replacement source is removed. This avoids
        // the creation of duplicated redirects. PageTypeSource is taken as stronger match and therefor used.
        $sources = array_filter($sources, fn($source) => !$this->sourceEqualsPageTypeZeroSource($source, $pageTypeZeroSource));
        $sources[] = $pageTypeZeroSource;
        $changeItem = $changeItem->withSourcesCollection(new RedirectSourceCollection(...array_values($sources)));
        $event->setSlugRedirectChangeItem($changeItem);
    }

    private function sourceEqualsPageTypeZeroSource(RedirectSourceInterface $source, PageTypeSource $pageTypeZeroSource): bool
    {
        return $source instanceof PlainSlugReplacementRedirectSource
            && $source->getHost() === $pageTypeZeroSource->getHost()
            && $source->getPath() === $pageTypeZeroSource->getPath();
    }

    private function createPageTypeZeroSource(int $pageUid, Site $site, SiteLanguage $siteLanguage): PageTypeSource
    {
        try {
            $context = GeneralUtility::makeInstance(Context::class);
            $uri = $site->getRouter($context)->generateUri(
                $pageUid,
                [
                    '_language' => $siteLanguage,
                    'type' => 0,
                ],
                '',
                RouterInterface::ABSOLUTE_URL
            );
            return new PageTypeSource(
                $uri->getHost() ?: '*',
                $uri->getPath(),
                0,
                [],
            );
        } catch (\InvalidArgumentException | InvalidRouteArgumentsException $e) {
            throw new UnableToLinkToPageException(
                sprintf(
                    'The link to the page with ID "%d" and type "%d" could not be generated: %s',
                    $pageUid,
                    0,
                    $e->getMessage()
                ),
                1671639962,
                $e
            );
        }
    }
}
