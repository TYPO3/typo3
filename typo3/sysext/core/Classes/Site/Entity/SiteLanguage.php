<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Site\Entity;

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

use Psr\Http\Message\UriInterface;

/**
 * Entity representing a site_language configuration of a site object.
 */
class SiteLanguage
{
    /**
     * The language mapped to the sys_language DB entry.
     *
     * @var int
     */
    protected $languageId;

    /**
     * Locale, like 'de_CH' or 'en_GB'
     *
     * @var string
     */
    protected $locale;

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
     * The flag key (like "gb" or "fr") used to be used in TYPO3's Backend.
     * @var string
     */
    protected $flagIdentifier = '';

    /**
     * The iso code for this language (two letter) ISO-639-1
     * @var string
     */
    protected $twoLetterIsoCode = 'en';

    /**
     * Language tag for this language defined by RFC 1766 / 3066 for "lang"
     * and "hreflang" attributes
     *
     * @var string
     */
    protected $hreflang = 'en-US';

    /**
     * The direction for this language
     * @var string
     */
    protected $direction = '';

    /**
     * Prefix for TYPO3's language files
     * "default" for english, otherwise one of TYPO3's internal language keys.
     * Previously configured via TypoScript config.language = fr
     *
     * @var string
     */
    protected $typo3Language = 'default';

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
    protected $attributes = [];

    /**
     * SiteLanguage constructor.
     *
     * @param int $languageId
     * @param string $locale
     * @param UriInterface $base
     * @param array $attributes
     */
    public function __construct(int $languageId, string $locale, UriInterface $base, array $attributes)
    {
        $this->languageId = $languageId;
        $this->locale = $locale;
        $this->base = $base;
        $this->attributes = $attributes;
        if (!empty($attributes['title'])) {
            $this->title = $attributes['title'];
        }
        if (!empty($attributes['navigationTitle'])) {
            $this->navigationTitle = $attributes['navigationTitle'];
        }
        if (!empty($attributes['flag'])) {
            $this->flagIdentifier = $attributes['flag'];
        }
        if (!empty($attributes['typo3Language'])) {
            $this->typo3Language = $attributes['typo3Language'];
        }
        if (!empty($attributes['iso-639-1'])) {
            $this->twoLetterIsoCode = $attributes['iso-639-1'];
        }
        if (!empty($attributes['hreflang'])) {
            $this->hreflang = $attributes['hreflang'];
        }
        if (!empty($attributes['direction'])) {
            $this->direction = $attributes['direction'];
        }
        if (!empty($attributes['fallbackType'])) {
            $this->fallbackType = $attributes['fallbackType'];
        }
        if (!empty($attributes['fallbacks'])) {
            $this->fallbackLanguageIds = is_array($attributes['fallbacks']) ? $attributes['fallbacks'] : explode(',', $attributes['fallbacks']);
        }
        if (isset($attributes['enabled'])) {
            $this->enabled = (bool)$attributes['enabled'];
        }
    }

    /**
     * Returns the SiteLanguage in an array representation for e.g. the usage
     * in TypoScript.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'languageId' => $this->getLanguageId(),
            'locale' => $this->getLocale(),
            'base' => (string)$this->getBase(),
            'title' => $this->getTitle(),
            'navigationTitle' => $this->getNavigationTitle(),
            'twoLetterIsoCode' => $this->getTwoLetterIsoCode(),
            'hreflang' => $this->getHreflang(),
            'direction' => $this->getDirection(),
            'typo3Language' => $this->getTypo3Language(),
            'flagIdentifier' => $this->getFlagIdentifier(),
            'fallbackType' => $this->getFallbackType(),
            'enabled' => $this->enabled(),
            'fallbackLanguageIds' => $this->getFallbackLanguageIds(),
        ];
    }

    /**
     * @return int
     */
    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return UriInterface
     */
    public function getBase(): UriInterface
    {
        return $this->base;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getNavigationTitle(): string
    {
        return $this->navigationTitle ?: $this->getTitle();
    }

    /**
     * @return string
     */
    public function getFlagIdentifier(): string
    {
        return $this->flagIdentifier;
    }

    /**
     * @return string
     */
    public function getTypo3Language(): string
    {
        return $this->typo3Language;
    }

    /**
     * Returns the ISO-639-1 language ISO code
     *
     * @return string
     */
    public function getTwoLetterIsoCode(): string
    {
        return $this->twoLetterIsoCode;
    }

    /**
     * Returns the RFC 1766 / 3066 language tag
     *
     * @return string
     */
    public function getHreflang(): string
    {
        return $this->hreflang;
    }

    /**
     * Returns the language direction
     *
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * Returns true if the language is available in frontend usage
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Helper so fluid can work with this as well.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getFallbackType(): string
    {
        return $this->fallbackType;
    }

    /**
     * @return array
     */
    public function getFallbackLanguageIds(): array
    {
        return $this->fallbackLanguageIds;
    }
}
