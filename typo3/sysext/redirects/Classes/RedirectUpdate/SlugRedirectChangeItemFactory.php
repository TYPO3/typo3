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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * @internal This factory class is a specific implementation for creating SlugRedirectChangeItems
 *           and is not part of the public TYPO3 API.
 */
class SlugRedirectChangeItemFactory
{
    public function __construct(
        protected SiteFinder $siteFinder
    ) {
    }

    public function create(int $pageId, ?array $original = null, ?array $changed = null): ?SlugRedirectChangeItem
    {
        $original ??= BackendUtility::getRecordWSOL('pages', $pageId);
        if (!$original) {
            return null;
        }
        $languageId = (int)$original['sys_language_uid'];
        $defaultLanguagePageId = (int)$original['sys_language_uid'] > 0 ? (int)$original['l10n_parent'] : $pageId;
        $site = $this->siteFinder->getSiteByPageId($defaultLanguagePageId);
        $siteLanguage = $site->getLanguageById($languageId);
        // We create a plain slug replacement source, which mirrors the behaviour since redirects implementation. This
        // may vanish anytime. Introducing an event here opens up the possibility to add custom source definitions, for
        // example doing a real URI building to cover route decorators and enhancers, or creating redirects for more
        // than only one source.
        $plainSlugSource = new PlainSlugReplacementRedirectSource(
            host: $siteLanguage->getBase()->getHost() ?: '*',
            path: rtrim($siteLanguage->getBase()->getPath(), '/') . $original['slug'],
            targetLinkParameters: []
        );
        $sourcesCollection = new RedirectSourceCollection($plainSlugSource);
        $changeItem = new SlugRedirectChangeItem(
            defaultLanguagePageId: $defaultLanguagePageId,
            pageId: $pageId,
            site: $site,
            siteLanguage: $siteLanguage,
            original: $original,
            sourcesCollection: $sourcesCollection,
            changed: $changed
        );
        // @todo Introduce an event here in a dedicated feature patch.
        return $changeItem;
    }
}
