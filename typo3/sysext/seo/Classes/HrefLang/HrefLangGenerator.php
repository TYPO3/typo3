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
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
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
    /**
     * The content object renderer
     *
     * @var ContentObjectRenderer
     */
    protected $cObj;

    /**
     * @var LanguageMenuProcessor
     */
    protected $languageMenuProcessor;

    public function __construct(ContentObjectRenderer $cObj, LanguageMenuProcessor $languageMenuProcessor)
    {
        $this->cObj = $cObj;
        $this->languageMenuProcessor = $languageMenuProcessor;
    }

    public function __invoke(ModifyHrefLangTagsEvent $event): void
    {
        $hrefLangs = $event->getHrefLangs();
        if ((int)$this->getTypoScriptFrontendController()->page['no_index'] === 1) {
            return;
        }

        $this->cObj->setRequest($event->getRequest());
        $languages = $this->languageMenuProcessor->process($this->cObj, [], [], []);
        $site = $this->getTypoScriptFrontendController()->getSite();
        $siteLanguage = $this->getTypoScriptFrontendController()->getLanguage();
        $pageId = (int)$this->getTypoScriptFrontendController()->id;

        foreach ($languages['languagemenu'] as $language) {
            if (!empty($language['link']) && $language['hreflang']) {
                $page = $this->getTranslatedPageRecord($pageId, $language['languageId'], $site);
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

    /**
     * @param string $url
     * @param SiteLanguage $siteLanguage
     * @return string
     */
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

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    protected function getTranslatedPageRecord(int $pageId, int $languageId, SiteInterface $site): array
    {
        $targetSiteLanguage = $site->getLanguageById($languageId);
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($targetSiteLanguage);

        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', $languageAspect);

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        if ($languageId > 0) {
            return $pageRepository->getPageOverlay($pageId, $languageId);
        }
        return $pageRepository->getPage($pageId);
    }
}
