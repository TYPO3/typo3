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

/**
 * @internal
 */
class LanguageServiceFactory
{
    /**
     * @var Locales
     */
    protected $locales;

    /**
     * @var LocalizationFactory
     */
    protected $localizationFactory;

    public function __construct(Locales $locales, LocalizationFactory $localizationFactory)
    {
        $this->locales = $locales;
        $this->localizationFactory = $localizationFactory;
    }

    /**
     * Factory method to create a language service object.
     *
     * @param string $locale the locale (= the TYPO3-internal locale given)
     * @return LanguageService
     */
    public function create(string $locale): LanguageService
    {
        $obj = new LanguageService($this->locales, $this->localizationFactory);
        $obj->init($locale);
        return $obj;
    }

    public function createFromUserPreferences(?AbstractUserAuthentication $user): LanguageService
    {
        if ($user && ($user->user['lang'] ?? false)) {
            return $this->create($user->user['lang']);
        }
        return $this->create('default');
    }
}
