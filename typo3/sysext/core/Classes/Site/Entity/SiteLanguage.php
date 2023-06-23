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

namespace TYPO3\CMS\Core\Site\Entity;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Localization\Locale;

/**
 * Entity representing a site_language configuration of a site object.
 */
class SiteLanguage
{
    /**
     * The language id.
     *
     * @var int
     */
    protected $languageId;

    /**
     * Locale, like 'de-CH' or 'en-GB'
     */
    protected Locale $locale;

    /**
     * The Base URL for this language
     *
     * @var UriInterface
     */
    protected $base;

    /**
     * Label to be used within TYPO3 to identify the language
     * @var string
     */
    protected $title = 'Default';

    /**
     * Label to be used within language menus
     * @var string
     */
    protected $navigationTitle = '';

    /**
     * Localized title of the site to be used in title tag.
     * @var string
     */
    protected $websiteTitle = '';

    /**
     * The flag key (like "gb" or "fr") used to be used in TYPO3's Backend.
     * @var string
     */
    protected $flagIdentifier = '';

    /**
     * The iso code for this language (two letter) ISO-639-1
     * @deprecated in favor of $this->locale->getLanguageCode()
     * @var string
     */
    protected $twoLetterIsoCode = '';

    /**
     * Language tag for this language defined by RFC 1766 / 3066 for "hreflang" attribute
     *
     * @var string
     */
    protected $hreflang = '';

    /**
     * The direction for this language
     * @deprecated in favor of $this->locale->isRightToLeftLanguageDirection()
     * @var string
     */
    protected $direction = '';

    /**
     * Prefix for TYPO3's language files. If empty, this
     * is fetched from $locale
     *
     * @var string
     */
    protected $typo3Language = '';

    /**
     * @var string
     */
    protected $fallbackType = 'strict';

    /**
     * @var array
     */
    protected $fallbackLanguageIds = [];

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * Additional parameters configured for this site language
     * @var array
     */
    protected $configuration = [];

    /**
     * SiteLanguage constructor.
     */
    public function __construct(int $languageId, string $locale, UriInterface $base, array $configuration)
    {
        $this->languageId = $languageId;
        $this->locale = new Locale($locale);
        $this->base = $base;
        $this->configuration = $configuration;

        if (!empty($configuration['title'])) {
            $this->title = $configuration['title'];
        }
        if (!empty($configuration['navigationTitle'])) {
            $this->navigationTitle = $configuration['navigationTitle'];
        }
        if (!empty($configuration['websiteTitle'])) {
            $this->websiteTitle = $configuration['websiteTitle'];
        }
        if (!empty($configuration['flag'])) {
            $this->flagIdentifier = $configuration['flag'];
        }
        if (!empty($configuration['typo3Language'])) {
            $this->typo3Language = $configuration['typo3Language'];
        }
        if (!empty($configuration['iso-639-1'])) {
            $this->twoLetterIsoCode = $configuration['iso-639-1'];
        }
        if (!empty($configuration['hreflang'])) {
            $this->hreflang = $configuration['hreflang'];
        }
        if (!empty($configuration['direction'])) {
            $this->direction = $configuration['direction'];
        }
        if (!empty($configuration['fallbackType'])) {
            $this->fallbackType = $configuration['fallbackType'];
        }
        if (isset($configuration['fallbacks'])) {
            $fallbackLanguageIds = $configuration['fallbacks'];

            // It is important to distinct between "0" and "" so, empty() should not be used here
            if (is_string($fallbackLanguageIds)) {
                if ($fallbackLanguageIds !== '') {
                    $fallbackLanguageIds = explode(',', $fallbackLanguageIds);
                } else {
                    $fallbackLanguageIds = [];
                }
            } elseif (is_scalar($fallbackLanguageIds)) {
                $fallbackLanguageIds = [$fallbackLanguageIds];
            }
            $this->fallbackLanguageIds = array_map('intval', $fallbackLanguageIds);
        }
        if (isset($configuration['enabled'])) {
            $this->enabled = (bool)$configuration['enabled'];
        }
    }

