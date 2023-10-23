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

namespace TYPO3\CMS\Seo\HrefLang;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;
use TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent;

/**
 * Class to add the hreflang tags to the page
 *
 * @internal
 */
class HrefLangGenerator
{
    public function __construct(
        protected ContentObjectRenderer $cObj,
        protected LanguageMenuProcessor $languageMenuProcessor,
    ) {}

    public function __invoke(ModifyHrefLangTagsEvent $event): void
    {
        $hrefLangs = $event->getHrefLangs();
        if ((int)$this->getTypoScriptFrontendController()->page['no_index'] === 1) {
            return;
        }

        $this->cObj->setRequest($event->getRequest());
        $languages = $this->languageMenuProcessor->process($this->cObj, [], [], []);
        $tsfe = $this->getTypoScriptFrontendController();
        $site = $tsfe->getSite();
        $siteLanguage = $tsfe->getLanguage();
        $pageId = $tsfe->id;

        foreach ($languages['languagemenu'] as $language) {
            if (!empty($language['link']) && $language['hreflang']) {
                if ($language['languageId'] === 0) {
                    // No need to fetch default language
                    $page = ($tsfe->page['_TRANSLATION_SOURCE'] ?? null)?->toArray(true) ?? $tsfe->page;
                } elseif ($language['languageId'] === ($tsfe->page['_PAGES_OVERLAY_REQUESTEDLANGUAGE'] ?? false)) {
                    // No need to fetch current language
                    $page = $tsfe->page;
                } else {
                    $page = $this->getTranslatedPageRecord($pageId, $language['languageId'], $site);
                }
                // do not set hreflang if a page is not translated explicitly
                if (empty($page)) {
                    continue;
                }
                // do not set hreflang when canonical is set explicitly
                if (!empty($page['canonical_link'])) {
                    continue;
                }

                $href = $this->getAbsoluteUrl($language['link'], $siteLanguage);
                $hrefLangs[$language['hreflang']] = $href;
            }
        }

        if (count($hrefLangs) > 1) {
            if (array_key_exists($languages['languagemenu'][0]['hreflang'], $hrefLangs)) {
                $hrefLangs['x-default'] = $hrefLangs[$languages['languagemenu'][0]['hreflang']];
            }
        }

        $event->setHrefLangs($hrefLangs);
    }

    protected function getAbsoluteUrl(string $url, SiteLanguage $siteLanguage): string
    {
        $uri = new Uri($url);
        if (empty($uri->getHost())) {
            $url = $siteLanguage->getBase()->withPath($uri->getPath());

            if ($uri->getQuery()) {
                $url = $url->withQuery($uri->getQuery());
            }
        }

        return (string)$url;
    }

    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    protected function getTranslatedPageRecord(int $pageId, int $languageId, Site $site): array
    {
        $targetSiteLanguage = $site->getLanguageById($languageId);
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($targetSiteLanguage);

        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', $languageAspect);

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        $pageRecord = $pageRepository->getPage($pageId);
        // Overlay was requested but did not apply
        if ($languageId > 0 && !($pageRecord['_PAGES_OVERLAY'] ?? false)) {
            return [];
        }
        return $pageRecord;
    }
}
