<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Seo\HrefLang;

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

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;

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
     * @var TypoScriptFrontendController
     */
    protected $typoScriptFrontendController;

    /**
     * HreflangGenerator constructor
     *
     * @param ContentObjectRenderer|null $cObj
     * @param TypoScriptFrontendController|null $typoScriptFrontendController
     */
    public function __construct(ContentObjectRenderer $cObj = null, TypoScriptFrontendController $typoScriptFrontendController = null)
    {
        if ($cObj === null) {
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        }
        if ($typoScriptFrontendController === null) {
            $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        }

        $this->cObj = $cObj;
        $this->typoScriptFrontendController = $typoScriptFrontendController;
    }

    public function generate(): string
    {
        $hreflangs = [];
        if ((int)$this->typoScriptFrontendController->page['no_index'] === 1) {
            return '';
        }

        if ($GLOBALS['TYPO3_REQUEST']->getAttribute('site') instanceof Site) {
            $languageMenu = GeneralUtility::makeInstance(LanguageMenuProcessor::class);
            $languages = $languageMenu->process($this->cObj, [], [], []);
            foreach ($languages['languagemenu'] as $language) {
                if ($language['available'] === 1) {
                    $href = $this->getAbsoluteUrl($language['link']);
                    $hreflangs[] =
                        '<link rel="alternate" hreflang="' . htmlspecialchars($language['hreflang']) . '" href="' . htmlspecialchars($href) . '"/>';
                }
            }

            if (count($hreflangs) > 1) {
                $href = $this->getAbsoluteUrl($languages['languagemenu'][0]['link']);
                $hreflangs[] =
                    '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($href) . '"/>' . LF;

                $GLOBALS['TSFE']->additionalHeaderData[] = implode(LF, $hreflangs);
            }
        }

        return implode(LF, $hreflangs);
    }

    /**
     * @param string $url
     * @return string
     */
    protected function getAbsoluteUrl(string $url): string
    {
        $uri = new Uri($url);
        if (empty($uri->getHost())) {
            $url = $this->getSiteLanguage()->getBase()->withPath($uri->getPath());

            if ($uri->getQuery()) {
                $url = $url->withQuery($uri->getQuery());
            }
        }

        return (string)$url;
    }

    /**
     * @return SiteLanguage
     */
    protected function getSiteLanguage(): SiteLanguage
    {
        return $GLOBALS['TYPO3_REQUEST']->getAttribute('language');
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
