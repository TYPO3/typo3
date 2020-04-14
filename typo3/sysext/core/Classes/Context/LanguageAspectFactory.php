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

namespace TYPO3\CMS\Core\Context;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A simple factory to create a language aspect.
 */
class LanguageAspectFactory
{
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
