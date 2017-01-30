<?php
namespace TYPO3\CMS\Compatibility7\Hooks;

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
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\ContentObject\TypolinkModifyLinkConfigForPageLinksHookInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Hooks for
 *  - TypoLink when linking to a page and sets forceAbsoluteUrl if the page has
 *  - TSFE to do a redirect if the url_scheme does not match
 */
class EnforceUrlSchemeHook implements TypolinkModifyLinkConfigForPageLinksHookInterface
{
    /**
     * Hooks in typolink when linking to a page
     *
     * Checks if pages.url_scheme has a value and then enforces an absolute URL with the scheme set in there
     *
     * @param array $linkConfiguration the typolink configuration
     * @param array $linkDetails
     * @param array $pageRow the fetched page record
     * @return array the modified link configuration
     */
    public function modifyPageLinkConfiguration(array $linkConfiguration, array $linkDetails, array $pageRow): array
    {
        // If pages.url_scheme is not set or not greater than zero, do not process
        if (!isset($pageRow['url_scheme']) || !($pageRow['url_scheme'] > 0)) {
            return $linkConfiguration;
        }

        // If an absolute URL with explicit scheme is already set, don't do anything
        if (isset($linkConfiguration['forceAbsoluteUrl']) && $linkConfiguration['forceAbsoluteUrl']
            && isset($conf['forceAbsoluteUrl.']['scheme']) && $conf['forceAbsoluteUrl.']['scheme']) {
            return $linkConfiguration;
        }

        // Enable forceAbsoluteUrl so the link configuration actually enters the if() clause in TypoLink
        $linkConfiguration['forceAbsoluteUrl'] = 1;

        // Now explictly override the scheme
        if (!isset($linkConfiguration['forceAbsoluteUrl.'])) {
            $linkConfiguration['forceAbsoluteUrl.'] = [];
        }
        $linkConfiguration['forceAbsoluteUrl.']['scheme'] = (int)$pageRow['url_scheme'] === HttpUtility::SCHEME_HTTP ? 'http' : 'https';
        return $linkConfiguration;
    }

    /**
     * Checks if pages.url_scheme is set, and then redirects to enforce HTTP / HTTPS if it does not match
     *
     * @param array $parameters not in use, as it does not contain useful information for this hook
     * @param TypoScriptFrontendController $parentObject
     */
    public function redirectIfUrlSchemeDoesNotMatch($parameters, $parentObject)
    {
        if (isset($parentObject->page['url_scheme']) && $parentObject->page['url_scheme'] > 0) {
            $newUrl = '';
            $requestUrlScheme = parse_url(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), PHP_URL_SCHEME);
            if ((int)$parentObject->page['url_scheme'] === HttpUtility::SCHEME_HTTP && $requestUrlScheme === 'https') {
                $newUrl = 'http://' . substr(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), 8);
            } elseif ((int)$parentObject->page['url_scheme'] === HttpUtility::SCHEME_HTTPS && $requestUrlScheme === 'http') {
                $newUrl = 'https://' . substr(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), 7);
            }
            if ($newUrl !== '') {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $headerCode = HttpUtility::HTTP_STATUS_303;
                } else {
                    $headerCode = HttpUtility::HTTP_STATUS_301;
                }
                HttpUtility::redirect($newUrl, $headerCode);
            }
        }
    }
}
