<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Typolink;

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
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Compatibility\LegacyDomainResolver;
use TYPO3\CMS\Frontend\ContentObject\TypolinkModifyLinkConfigForPageLinksHookInterface;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Builds a TypoLink to a certain page
 */
class PageLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     * @throws UnableToLinkException
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): array
    {
        $tsfe = $this->getTypoScriptFrontendController();
        // Checking if the id-parameter is an alias.
        if (!empty($linkDetails['pagealias'])) {
            $linkDetails['pageuid'] = $tsfe->sys_page->getPageIdFromAlias($linkDetails['pagealias']);
        } elseif (empty($linkDetails['pageuid']) || $linkDetails['pageuid'] === 'current') {
            // If no id or alias is given
            $linkDetails['pageuid'] = $tsfe->id;
        }

        // Link to page even if access is missing?
        if (isset($conf['linkAccessRestrictedPages'])) {
            $disableGroupAccessCheck = (bool)$conf['linkAccessRestrictedPages'];
        } else {
            $disableGroupAccessCheck = (bool)$tsfe->config['config']['typolinkLinkAccessRestrictedPages'];
        }

        // Looking up the page record to verify its existence:
        $page = $this->resolvePage($linkDetails, $conf, $disableGroupAccessCheck);

        if (empty($page)) {
            throw new UnableToLinkException('Page id "' . $linkDetails['typoLinkParameter'] . '" was not found, so "' . $linkText . '" was not linked.', 1490987336, null, $linkText);
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typolinkProcessing']['typolinkModifyParameterForPageLinks'] ?? [] as $classData) {
            $hookObject = GeneralUtility::makeInstance($classData);
            if (!$hookObject instanceof TypolinkModifyLinkConfigForPageLinksHookInterface) {
                throw new \UnexpectedValueException('$hookObject must implement interface ' . TypolinkModifyLinkConfigForPageLinksHookInterface::class, 1483114905);
            }
            /** @var TypolinkModifyLinkConfigForPageLinksHookInterface $hookObject */
            $conf = $hookObject->modifyPageLinkConfiguration($conf, $linkDetails, $page);
        }
        $enableLinksAcrossDomains = $tsfe->config['config']['typolinkEnableLinksAcrossDomains'];
        if ($conf['no_cache.']) {
            $conf['no_cache'] = (string)$this->contentObjectRenderer->stdWrap($conf['no_cache'], $conf['no_cache.']);
        }

        $sectionMark = trim(isset($conf['section.']) ? (string)$this->contentObjectRenderer->stdWrap($conf['section'], $conf['section.']) : (string)$conf['section']);
        if ($sectionMark === '' && isset($linkDetails['fragment'])) {
            $sectionMark = $linkDetails['fragment'];
        }
        if ($sectionMark !== '') {
            $sectionMark = '#' . (MathUtility::canBeInterpretedAsInteger($sectionMark) ? 'c' : '') . $sectionMark;
        }
        // Overruling 'type'
        $pageType = $linkDetails['pagetype'] ?? '';

        if (isset($linkDetails['parameters'])) {
            $conf['additionalParams'] .= '&' . ltrim($linkDetails['parameters'], '&');
        }
        // MointPoints, look for closest MPvar:
        $MPvarAcc = [];
        if (!$tsfe->config['config']['MP_disableTypolinkClosestMPvalue']) {
            $temp_MP = $this->getClosestMountPointValueForPage($page['uid']);
            if ($temp_MP) {
                $MPvarAcc['closest'] = $temp_MP;
            }
        }
        // Look for overlay Mount Point:
        $mount_info = $tsfe->sys_page->getMountPointInfo($page['uid'], $page);
        if (is_array($mount_info) && $mount_info['overlay']) {
            $page = $tsfe->sys_page->getPage($mount_info['mount_pid'], $disableGroupAccessCheck);
            if (empty($page)) {
                throw new UnableToLinkException('Mount point "' . $mount_info['mount_pid'] . '" was not available, so "' . $linkText . '" was not linked.', 1490987337, null, $linkText);
            }
            $MPvarAcc['re-map'] = $mount_info['MPvar'];
        }
        // Query Params:
        $addQueryParams = $conf['addQueryString'] ? $this->contentObjectRenderer->getQueryArguments($conf['addQueryString.']) : '';
        $addQueryParams .= isset($conf['additionalParams.']) ? trim((string)$this->contentObjectRenderer->stdWrap($conf['additionalParams'], $conf['additionalParams.'])) : trim((string)$conf['additionalParams']);
        if ($addQueryParams === '&' || $addQueryParams[0] !== '&') {
            $addQueryParams = '';
        }
        // Mount pages are always local and never link to another domain
        if (!empty($MPvarAcc)) {
            // Add "&MP" var:
            $addQueryParams .= '&MP=' . rawurlencode(implode(',', $MPvarAcc));
        } elseif (strpos($addQueryParams, '&MP=') === false) {
            // We do not come here if additionalParams had '&MP='. This happens when typoLink is called from
            // menu. Mount points always work in the content of the current domain and we must not change
            // domain if MP variables exist.
            // If we link across domains and page is free type shortcut, we must resolve the shortcut first!
            // If we do not do it, TYPO3 will fail to (1) link proper page in RealURL/CoolURI because
            // they return relative links and (2) show proper page if no RealURL/CoolURI exists when link is clicked
            if ($enableLinksAcrossDomains
                && (int)$page['doktype'] === PageRepository::DOKTYPE_SHORTCUT
                && (int)$page['shortcut_mode'] === PageRepository::SHORTCUT_MODE_NONE
            ) {
                // Save in case of broken destination or endless loop
                $page2 = $page;
                // Same as in RealURL, seems enough
                $maxLoopCount = 20;
                while ($maxLoopCount
                    && is_array($page)
                    && (int)$page['doktype'] === PageRepository::DOKTYPE_SHORTCUT
                    && (int)$page['shortcut_mode'] === PageRepository::SHORTCUT_MODE_NONE
                ) {
                    $page = $tsfe->sys_page->getPage($page['shortcut'], $disableGroupAccessCheck);
                    $maxLoopCount--;
                }
                if (empty($page) || $maxLoopCount === 0) {
                    // We revert if shortcut is broken or maximum number of loops is exceeded (indicates endless loop)
                    $page = $page2;
                }
            }
        }
        if ($conf['useCacheHash']) {
            $params = $tsfe->linkVars . $addQueryParams . '&id=' . $page['uid'];
            if (trim($params, '& ') !== '') {
                $cHash = GeneralUtility::makeInstance(CacheHashCalculator::class)->generateForParameters($params);
                $addQueryParams .= $cHash ? '&cHash=' . $cHash : '';
            }
            unset($params);
        }

        // get config.linkVars and prepend them before the actual GET parameters
        $queryParameters = [];
        parse_str($addQueryParams, $queryParameters);
        if ($tsfe->linkVars) {
            $globalQueryParameters = [];
            parse_str($tsfe->linkVars, $globalQueryParameters);
            $queryParameters = array_replace_recursive($globalQueryParameters, $queryParameters);
        }
        // Disable "?id=", for pages with no site configuration, this is added later-on anyway
        unset($queryParameters['id']);

        // Override language property if not being set already
        if (isset($queryParameters['L']) && !isset($conf['language'])) {
            $conf['language'] = (int)$queryParameters['L'];
        }

        // Check if the target page has a site configuration
        try {
            $siteOfTargetPage = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId((int)$page['uid'], null, $queryParameters['MP'] ?? '');
            $currentSite = $this->getCurrentSite();
        } catch (SiteNotFoundException $e) {
            // Usually happens in tests, as Pseudo Sites should be available everywhere.
            $siteOfTargetPage = null;
            $currentSite = null;
        }

        // Link to a page that has a site configuration
        if ($siteOfTargetPage !== null) {
            $siteLanguageOfTargetPage = $this->getSiteLanguageOfTargetPage($siteOfTargetPage, (string)($conf['language'] ?? 'current'));
            $languageAspect = LanguageAspectFactory::createFromSiteLanguage($siteLanguageOfTargetPage);

            // Now overlay the page in the target language, in order to have valid title attributes etc.
            if ($siteLanguageOfTargetPage->getLanguageId() > 0) {
                $context = clone GeneralUtility::makeInstance(Context::class);
                $context->setAspect('language', $languageAspect);
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
                $page = $pageRepository->getPageOverlay($page);
            }
            // Check if the target page can be access depending on l18n_cfg
            if (!$tsfe->sys_page->isPageSuitableForLanguage($page, $languageAspect)) {
                if ($siteLanguageOfTargetPage->getLanguageId() === 0 && GeneralUtility::hideIfDefaultLanguage($page['l18n_cfg'])) {
                    throw new UnableToLinkException('Default language of page  "' . $linkDetails['typoLinkParameter'] . '" is hidden, so "' . $linkText . '" was not linked.', 1551621985, null, $linkText);
                }
                // If the requested language is not the default language and the page has no overlay for this language
                // generating a link would cause a 404 error when using this like if one of those conditions apply:
                //  - The page is set to be hidden if it is not translated (evaluated in TSFE)
                //  - The site configuration has a "strict" fallback set (evaluated in the Router - very early)
                if ($siteLanguageOfTargetPage->getLanguageId() > 0 && !isset($page['_PAGES_OVERLAY']) && (GeneralUtility::hideIfNotTranslated($page['l18n_cfg']) || $siteLanguageOfTargetPage->getFallbackType() === 'strict')) {
                    throw new UnableToLinkException('Fallback to default language of page "' . $linkDetails['typoLinkParameter'] . '" is disabled, so "' . $linkText . '" was not linked.', 1551621996, null, $linkText);
                }
            }

            // No need for any L parameter with Site handling
            unset($queryParameters['L']);
            if ($pageType) {
                $queryParameters['type'] = (int)$pageType;
            }
            // Generate the URL
            $url = $this->generateUrlForPageWithSiteConfiguration($page, $siteOfTargetPage, $queryParameters, $sectionMark, $conf);

            $treatAsExternalLink = true;
            // no scheme => always not external
            if (!$url->getScheme() || !$url->getHost()) {
                $treatAsExternalLink = false;
            } else {
                // URL has a scheme, possibly because someone requested a full URL. So now lets check if the URL
                // is on the same site pagetree. If this is the case, we'll treat it as internal
                // @todo: currently this does not check if the target page is a mounted page in a different site,
                // so it is treating this as an absolute URL, which is wrong
                if ($currentSite instanceof Site && $currentSite->getRootPageId() === $siteOfTargetPage->getRootPageId()) {
                    $treatAsExternalLink = false;
                }
            }

            $url = (string)$url;
            if ($treatAsExternalLink) {
                $target = $target ?: $this->resolveTargetAttribute($conf, 'extTarget', false, $tsfe->extTarget);
            } else {
                $target = (isset($page['target']) && trim($page['target'])) ? $page['target'] : $target;
                if (empty($target)) {
                    $target = $this->resolveTargetAttribute($conf, 'target', true, $tsfe->intTarget);
                }
            }
        } else {
            // Now overlay the page in the target language, in order to have valid title attributes etc.
            if (isset($conf['language']) && $conf['language'] > 0 && $conf['language'] !== 'current') {
                $page = $tsfe->sys_page->getPageOverlay($page, (int)$conf['language']);
            }
            if ($conf['language'] === 0 && GeneralUtility::hideIfDefaultLanguage($page['l18n_cfg'])) {
                throw new UnableToLinkException('Default language of page  "' . $linkDetails['typoLinkParameter'] . '" is hidden, so "' . $linkText . '" was not linked.', 1529527301, null, $linkText);
            }
            // If the requested language is not the default language and the page has no overlay for this language
            // generating a link would cause a 404 error when using this like if the page is set to be hidden
            // if it is not translated (evaluated in TSFE)
            if ($conf['language'] > 0 && !isset($page['_PAGES_OVERLAY']) && GeneralUtility::hideIfNotTranslated($page['l18n_cfg'])) {
                throw new UnableToLinkException('Fallback to default language of page "' . $linkDetails['typoLinkParameter'] . '" is disabled, so "' . $linkText . '" was not linked.', 1529527488, null, $linkText);
            }

            // If the typolink.language parameter was set, ensure that this is added to L query parameter
            if (!isset($queryParameters['L']) && MathUtility::canBeInterpretedAsInteger($conf['language'] ?? false)) {
                $queryParameters['L'] = $conf['language'];
            }
            list($url, $target) = $this->generateUrlForPageWithoutSiteConfiguration($page, $queryParameters, $conf, $pageType, $sectionMark, $target, $MPvarAcc);
        }

        // If link is to an access restricted page which should be redirected, then find new URL:
        if (empty($conf['linkAccessRestrictedPages'])
            && $tsfe->config['config']['typolinkLinkAccessRestrictedPages']
            && $tsfe->config['config']['typolinkLinkAccessRestrictedPages'] !== 'NONE'
            && !$tsfe->checkPageGroupAccess($page)
        ) {
            $thePage = $tsfe->sys_page->getPage($tsfe->config['config']['typolinkLinkAccessRestrictedPages']);
            $addParams = str_replace(
                [
                    '###RETURN_URL###',
                    '###PAGE_ID###'
                ],
                [
                    rawurlencode($url),
                    $page['uid']
                ],
                $tsfe->config['config']['typolinkLinkAccessRestrictedPages_addParams']
            );
            $url = $this->contentObjectRenderer->getTypoLink_URL($thePage['uid'] . ($pageType ? ',' . $pageType : ''), $addParams, $target);
            $url = $this->forceAbsoluteUrl($url, $conf);
        }

        // Setting title if blank value to link
        $linkText = $this->parseFallbackLinkTextIfLinkTextIsEmpty($linkText, $page['title']);
        return [$url, $linkText, $target];
    }

    /**
     * Resolves page and if a translated page was found, resolves that to it
     * language parent, adjusts `$linkDetails['pageuid']` (for hook processing)
     * and modifies `$configuration['language']` (for language URL generation).
     *
     * @param array $linkDetails
     * @param array $configuration
     * @param bool $disableGroupAccessCheck
     * @return array
     */
    protected function resolvePage(array &$linkDetails, array &$configuration, bool $disableGroupAccessCheck): array
    {
        $pageRepository = $this->buildPageRepository();
        // Looking up the page record to verify its existence
        // This is used when a page to a translated page is executed directly.
        $page = $pageRepository->getPage($linkDetails['pageuid'], $disableGroupAccessCheck);

        if (empty($page) || !is_array($page)) {
            return [];
        }

        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? null;
        $languageParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? null;
        $language = (int)($page[$languageField] ?? 0);

        // The page that should be linked is actually a default-language page, nothing to do here.
        if ($language === 0 || empty($page[$languageParentField])) {
            return $page;
        }

        // Let's fetch the default-language page now
        $languageParentPage = $pageRepository->getPage(
            $page[$languageParentField],
            $disableGroupAccessCheck
        );
        if (empty($languageParentPage)) {
            return $page;
        }

        // Set the "pageuid" to the default-language page ID.
        $linkDetails['pageuid'] = (int)$languageParentPage['uid'];
        $configuration['language'] = $language;
        $linkDetails['parameters'] .= '&L=' . $language;
        return $languageParentPage;
    }

    /**
     * Fetches the requested language of a site that the link should be built for
     *
     * @param Site $siteOfTargetPage
     * @param string $targetLanguageId "current" or the languageId
     * @return SiteLanguage
     * @throws UnableToLinkException
     */
    protected function getSiteLanguageOfTargetPage(Site $siteOfTargetPage, string $targetLanguageId): SiteLanguage
    {
        $currentSite = $this->getCurrentSite();
        $currentSiteLanguage = $this->getCurrentSiteLanguage();
        // Happens when currently on a pseudo-site configuration
        // We assume to use the default language then
        if ($currentSite && !($currentSiteLanguage instanceof SiteLanguage)) {
            $currentSiteLanguage = $currentSite->getDefaultLanguage();
        }

        if ($targetLanguageId === 'current') {
            $targetLanguageId = $currentSiteLanguage ? $currentSiteLanguage->getLanguageId() : 0;
        } else {
            $targetLanguageId = (int)$targetLanguageId;
        }
        try {
            $siteLanguageOfTargetPage = $siteOfTargetPage->getLanguageById($targetLanguageId);
        } catch (\InvalidArgumentException $e) {
            throw new UnableToLinkException('The target page does not have a language with ID ' . $targetLanguageId . ' configured in its site configuration.', 1535477406);
        }
        return $siteLanguageOfTargetPage;
    }

    /**
     * Create a UriInterface object when linking to a page with a site configuration
     *
     * @param array $page
     * @param Site $siteOfTargetPage
     * @param array $queryParameters
     * @param string $fragment
     * @param array $conf
     * @return UriInterface
     * @throws UnableToLinkException
     */
    protected function generateUrlForPageWithSiteConfiguration(array $page, Site $siteOfTargetPage, array $queryParameters, string $fragment, array $conf): UriInterface
    {
        $currentSite = $this->getCurrentSite();
        $currentSiteLanguage = $this->getCurrentSiteLanguage();
        // Happens when currently on a pseudo-site configuration
        // We assume to use the default language then
        if ($currentSite && !($currentSiteLanguage instanceof SiteLanguage)) {
            $currentSiteLanguage = $currentSite->getDefaultLanguage();
        }

        $siteLanguageOfTargetPage = $this->getSiteLanguageOfTargetPage($siteOfTargetPage, (string)($conf['language'] ?? 'current'));

        // By default, it is assumed to ab an internal link or current domain's linking scheme should be used
        // Use the config option to override this.
        $useAbsoluteUrl = $conf['forceAbsoluteUrl'] ?? false;
        // Check if the current page equal to the site of the target page, now only set the absolute URL
        // Always generate absolute URLs if no current site is set
        if (
            !$currentSite
            || $currentSite->getRootPageId() !== $siteOfTargetPage->getRootPageId()
            || $siteLanguageOfTargetPage->getBase()->getHost() !== $currentSiteLanguage->getBase()->getHost()) {
            $useAbsoluteUrl = true;
        }

        $targetPageId = (int)($page['l10n_parent'] > 0 ? $page['l10n_parent'] : $page['uid']);
        $queryParameters['_language'] = $siteLanguageOfTargetPage;

        if ($conf['no_cache']) {
            $queryParameters['no_cache'] = 1;
        }

        if ($fragment
            && $useAbsoluteUrl === false
            && $currentSiteLanguage === $siteLanguageOfTargetPage
            && $targetPageId === (int)$GLOBALS['TSFE']->id
            && (empty($conf['addQueryString']) || !isset($conf['addQueryString.']))
            && !$GLOBALS['TSFE']->config['config']['baseURL']
            && count($queryParameters) === 1 // _language is always set
            ) {
            $uri = (new Uri())->withFragment($fragment);
        } else {
            try {
                $uri = $siteOfTargetPage->getRouter()->generateUri(
                    $targetPageId,
                    $queryParameters,
                    $fragment,
                    $useAbsoluteUrl ? RouterInterface::ABSOLUTE_URL : RouterInterface::ABSOLUTE_PATH
                );
            } catch (InvalidRouteArgumentsException $e) {
                throw new UnableToLinkException('The target page could not be linked. Error: ' . $e->getMessage(), 1535472406);
            }
            // Override scheme, but only if the site does not define a scheme yet AND the site defines a domain/host
            if ($useAbsoluteUrl && !$uri->getScheme() && $uri->getHost()) {
                $scheme = $conf['forceAbsoluteUrl.']['scheme'] ?? 'https';
                $uri = $uri->withScheme($scheme);
            }
        }

        return $uri;
    }

    /**
     * Generate a URL for a page without site configuration
     *
     * @param array $page
     * @param array $additionalQueryParams
     * @param array $conf
     * @param string $pageType
     * @param string $sectionMark
     * @param string $target
     * @param array $MPvarAcc
     * @return array
     */
    protected function generateUrlForPageWithoutSiteConfiguration(array $page, array $additionalQueryParams, array $conf, string $pageType, string $sectionMark, string $target, array $MPvarAcc): array
    {
        // Build a string out of the query parameters
        $additionalQueryParams = http_build_query($additionalQueryParams, '', '&', PHP_QUERY_RFC3986);
        if (!empty($additionalQueryParams)) {
            $additionalQueryParams = '&' . $additionalQueryParams;
        }

        $tsfe = $this->getTypoScriptFrontendController();
        $enableLinksAcrossDomains = $tsfe->config['config']['typolinkEnableLinksAcrossDomains'];
        $targetDomain = '';
        $currentDomain = (string)GeneralUtility::getIndpEnv('HTTP_HOST');
        if (!empty($MPvarAcc)) {
            $domainResolver = GeneralUtility::makeInstance(LegacyDomainResolver::class);
            $targetDomainRecord = $domainResolver->matchPageId((int)$page['uid'], $GLOBALS['TYPO3_REQUEST']);
            // Do not prepend the domain if it is the current hostname
            if (!empty($targetDomainRecord) && !$targetDomainRecord['isCurrentDomain']) {
                $targetDomain = $targetDomainRecord['domainName'];
            }
        }
        $absoluteUrlScheme = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https' : 'http';
        // URL shall be absolute:
        if (isset($conf['forceAbsoluteUrl']) && $conf['forceAbsoluteUrl']) {
            // Override scheme:
            if (isset($conf['forceAbsoluteUrl.']['scheme']) && $conf['forceAbsoluteUrl.']['scheme']) {
                $absoluteUrlScheme = $conf['forceAbsoluteUrl.']['scheme'];
            }
            // If no domain records are defined, use current domain
            $targetDomain = $targetDomain ?: $currentDomain;
            // If go for an absolute link, add site path if it's not taken care about by absRefPrefix
            if (!$tsfe->absRefPrefix && $targetDomain === $currentDomain) {
                $targetDomain = $currentDomain . rtrim(GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'), '/');
            }
        }
        // If target page has a different domain and the current domain's linking scheme (e.g. RealURL/...) should not be used
        if ($targetDomain !== '' && $targetDomain !== $currentDomain && !$enableLinksAcrossDomains) {
            $target = $target ?: $this->resolveTargetAttribute($conf, 'extTarget', false, $tsfe->extTarget);
            // Convert IDNA-like domain (if any)
            if (!preg_match('/^[a-z0-9.\\-]*$/i', $targetDomain)) {
                $targetDomain = GeneralUtility::idnaEncode($targetDomain);
            }
            $url = $absoluteUrlScheme . '://' . $targetDomain . '/index.php?id=' . $page['uid'] . $additionalQueryParams;
        } else {
            // Internal link or current domain's linking scheme should be used
            // Internal target:
            $target = (isset($page['target']) && trim($page['target'])) ? $page['target'] : $target;
            if (empty($target)) {
                $target = $this->resolveTargetAttribute($conf, 'target', true, $tsfe->intTarget);
            }
            $LD = $this->createTotalUrlAndLinkData($page, $target, $conf['no_cache'], $additionalQueryParams, $pageType, $targetDomain);
            if ($targetDomain !== '') {
                // We will add domain only if URL does not have it already.
                if ($enableLinksAcrossDomains && $targetDomain !== $currentDomain && !empty($tsfe->absRefPrefix)) {
                    // Get rid of the absRefPrefix if necessary. absRefPrefix is applicable only
                    // to the current web site. If we have domain here it means we link across
                    // domains. absRefPrefix can contain domain name, which will screw up
                    // the link to the external domain.
                    $prefixLength = strlen($tsfe->absRefPrefix);
                    if (strpos($LD['totalURL'], $tsfe->absRefPrefix) === 0) {
                        $LD['totalURL'] = substr($LD['totalURL'], $prefixLength);
                    }
                }
                $urlParts = parse_url($LD['totalURL']);
                if (empty($urlParts['host'])) {
                    $LD['totalURL'] = $absoluteUrlScheme . '://' . $targetDomain . ($LD['totalURL'][0] === '/' ? '' : '/') . $LD['totalURL'];
                }
            }
            $url = $LD['totalURL'];
        }
        $url .= $sectionMark;
        // If sectionMark is set, there is no baseURL AND the current page is the page the link is to,
        // check if there are any additional parameters or addQueryString parameters and if not, drop the url.
        if ($sectionMark
            && !$tsfe->config['config']['baseURL']
            && (int)$page['uid'] === (int)$tsfe->id
            && !trim($additionalQueryParams)
            && (empty($conf['addQueryString']) || !isset($conf['addQueryString.']))
        ) {
            $currentQueryArray = [];
            parse_str(GeneralUtility::getIndpEnv('QUERY_STRING'), $currentQueryArray);

            if (empty($currentQueryArray)) {
                list(, $URLparams) = explode('?', $url);
                list($URLparams) = explode('#', (string)$URLparams);
                parse_str($URLparams . $LD['orig_type'], $URLparamsArray);
                // Type nums must match as well as page ids
                if ((int)$URLparamsArray['type'] === (int)$tsfe->type) {
                    unset($URLparamsArray['id']);
                    unset($URLparamsArray['type']);
                    // If there are no parameters left.... set the new url.
                    if (empty($URLparamsArray)) {
                        $url = $sectionMark;
                    }
                }
            }
        }
        return [$url, $target];
    }

    /**
     * Returns the &MP variable value for a page id.
     * The function will do its best to find a MP value that will keep the page id inside the current Mount Point rootline if any.
     *
     * @param int $pageId page id
     * @return string MP value, prefixed with &MP= (depending on $raw)
     */
    protected function getClosestMountPointValueForPage($pageId)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        if (empty($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids']) || !$tsfe->MP) {
            return '';
        }
        // Same page as current.
        if ((int)$tsfe->id === (int)$pageId) {
            return $tsfe->MP;
        }

        // Find closest mount point
        // Gets rootline of linked-to page
        try {
            $tCR_rootline = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
        } catch (RootLineException $e) {
            $tCR_rootline = [];
        }
        $inverseTmplRootline = array_reverse($tsfe->tmpl->rootLine);
        $rl_mpArray = [];
        $startMPaccu = false;
        // Traverse root line of link uid and inside of that the REAL root line of current position.
        foreach ($tCR_rootline as $tCR_data) {
            foreach ($inverseTmplRootline as $rlKey => $invTmplRLRec) {
                // Force accumulating when in overlay mode: Links to this page have to stay within the current branch
                if ($invTmplRLRec['_MOUNT_OL'] && (int)$tCR_data['uid'] === (int)$invTmplRLRec['uid']) {
                    $startMPaccu = true;
                }
                // Accumulate MP data:
                if ($startMPaccu && $invTmplRLRec['_MP_PARAM']) {
                    $rl_mpArray[] = $invTmplRLRec['_MP_PARAM'];
                }
                // If two PIDs matches and this is NOT the site root, start accumulation of MP data (on the next level):
                // (The check for site root is done so links to branches outsite the site but sharing the site roots PID
                // is NOT detected as within the branch!)
                if ((int)$tCR_data['pid'] === (int)$invTmplRLRec['pid'] && count($inverseTmplRootline) !== $rlKey + 1) {
                    $startMPaccu = true;
                }
            }
            if ($startMPaccu) {
                // Good enough...
                break;
            }
        }
        return !empty($rl_mpArray) ? implode(',', array_reverse($rl_mpArray)) : '';
    }

    /**
     * Initializes the automatically created mountPointMap coming from the "config.MP_mapRootPoints" setting
     * Can be called many times with overhead only the first time since then the map is generated and cached in memory.
     *
     * Previously located within TemplateService::getFromMPmap()
     *
     * @param int $pageId Page id to return MPvar value for.
     * @return string
     */
    public function getMountPointParameterFromRootPointMaps(int $pageId)
    {
        // Create map if not found already
        $config = $this->getTypoScriptFrontendController()->config;
        $mountPointMap = $this->initializeMountPointMap(
            !empty($config['config']['MP_defaults']) ? $config['config']['MP_defaults'] : null,
            !empty($config['config']['MP_mapRootPoints']) ? $config['config']['MP_mapRootPoints'] : null
        );

        // Finding MP var for Page ID:
        if (!empty($mountPointMap[$pageId])) {
            return implode(',', $mountPointMap[$pageId]);
        }
        return '';
    }

    /**
     * Create mount point map, based on TypoScript config.MP_mapRootPoints and config.MP_defaults.
     *
     * @param string $defaultMountPoints a string as defined in config.MP_defaults
     * @param string|null $mapRootPointList a string as defined in config.MP_mapRootPoints
     * @return array
     */
    protected function initializeMountPointMap(string $defaultMountPoints = null, string $mapRootPointList = null): array
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
        $mountPointMap = $runtimeCache->get('pageLinkBuilderMountPointMap') ?: [];
        if (!empty($mountPointMap) || (empty($mapRootPointList) && empty($defaultMountPoints))) {
            return $mountPointMap;
        }
        if ($defaultMountPoints) {
            $defaultMountPoints = GeneralUtility::trimExplode('|', $defaultMountPoints, true);
            foreach ($defaultMountPoints as $temp_p) {
                list($temp_idP, $temp_MPp) = explode(':', $temp_p, 2);
                $temp_ids = GeneralUtility::intExplode(',', $temp_idP);
                foreach ($temp_ids as $temp_id) {
                    $mountPointMap[$temp_id] = trim($temp_MPp);
                }
            }
        }

        $rootPoints = GeneralUtility::trimExplode(',', strtolower($mapRootPointList), true);
        // Traverse rootpoints
        foreach ($rootPoints as $p) {
            $initMParray = [];
            if ($p === 'root') {
                $rootPage = $this->getTypoScriptFrontendController()->tmpl->rootLine[0];
                $p = $rootPage['uid'];
                if ($rootPage['_MOUNT_OL'] && $rootPage['_MP_PARAM']) {
                    $initMParray[] = $rootPage['_MP_PARAM'];
                }
            }
            $this->populateMountPointMapForPageRecursively($mountPointMap, (int)$p, $initMParray);
        }
        $runtimeCache->set('pageLinkBuilderMountPointMap', $mountPointMap);
        return $mountPointMap;
    }

    /**
     * Creating mountPointMap for a certain ID root point.
     * Previously called TemplateService->initMPmap_create()
     *
     * @param array $mountPointMap the exiting mount point map
     * @param int $id Root id from which to start map creation.
     * @param array $MP_array MP_array passed from root page.
     * @param int $level Recursion brake. Incremented for each recursive call. 20 is the limit.
     * @see getMountPointParameterFromRootPointMaps()
     */
    protected function populateMountPointMapForPageRecursively(array &$mountPointMap, int $id, $MP_array = [], $level = 0)
    {
        if ($id <= 0) {
            return;
        }
        // First level, check id
        if (!$level) {
            // Find mount point if any:
            $mount_info = $this->getTypoScriptFrontendController()->sys_page->getMountPointInfo($id);
            // Overlay mode:
            if (is_array($mount_info) && $mount_info['overlay']) {
                $MP_array[] = $mount_info['MPvar'];
                $id = $mount_info['mount_pid'];
            }
            // Set mapping information for this level:
            $mountPointMap[$id] = $MP_array;
            // Normal mode:
            if (is_array($mount_info) && !$mount_info['overlay']) {
                $MP_array[] = $mount_info['MPvar'];
                $id = $mount_info['mount_pid'];
            }
        }
        if ($id && $level < 20) {
            $nextLevelAcc = [];
            // Select and traverse current level pages:
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryResult = $queryBuilder
                ->select('uid', 'pid', 'doktype', 'mount_pid', 'mount_pid_ol', 't3ver_state')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        'doktype',
                        $queryBuilder->createNamedParameter(PageRepository::DOKTYPE_RECYCLER, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        'doktype',
                        $queryBuilder->createNamedParameter(PageRepository::DOKTYPE_BE_USER_SECTION, \PDO::PARAM_INT)
                    )
                )->execute();
            while ($row = $queryResult->fetch()) {
                // Find mount point if any:
                $next_id = (int)$row['uid'];
                $next_MP_array = $MP_array;
                $mount_info = $this->getTypoScriptFrontendController()->sys_page->getMountPointInfo($next_id, $row);
                // Overlay mode:
                if (is_array($mount_info) && $mount_info['overlay']) {
                    $next_MP_array[] = $mount_info['MPvar'];
                    $next_id = (int)$mount_info['mount_pid'];
                }
                if (!isset($mountPointMap[$next_id])) {
                    // Set mapping information for this level:
                    $mountPointMap[$next_id] = $next_MP_array;
                    // Normal mode:
                    if (is_array($mount_info) && !$mount_info['overlay']) {
                        $next_MP_array[] = $mount_info['MPvar'];
                        $next_id = (int)$mount_info['mount_pid'];
                    }
                    // Register recursive call
                    // (have to do it this way since ALL of the current level should be registered BEFORE the sublevel at any time)
                    $nextLevelAcc[] = [$next_id, $next_MP_array];
                }
            }
            // Call recursively, if any:
            foreach ($nextLevelAcc as $pSet) {
                $this->populateMountPointMapForPageRecursively($mountPointMap, $pSet[0], $pSet[1], $level + 1);
            }
        }
    }

    /**
     * The mother of all functions creating links/URLs etc in a TypoScript environment.
     * See the references below.
     * Basically this function takes care of issues such as type,id,alias and Mount Points, URL rewriting (through hooks), M5/B6 encoded parameters etc.
     * It is important to pass all links created through this function since this is the guarantee that globally configured settings for link creating are observed and that your applications will conform to the various/many configuration options in TypoScript Templates regarding this.
     *
     * @param array $page The page record of the page to which we are creating a link. Needed due to fields like uid, alias, target, title and sectionIndex_uid.
     * @param string $target Target string
     * @param bool $no_cache If set, then the "&no_cache=1" parameter is included in the URL.
     * @param string $addParams Additional URL parameters to set in the URL. Syntax is "&foo=bar&foo2=bar2" etc. Also used internally to add parameters if needed.
     * @param string $typeOverride If you set this value to something else than a blank string, then the typeNumber used in the link will be forced to this value. Normally the typeNum is based on the target set OR on $this->getTypoScriptFrontendController()->config['config']['forceTypeValue'] if found.
     * @param string $targetDomain The target Doamin, if any was detected in typolink
     * @return array Contains keys like "totalURL", "url", "sectionIndex", "linkVars", "no_cache", "type" of which "totalURL" is normally the value you would use while the other keys contains various parts that was used to construct "totalURL
     */
    protected function createTotalUrlAndLinkData($page, $target, $no_cache, $addParams = '', $typeOverride = '', $targetDomain = '')
    {
        $LD = [];
        // Adding Mount Points, "&MP=", parameter for the current page if any is set
        // but non other set explicitly
        if (strpos($addParams, '&MP=') === false) {
            $mountPointParameter = $this->getMountPointParameterFromRootPointMaps((int)$page['uid']);
            if ($mountPointParameter) {
                $addParams .= '&MP=' . rawurlencode($mountPointParameter);
            }
        }
        // Setting ID/alias:
        $script = 'index.php';
        if ($page['alias']) {
            $LD['url'] = $script . '?id=' . rawurlencode($page['alias']);
        } else {
            $LD['url'] = $script . '?id=' . $page['uid'];
        }
        // typeNum
        $typeNum = $this->getTypoScriptFrontendController()->tmpl->setup[$target . '.']['typeNum'];
        if (!MathUtility::canBeInterpretedAsInteger($typeOverride) && (int)$this->getTypoScriptFrontendController()->config['config']['forceTypeValue']) {
            $typeOverride = (int)$this->getTypoScriptFrontendController()->config['config']['forceTypeValue'];
        }
        if ((string)$typeOverride !== '') {
            $typeNum = $typeOverride;
        }
        // Override...
        if ($typeNum) {
            $LD['type'] = '&type=' . (int)$typeNum;
        } else {
            $LD['type'] = '';
        }
        // Preserving the type number.
        $LD['orig_type'] = $LD['type'];
        // noCache
        $LD['no_cache'] = $no_cache ? '&no_cache=1' : '';
        // linkVars
        if ($addParams) {
            $LD['linkVars'] = HttpUtility::buildQueryString(GeneralUtility::explodeUrl2Array($this->getTypoScriptFrontendController()->linkVars . $addParams), '&');
        } else {
            $LD['linkVars'] = $this->getTypoScriptFrontendController()->linkVars;
        }
        // Add absRefPrefix if exists.
        $LD['url'] = $this->getTypoScriptFrontendController()->absRefPrefix . $LD['url'];
        // If the special key 'sectionIndex_uid' (added 'manually' in tslib/menu.php to the page-record) is set, then the link jumps directly to a section on the page.
        $LD['sectionIndex'] = $page['sectionIndex_uid'] ? '#c' . $page['sectionIndex_uid'] : '';
        // Compile the normal total url
        $LD['totalURL'] = rtrim($LD['url'] . $LD['type'] . $LD['no_cache'] . $LD['linkVars'] . $this->getTypoScriptFrontendController()->getMethodUrlIdToken, '?') . $LD['sectionIndex'];
        // Call post processing function for link rendering:
        $_params = [
            'LD' => &$LD,
            'args' => ['page' => $page, 'oTarget' => $target, 'no_cache' => $no_cache, 'script' => $script, 'addParams' => $addParams, 'typeOverride' => $typeOverride, 'targetDomain' => $targetDomain],
            'typeNum' => $typeNum
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this->getTypoScriptFrontendController()->tmpl);
        }
        return $LD;
    }

    /**
     * Check if we have a site object in the current request. if null, this usually means that
     * this class was called from CLI context.
     *
     * @return SiteInterface|null
     */
    protected function getCurrentSite(): ?SiteInterface
    {
        if ($GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            return $GLOBALS['TYPO3_REQUEST']->getAttribute('site', null);
        }
        if (MathUtility::canBeInterpretedAsInteger($GLOBALS['TSFE']->id) && $GLOBALS['TSFE']->id > 0) {
            $matcher = GeneralUtility::makeInstance(SiteMatcher::class);
            try {
                $site = $matcher->matchByPageId((int)$GLOBALS['TSFE']->id);
            } catch (SiteNotFoundException $e) {
                $site = null;
            }
            return $site;
        }
        return null;
    }

    /**
     * If the current request has a site language, this means that the SiteResolver has detected a
     * page with a site configuration and a selected language, so let's choose that one.
     *
     * @return SiteLanguage|null
     */
    protected function getCurrentSiteLanguage(): ?SiteLanguage
    {
        if ($GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            return $GLOBALS['TYPO3_REQUEST']->getAttribute('language', null);
        }
        return null;
    }

    /**
     * Builds PageRepository instance without depending on global context, e.g.
     * not automatically overlaying records based on current request language.
     *
     * @return PageRepository
     */
    protected function buildPageRepository(): PageRepository
    {
        // clone global context object (singleton)
        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect(
            'language',
            GeneralUtility::makeInstance(LanguageAspect::class)
        );
        $pageRepository = GeneralUtility::makeInstance(
            PageRepository::class,
            $context
        );
        return $pageRepository;
    }
}
