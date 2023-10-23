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

namespace TYPO3\CMS\Redirects\RedirectUpdate;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent;

/**
 * @internal This factory class is a specific implementation for creating SlugRedirectChangeItems
 *           and is not part of the public TYPO3 API.
 */
final class SlugRedirectChangeItemFactory
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function create(int $pageId, ?array $original = null, ?array $changed = null): ?SlugRedirectChangeItem
    {
        $original ??= BackendUtility::getRecordWSOL('pages', $pageId);
        if (!$original) {
            return null;
        }
        $languageId = (int)$original['sys_language_uid'];
        $defaultLanguagePageId = (int)$original['sys_language_uid'] > 0 ? (int)$original['l10n_parent'] : $pageId;
        try {
            $site = $this->siteFinder->getSiteByPageId($defaultLanguagePageId);
        } catch (SiteNotFoundException) {
            // "autoCreateRedirects" and "autoUpdateSlugs" are site configuration settings. Not finding one
            // means that we should not handle the creation of them, thus no need to create a change item.
            return null;
        }
        $siteLanguage = $site->getLanguageById($languageId);
        // Verify we should process auto redirect creation or slug updating. If not return early avoiding to create
        // a change item which is superflous at all.
        $settings = $site->getSettings();
        $autoUpdateSlugs = (bool)$settings->get('redirects.autoUpdateSlugs', true);
        $autoCreateRedirects = (bool)$settings->get('redirects.autoCreateRedirects', true);
        if (!($autoUpdateSlugs || $autoCreateRedirects)) {
            return null;
        }
        $changeItem = new SlugRedirectChangeItem(
            defaultLanguagePageId: $defaultLanguagePageId,
            pageId: $pageId,
            site: $site,
            siteLanguage: $siteLanguage,
            original: $original,
            sourcesCollection: new RedirectSourceCollection(),
            changed: $changed
        );
        return $this->eventDispatcher->dispatch(new SlugRedirectChangeItemCreatedEvent($changeItem))
            ->getSlugRedirectChangeItem();
    }
}
