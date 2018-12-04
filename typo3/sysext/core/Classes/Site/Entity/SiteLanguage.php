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
    protected $configuration = [];

    /**
     * SiteLanguage constructor.
     *
     * @param int $languageId
     * @param string $locale
     * @param UriInterface $base
     * @param array $configuration
     */
    public function __construct(int $languageId, string $locale, UriInterface $base, array $configuration)
    {
        $this->languageId = $languageId;
        $this->locale = $locale;
        $this->base = $base;
        $this->configuration = $configuration;

        if (!empty($configuration['title'])) {
            $this->title = $configuration['title'];
        }
        if (!empty($configuration['navigationTitle'])) {
            $this->navigationTitle = $configuration['navigationTitle'];
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
            $this->fallbackLanguageIds = is_array($configuration['fallbacks']) ? $configuration['fallbacks'] : explode(',', $configuration['fallbacks']);
        }
        if (isset($configuration['enabled'])) {
            $this->enabled = (bool)$configuration['enabled'];
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
        return array_merge($this->configuration, [
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
        ]);
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
        return $this->navigationTitle ?: $this->title;
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
