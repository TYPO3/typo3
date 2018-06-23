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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
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
        $page = $tsfe->sys_page->getPage($linkDetails['pageuid'], $disableGroupAccessCheck);

        if (empty($page)) {
            throw new UnableToLinkException('Page id "' . $linkDetails['typoLinkParameter'] . '" was not found, so "' . $linkText . '" was not linked.', 1490987336, null, $linkText);
        }
        $language = empty($page['_PAGES_OVERLAY']) ? 0 : $page['_PAGES_OVERLAY_LANGUAGE'];
        if ($language === 0 && GeneralUtility::hideIfDefaultLanguage($page['l18n_cfg'])) {
            throw new UnableToLinkException('Default language of page  "' . $linkDetails['typoLinkParameter'] . '" is hidden, so "' . $linkText . '" was not linked.', 1529527301, null, $linkText);
        }
        if ($language > 0 && !isset($page['_PAGES_OVERLAY']) && GeneralUtility::hideIfNotTranslated($page['l18n_cfg'])) {
            throw new UnableToLinkException('Fallback to default language of page "' . $linkDetails['typoLinkParameter'] . '" is disabled, so "' . $linkText . '" was not linked.', 1529527488, null, $linkText);
        }

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typolinkProcessing']['typolinkModifyParameterForPageLinks'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typolinkProcessing']['typolinkModifyParameterForPageLinks'] as $classData) {
                $hookObject = GeneralUtility::makeInstance($classData);
                if (!$hookObject instanceof TypolinkModifyLinkConfigForPageLinksHookInterface) {
                    throw new \UnexpectedValueException('$hookObject must implement interface ' . TypolinkModifyLinkConfigForPageLinksHookInterface::class, 1483114905);
                }
                /** @var $hookObject TypolinkModifyLinkConfigForPageLinksHookInterface */
                $conf = $hookObject->modifyPageLinkConfiguration($conf, $linkDetails, $page);
            }
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
        // Setting title if blank value to link
        $linkText = $this->parseFallbackLinkTextIfLinkTextIsEmpty($linkText, $page['title']);
        // Query Params:
        $addQueryParams = $conf['addQueryString'] ? $this->contentObjectRenderer->getQueryArguments($conf['addQueryString.']) : '';
        $addQueryParams .= isset($conf['additionalParams.']) ? trim((string)$this->contentObjectRenderer->stdWrap($conf['additionalParams'], $conf['additionalParams.'])) : trim((string)$conf['additionalParams']);
        if ($addQueryParams === '&' || $addQueryParams[0] !== '&') {
            $addQueryParams = '';
        }
        $targetDomain = '';
        $currentDomain = (string)GeneralUtility::getIndpEnv('HTTP_HOST');
        // Mount pages are always local and never link to another domain
        if (!empty($MPvarAcc)) {
            // Add "&MP" var:
            $addQueryParams .= '&MP=' . rawurlencode(implode(',', $MPvarAcc));
        } elseif (strpos($addQueryParams, '&MP=') === false && $tsfe->config['config']['typolinkCheckRootline']) {
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

            $targetDomain = $tsfe->getDomainNameForPid($page['uid']);
            // Do not prepend the domain if it is the current hostname
            if (!$targetDomain || $tsfe->domainNameMatchesCurrentRequest($targetDomain)) {
                $targetDomain = '';
            }
        }
        if ($conf['useCacheHash']) {
            $params = $tsfe->linkVars . $addQueryParams . '&id=' . $page['uid'];
            if (trim($params, '& ') !== '') {
                $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class);
                $cHash = $cacheHash->generateForParameters($params);
                $addQueryParams .= $cHash ? '&cHash=' . $cHash : '';
            }
            unset($params);
        }
        $absoluteUrlScheme = GeneralUtility::getIndpEnv('TYPO3_SSL') ? 'https' : 'http';
        // URL shall be absolute:
        if (isset($conf['forceAbsoluteUrl']) && $conf['forceAbsoluteUrl']) {
            // Override scheme:
            if (isset($conf['forceAbsoluteUrl.']['scheme']) && $conf['forceAbsoluteUrl.']['scheme']) {
                $absoluteUrlScheme = $conf['forceAbsoluteUrl.']['scheme'];
            }
            // If no domain records are defined, use current domain:
            $currentUrlScheme = parse_url(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), PHP_URL_SCHEME);
            if ($targetDomain === '' && ($conf['forceAbsoluteUrl'] || $absoluteUrlScheme !== $currentUrlScheme)) {
                $targetDomain = $currentDomain;
            }
            // If go for an absolute link, add site path if it's not taken care about by absRefPrefix
            if (!$tsfe->config['config']['absRefPrefix'] && $targetDomain === $currentDomain) {
                $targetDomain = $currentDomain . rtrim(GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'), '/');
            }
        }
        // If target page has a different domain and the current domain's linking scheme (e.g. RealURL/...) should not be used
        if ($targetDomain !== '' && $targetDomain !== $currentDomain && !$enableLinksAcrossDomains) {
            $target = $target ?: $this->resolveTargetAttribute($conf, 'extTarget', false, $tsfe->extTarget);
            $LD['target'] = $target;
            // Convert IDNA-like domain (if any)
            if (!preg_match('/^[a-z0-9.\\-]*$/i', $targetDomain)) {
                $targetDomain =  GeneralUtility::idnaEncode($targetDomain);
            }
            $url = $absoluteUrlScheme . '://' . $targetDomain . '/index.php?id=' . $page['uid'] . $addQueryParams . $sectionMark;
        } else {
            // Internal link or current domain's linking scheme should be used
            // Internal target:
            if (empty($target)) {
                $target = $this->resolveTargetAttribute($conf, 'target', true, $tsfe->intTarget);
            }
            $LD = $tsfe->tmpl->linkData($page, $target, $conf['no_cache'], '', '', $addQueryParams, $pageType, $targetDomain);
            if ($targetDomain !== '') {
                // We will add domain only if URL does not have it already.
                if ($enableLinksAcrossDomains && $targetDomain !== $currentDomain && isset($tsfe->config['config']['absRefPrefix'])) {
                    // Get rid of the absRefPrefix if necessary. absRefPrefix is applicable only
                    // to the current web site. If we have domain here it means we link across
                    // domains. absRefPrefix can contain domain name, which will screw up
                    // the link to the external domain.
                    $prefixLength = strlen($tsfe->config['config']['absRefPrefix']);
                    if (substr($LD['totalURL'], 0, $prefixLength) === $tsfe->config['config']['absRefPrefix']) {
                        $LD['totalURL'] = substr($LD['totalURL'], $prefixLength);
                    }
                }
                $urlParts = parse_url($LD['totalURL']);
                if (empty($urlParts['host'])) {
                    $LD['totalURL'] = $absoluteUrlScheme . '://' . $targetDomain . ($LD['totalURL'][0] === '/' ? '' : '/') . $LD['totalURL'];
                }
            }
            $url = $LD['totalURL'] . $sectionMark;
        }
        $target = $LD['target'];
        // If sectionMark is set, there is no baseURL AND the current page is the page the link is to, check if there are any additional parameters or addQueryString parameters and if not, drop the url.
        if ($sectionMark
            && !$tsfe->config['config']['baseURL']
            && (int)$page['uid'] === (int)$tsfe->id
            && !trim($addQueryParams)
            && (empty($conf['addQueryString']) || !isset($conf['addQueryString.']))
        ) {
            $currentQueryArray = GeneralUtility::explodeUrl2Array(GeneralUtility::getIndpEnv('QUERY_STRING'), true);
            $currentQueryParams = GeneralUtility::implodeArrayForUrl('', $currentQueryArray, '', false, true);

            if (!trim($currentQueryParams)) {
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
            $this->contentObjectRenderer->lastTypoLinkLD['totalUrl'] = $url;
        }

        return [$url, $linkText, $target];
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
        $tCR_rootline = $tsfe->sys_page->getRootLine($pageId, '', true);
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
}
