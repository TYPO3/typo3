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

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;

/**
 * Class to add the metatags for the SEO fields in core
 *
 * @internal
 */
class HreflangGenerator
{
    /**
     * The content object renderer
     *
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * HreflangGenerator constructor
     *
     * @param ContentObjectRenderer $cObj
     */
    public function __construct(ContentObjectRenderer $cObj = null)
    {
        if ($cObj === null) {
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        }
        $this->cObj = $cObj;
    }

    public function generate(): string
    {
        $hreflangs = '';
        if ($GLOBALS['TYPO3_REQUEST']->getAttribute('site') instanceof Site) {
            $languageMenu = GeneralUtility::makeInstance(LanguageMenuProcessor::class);
            $languages = $languageMenu->process($this->cObj, [], [], []);
            $hreflangs = '' . LF;
            foreach ($languages['languagemenu'] as $language) {
                if ($language['available'] == 1) {
                    $hreflangs .= '<link rel="alternate" hreflang="' . $language['hreflang'] . '" href="' . $language['link'] . '"/>' . LF;
                }
            }
            $hreflangs .= '<link rel="alternate" hreflang="x-default" href="' . $languages['languagemenu'][0]['link'] . '"/>' . LF;
            $GLOBALS['TSFE']->additionalHeaderData[] = $hreflangs;
        }
        return $hreflangs;
    }
}
