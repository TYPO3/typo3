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

namespace TYPO3\CMS\Extbase\Persistence\Generic;

use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Schema\LanguageMarker;

/**
 * The key of an object in the persistence session's identity map.
 *
 * It combines the base identifier ("uid", or "uid_localizedUid" for an overlaid record) with the
 * content-fetching configuration of the language aspect, because the same record fetched with a
 * different language configuration is a different object. String form:
 * "uid[_localizedUid]@contentId-overlayType-fallbackChain".
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class EntityIdentity implements \Stringable
{
    private function __construct(
        private string $baseIdentifier,
        private LanguageAspect $languageAspect,
    ) {}

    /**
     * @param array{uid: int|string, _LOCALIZED_UID?: int|string|null} $row
     * @param LanguageAspect|null $languageAspect Null for newly inserted objects, which use the default language
     */
    public static function fromRow(array $row, ?LanguageAspect $languageAspect = null): self
    {
        $baseIdentifier = (string)$row['uid'];
        if (isset($row['_LOCALIZED_UID'])) {
            $baseIdentifier .= '_' . $row['_LOCALIZED_UID'];
        }
        return self::fromBaseIdentifier($baseIdentifier, $languageAspect);
    }

    /**
     * @param LanguageAspect|null $languageAspect Null for newly inserted objects, which use the default language
     */
    public static function fromBaseIdentifier(string $baseIdentifier, ?LanguageAspect $languageAspect = null): self
    {
        return new self(
            $baseIdentifier,
            $languageAspect ?? new LanguageAspect(
                LanguageMarker::DEFAULT_LANGUAGE,
                LanguageMarker::DEFAULT_LANGUAGE,
                LanguageAspect::OVERLAYS_ON_WITH_FLOATING,
                []
            )
        );
    }

    /**
     * Extract the base identifier (before '@') from the string form of an identity.
     */
    public static function baseIdentifierOf(string $identifier): string
    {
        $pos = strpos($identifier, '@');
        if ($pos !== false) {
            return substr($identifier, 0, $pos);
        }
        return $identifier;
    }

    /**
     * Content id, overlay type and fallback chain: everything that affects which record overlay is
     * returned. The language id is excluded, it only affects menus and links.
     */
    private function getContentIdentifier(): string
    {
        return sprintf(
            '%d-%s-%s',
            $this->languageAspect->getContentId(),
            $this->languageAspect->getOverlayType(),
            implode(',', $this->languageAspect->getFallbackChain())
        );
    }

    public function __toString(): string
    {
        return $this->baseIdentifier . '@' . $this->getContentIdentifier();
    }
}