    /**
     * Returns the SiteLanguage in an array representation for e.g. the usage
     * in TypoScript.
     */
    public function toArray(): array
    {
        return array_merge($this->configuration, [
            'languageId' => $this->getLanguageId(),
            // kept for backwards-compat for the time being, might change to BGP-47 format
            'locale' => $this->getLocale()->posixFormatted(),
            'base' => (string)$this->getBase(),
            'title' => $this->getTitle(),
            'websiteTitle' => $this->getWebsiteTitle(),
            'navigationTitle' => $this->getNavigationTitle(),
            // @deprecated will be removed in TYPO3 v13.0
            'twoLetterIsoCode' => $this->twoLetterIsoCode ?: $this->locale->getLanguageCode(),
            'hreflang' => $this->hreflang ?: $this->locale->getName(),
            'direction' => $this->direction ?: ($this->locale->isRightToLeftLanguageDirection() ? 'rtl' : ''),
            'typo3Language' => $this->getTypo3Language(),
            'flagIdentifier' => $this->getFlagIdentifier(),
            'fallbackType' => $this->getFallbackType(),
            'enabled' => $this->enabled(),
            'fallbackLanguageIds' => $this->getFallbackLanguageIds(),
        ]);
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getBase(): UriInterface
    {
        return $this->base;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getNavigationTitle(): string
    {
        return $this->navigationTitle ?: $this->title;
    }

    public function getWebsiteTitle(): string
    {
        return $this->websiteTitle;
    }

    public function getFlagIdentifier(): string
    {
        return $this->flagIdentifier;
    }

    /**
     * Returns the XLF label language key, returns "default" when it is "en".
     * "default" is currently still needed for TypoScript label overloading.
     * For locales like "en-US", this method returns "en_US" which can then be used
     * for XLF file prefixes properly.
     */
    public function getTypo3Language(): string
    {
        if ($this->typo3Language !== '') {
            return $this->typo3Language;
        }
        // locale is just set to "C" or "en", this should then be mapped to "default"
        if ($this->locale->getLanguageCode() === 'en' && !$this->locale->getCountryCode()) {
            return 'default';
        }
        $typo3Language = $this->locale->getLanguageCode();
        if ($this->locale->getCountryCode()) {
            $typo3Language .= '_' . $this->locale->getCountryCode();
        }
        return $typo3Language;
    }

    /**
     * @internal
     */
    public function hasCustomTypo3Language(): bool
    {
        return $this->typo3Language !== '';
    }

    /**
     * Returns the ISO-639-1 language ISO code
     * @deprecated not needed anymore, use $this->getLocale()->getLanguageCode() instead.
     */
    public function getTwoLetterIsoCode(): string
    {
        trigger_error('SiteLanguage->getTwoLetterIsoCode() will be removed in TYPO3 v13.0. Use SiteLanguage->getLocale()->getLanguageCode() instead.', E_USER_DEPRECATED);
        return $this->twoLetterIsoCode ?: $this->locale->getLanguageCode();
    }

    /**
     * Returns the RFC 1766 / 3066 language tag for hreflang tags
     */
    public function getHreflang(bool $fetchCustomSetting = false): string
    {
        // Ensure to check if a custom attribute is set
        if ($fetchCustomSetting) {
            return $this->hreflang;
        }
        return $this->hreflang ?: $this->locale->getName();
    }

    /**
     * Returns the language direction
     * @deprecated in favor of $this->locale->isRightToLeftLanguageDirection()
     */
    public function getDirection(): string
    {
        trigger_error('SiteLanguage->getDirection() will be removed in TYPO3 v13.0. Use SiteLanguage->getLocale()->isRightToLeftLanguageDirection() instead.', E_USER_DEPRECATED);
        return $this->direction;
    }

    /**
     * Returns true if the language is available in frontend usage
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Helper so fluid can work with this as well.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getFallbackType(): string
    {
        return $this->fallbackType;
    }

    public function getFallbackLanguageIds(): array
    {
        return $this->fallbackLanguageIds;
    }
}
