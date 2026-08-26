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

namespace TYPO3\CMS\Core\Localization;

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

readonly class LanguageServiceFactory
{
    public function __construct(
        protected Locales $locales,
        protected LocalizationFactory $localizationFactory,
        protected FrontendInterface $runtimeCache
    ) {}

    /**
     * Factory method to create a language service object.
     *
     * A Locale without resolved dependencies (e.g. one of a SiteLanguage)
     * gets them resolved, so "de-AT" falls back to "de".
     * An object with resolved dependencies is kept.
     *
     * @param Locale|string $locale the locale
     */
    public function create(Locale|string $locale): LanguageService
    {
        if (is_string($locale) || !$locale->hasResolvedDependencies()) {
            $locale = $this->locales->createLocale((string)$locale);
        }
        $obj = new LanguageService($this->locales, $this->localizationFactory, $this->runtimeCache);
        $obj->init($locale);
        return $obj;
    }

    public function createFromUserPreferences(?AbstractUserAuthentication $user): LanguageService
    {
        return $this->create($this->locales->createLocaleFromUserPreferences($user));
    }

    public function createFromSiteLanguage(SiteLanguage $language): LanguageService
    {
        return $this->create($language->getLocale());
    }

    /**
     * Creates a LanguageService based on the current backend user's preferences.
     *
     * @internal don't use this in your own code, as we implicitly have a global dependency here.
     */
    public function createForBackendUser(): LanguageService
    {
        return $this->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }
}
