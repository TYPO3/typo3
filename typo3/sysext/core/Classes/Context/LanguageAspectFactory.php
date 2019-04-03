<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Context;

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

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A simple factory to create a language aspect.
 */
class LanguageAspectFactory
{
    /**
     * Create a language aspect based on TypoScript settings, which we know:
     *
     * config.sys_language_uid
     * config.sys_language_mode
     * config.sys_language_overlay
     *
     * @param array $config
     * @return LanguageAspect
     */
    public static function createFromTypoScript(array $config): LanguageAspect
    {
        // Get values from TypoScript, if not set before
        $languageId = (int)($config['sys_language_uid'] ?? 0);
        $fallbacks = GeneralUtility::trimExplode(';', $config['sys_language_mode'] ?? '');
        $fallbackMode = null;
        if (isset($fallbacks[0])) {
            $fallbackMode = $fallbacks[0];
        }
        $fallbackOrder = null;
        if (isset($fallbacks[1])) {
            $fallbackOrder = $fallbacks[1];
        }

        // Page resolving
        switch ($fallbackMode) {
            case 'strict':
                $fallBackOrder = [];
                break;
                // Ignore anything if a page cannot be found, and resolve pageId=0 instead.
            case 'ignore':
                $fallBackOrder = [-1];
                break;
            case 'fallback':
            case 'content_fallback':
                if (!empty($fallbackOrder)) {
                    $fallBackOrder = GeneralUtility::trimExplode(',', $fallbackOrder);
                    // no strict typing explictly done here
                    if (!in_array(0, $fallBackOrder) && !in_array('pageNotFound', $fallBackOrder)) {
                        $fallBackOrder[] = 'pageNotFound';
                    }
                } else {
                    $fallBackOrder = [0];
                }
                break;
            case '':
                $fallBackOrder = ['off'];
                break;
            default:
                $fallBackOrder = [0];
        }

        // Content fetching
        switch ((string)($config['sys_language_overlay'] ?? '')) {
            case '1':
                $overlayType = LanguageAspect::OVERLAYS_MIXED;
                break;
            case '0':
                $overlayType = LanguageAspect::OVERLAYS_OFF;
                break;
            case 'hideNonTranslated':
                $overlayType = LanguageAspect::OVERLAYS_ON;
                break;
            default:
                $overlayType = LanguageAspect::OVERLAYS_ON_WITH_FLOATING;
        }

        return GeneralUtility::makeInstance(LanguageAspect::class, $languageId, $languageId, $overlayType, $fallBackOrder);
    }

    /**
     * Site Languages always run with overlays + floating records.
     *
     * @param SiteLanguage $language
     * @return LanguageAspect
     */
    public static function createFromSiteLanguage(SiteLanguage $language): LanguageAspect
    {
        $languageId = $language->getLanguageId();
        $fallbackType = $language->getFallbackType();
        $fallbackOrder = $language->getFallbackLanguageIds();
        $fallbackOrder[] = 'pageNotFound';
        switch ($fallbackType) {
            // Fall back to other language, if the page does not exist in the requested language
            // But always fetch only records of this specific (available) language
            case 'free':
                $overlayType = LanguageAspect::OVERLAYS_OFF;
                break;

            // Fall back to other language, if the page does not exist in the requested language
            // Do overlays, and keep the ones that are not translated
            case 'fallback':
                $overlayType = LanguageAspect::OVERLAYS_MIXED;
                break;

            // Same as "fallback" but remove the records that are not translated
            case 'strict':
                $overlayType = LanguageAspect::OVERLAYS_ON_WITH_FLOATING;
                break;

            // Ignore, fallback to default language
            default:
                $fallbackOrder = [0];
                $overlayType = LanguageAspect::OVERLAYS_OFF;
        }

        return GeneralUtility::makeInstance(LanguageAspect::class, $languageId, $languageId, $overlayType, $fallbackOrder);
    }
}
