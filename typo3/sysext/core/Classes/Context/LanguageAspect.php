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

use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

/**
 * The Aspect is usually available as "language" property, and
 * can be used to find out the "overlay"/data retrieval strategy.
 *
 *
 * "id" (languageId, int)
 * - formally known as $TSFE->sys_language_uid
 * - the requested language of the current page (frontend)
 * - used in menus and links to generate "links in language with this ID"
 *
 * "contentId" (int)
 * - formally known as $TSFE->sys_language_content
 * - the language of records to be fetched
 * - if empty, "languageId" is used.
 *
 * "fallbackChain"
 *  - when "fallback" go with
 *  - depends what "contentId" value should be set
 *  - defined in config.sys_language_mode (strict/content_fallback:4,5,stop/ignore?)
 *  - previously known as $TSFE->sys_language_mode
 *  - defines "contentId" based on "if the current page is available in this language"
 *   - "strict"
 *   - "fallback" if current page is not available, check the fallbackChain"
 *   - "fallbackAndIgnore"
 *
 * "overlayType"
 * - defines which way the records should be fetched from ($TSFE->sys_language_contentOL and config.sys_language_overlay)
 * - usually you fetch language 0 and -1, then take the "contentId" and "overlay" them
 *    - here you have two choices
 *          1. "on" if there is no overlay, do not render the default language records ("hideNonTranslated")
 *          2. "mixed" - if there is no overlay, just keep the default language, possibility to have mixed languages - config.sys_language_overlay = 1
 *          3. "off" - do not do overlay, only fetch records available in the current "contentId" (see above), and do not care about overlays or fallbacks - fallbacks could be an option here, actually that is placed on top
 *          4. "includeFloating" - on + includeRecordsWithoutDefaultTranslation
 */
class LanguageAspect implements AspectInterface
{
    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var int
     */
    protected $contentId = 0;

    /**
     * @var array
     */
    protected $fallbackChain = [];

    /**
     * @var string
     */
    protected $overlayType;

    public const OVERLAYS_OFF = 'off';  // config.sys_language_overlay = 0
    public const OVERLAYS_MIXED = 'mixed';  // config.sys_language_overlay = 1 (keep the ones that are only available in default language)
    public const OVERLAYS_ON = 'on';    // "hideNonTranslated"
    public const OVERLAYS_ON_WITH_FLOATING = 'includeFloating';    // "hideNonTranslated" + records that are only available in polish

    /**
     * Create the default language
     *
     * @param int $id
     * @param int|null $contentId
     * @param string $overlayType
     * @param array $fallbackChain
     */
    public function __construct(int $id = 0, int $contentId = null, string $overlayType = self::OVERLAYS_ON_WITH_FLOATING, array $fallbackChain = [])
    {
        $this->overlayType = $overlayType;
        $this->id = $id;
        $this->contentId = $contentId ?? $this->id;
        $this->fallbackChain = $fallbackChain;
    }

    /**
     * Used language overlay
     *
     * @return string
     */
    public function getOverlayType(): string
    {
        return $this->overlayType;
    }

    /**
     * Returns the language ID the current page was requested,
     * this is relevant when building menus or links to other pages.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Contains the language UID of the content records that should be overlaid to would be fetched.
     * This is especially useful when a page requested with language=4 should fall back to showing
     * content of language=2 (see fallbackChain)
     *
     * @return int
     */
    public function getContentId(): int
    {
        return $this->contentId;
    }

    public function getFallbackChain(): array
    {
        return $this->fallbackChain;
    }

    /**
     * Whether overlays should be done
     *
     * @return bool
     */
    public function doOverlays(): bool
    {
        return $this->contentId > 0 && $this->overlayType !== self::OVERLAYS_OFF;
    }

    /**
     * Previously known as TSFE->sys_language_mode, here for compatibility reasons
     *
     * @return string
     */
    public function getLegacyLanguageMode(): string
    {
        if ($this->fallbackChain === ['off']) {
            return '';
        }
        if (empty($this->fallbackChain)) {
            return 'strict';
        }
        if ($this->fallbackChain === [-1]) {
            return 'ignore';
        }
        return 'content_fallback';
    }

    /**
     * Previously known as TSFE->sys_language_contentOL, here for compatibility reasons
     *
     * @return string
     */
    public function getLegacyOverlayType(): string
    {
        switch ($this->overlayType) {
            case self::OVERLAYS_ON_WITH_FLOATING:
            case self::OVERLAYS_ON:
                return 'hideNonTranslated';
            case self::OVERLAYS_MIXED:
                return '1';
            case self::OVERLAYS_OFF:
            default:
                return '0';
        }
    }

    /**
     * Fetch a property.
     *
     * @param string $name
     * @return int|string|array
     * @throws AspectPropertyNotFoundException
     */
    public function get(string $name)
    {
        switch ($name) {
            case 'id':
                return $this->id;
            case 'contentId':
                return $this->contentId;
            case 'fallbackChain':
                return $this->fallbackChain;
            case 'overlayType':
                return $this->overlayType;
            case 'legacyLanguageMode':
                return $this->getLegacyLanguageMode();
            case 'legacyOverlayType':
                return $this->getLegacyOverlayType();
        }
        throw new AspectPropertyNotFoundException('Property "' . $name . '" not found in Aspect "' . __CLASS__ . '".', 1530448504);
    }
}
