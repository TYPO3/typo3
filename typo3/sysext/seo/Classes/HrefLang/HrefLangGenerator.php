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

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
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
    public $cObj;

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

        $languages = $this->languageMenuProcessor->process($this->cObj, [], [], []);
        /** @var SiteLanguage $siteLanguage */
        $siteLanguage = $event->getRequest()->getAttribute('language');
        foreach ($languages['languagemenu'] as $language) {
            if ($language['available'] === 1 && !empty($language['link'])) {
                $href = $this->getAbsoluteUrl($language['link'], $siteLanguage);
                $hrefLangs[$language['hreflang']] = $href;
            }
        }

        if (count($hrefLangs) > 1) {
            $href = $this->getAbsoluteUrl($languages['languagemenu'][0]['link'], $siteLanguage);
            $hrefLangs['x-default'] = $href;
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
}
