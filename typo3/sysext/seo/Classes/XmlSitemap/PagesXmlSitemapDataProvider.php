<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Seo\XmlSitemap;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class to generate a XML sitemap for pages
 */
class PagesXmlSitemapDataProvider extends AbstractXmlSitemapDataProvider
{
    public function __construct(ServerRequestInterface $request, string $key, array $config = [], ContentObjectRenderer $cObj = null)
    {
        parent::__construct($request, $key, $config, $cObj);

        $this->generateItems($request);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    public function generateItems(ServerRequestInterface $request): void
    {
        $site = $request->getAttribute('site');
        $rootPageId = $site->getRootPageId();

        $additionalWhere = $this->config['additionalWhere'] ?? '';
        if (!empty($this->config['excludedDoktypes'])) {
            $excludedDoktypes = GeneralUtility::trimExplode(',', $this->config['excludedDoktypes']);
            if (!empty($excludedDoktypes)) {
                $additionalWhere .= ' AND doktype NOT IN (' . implode(',', $excludedDoktypes) . ')';
            }
        }

        $rootPage = $this->getTypoScriptFrontendController()->page;
        $pages = [
            [
                'uid' => $rootPage['uid'],
                'tstamp' => $rootPage['tstamp'],
                'l18n_cfg' => $rootPage['l18n_cfg'],
                'SYS_LASTCHANGED' => $rootPage['SYS_LASTCHANGED']
            ]
        ];

        $pages = $this->getSubPages($rootPageId, $pages, ltrim($additionalWhere));

        $languageId = $this->getCurrentLanguageAspect()->getId();
        foreach ($pages as $page) {
            /**
             * @todo Checking if the page has to be shown/hidden should normally be handled by the
             * PageRepository but to prevent major breaking changes this is checked here for now
             */
            if (
                !(
                    GeneralUtility::hideIfDefaultLanguage($page['l18n_cfg'])
                    && (!$languageId || ($languageId && !$page['_PAGES_OVERLAY']))
                )
                &&
                !(
                    $languageId
                    && GeneralUtility::hideIfNotTranslated($page['l18n_cfg'])
                    && !$page['_PAGES_OVERLAY']
                )
            ) {
                $typoLinkConfig = [
                    'parameter' => $page['uid'],
                    'forceAbsoluteUrl' => 1,
                ];

                $loc = $this->cObj->typoLink_URL($typoLinkConfig);
                $lastMod = $page['SYS_LASTCHANGED'] ?: $page['tstamp'];

                $this->items[] = [
                    'loc' => $loc,
                    'lastMod' => (int)$lastMod
                ];
            }
        }
    }

    /**
     * Get subpages
     *
     * @param int $parentPageId
     * @param array $pages
     * @param string $additionalWhere
     * @return array
     */
    protected function getSubPages(int $parentPageId, array $pages = [], $additionalWhere = ''): array
    {
        $subPages = $this->getTypoScriptFrontendController()->sys_page->getMenu(
            $parentPageId,
            'uid, tstamp, SYS_LASTCHANGED, l18n_cfg',
            'sorting',
            $additionalWhere,
            false
        );
        $pages = array_merge($pages, $subPages);

        foreach ($subPages as $subPage) {
            $pages = $this->getSubPages((int)$subPage['uid'], $pages, $additionalWhere);
        }

        return $pages;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return LanguageAspect
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function getCurrentLanguageAspect(): LanguageAspect
    {
        return GeneralUtility::makeInstance(Context::class)->getAspect('language');
    }
}
