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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Persistence\Generic\EntityIdentity;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Pins the string form of identity map keys: "uid[_localizedUid]@contentId-overlayType-fallbackChain".
 */
final class EntityIdentityTest extends UnitTestCase
{
    #[Test]
    public function defaultLanguageRowHasNoFallbackChain(): void
    {
        // Given a row of the default language
        $row = ['uid' => 2];
        $languageAspect = new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON_WITH_FLOATING);

        // When the identifier is built
        $identifier = (string)EntityIdentity::fromRow($row, $languageAspect);

        // Then the key consists of the uid and the content configuration
        self::assertSame('2@0-includeFloating-', $identifier);
    }

    #[Test]
    public function translatedRowContainsLocalizedUidAndFallbackChain(): void
    {
        // Given an overlaid row in a translated content language
        $row = ['uid' => 2, '_LOCALIZED_UID' => 11];
        $languageAspect = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED, [0]);

        // When the identifier is built
        $identifier = (string)EntityIdentity::fromRow($row, $languageAspect);

        // Then the localized uid is appended to the uid
        self::assertSame('2_11@1-mixed-0', $identifier);
    }

    #[Test]
    public function allLanguagesContentIdIsKeptNegative(): void
    {
        // Given a language aspect with content id -1
        $languageAspect = new LanguageAspect(-1, -1, LanguageAspect::OVERLAYS_OFF);

        // When the identifier is built
        $identifier = (string)EntityIdentity::fromRow(['uid' => 4], $languageAspect);

        // Then the content id is part of the key as is
        self::assertSame('4@-1-off-', $identifier);
    }

    #[Test]
    public function fallbackChainEntriesAreJoinedByComma(): void
    {
        // Given a fallback chain with a numeric and a non-numeric entry
        $languageAspect = new LanguageAspect(3, 3, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [2, 'pageNotFound']);

        // When the identifier is built
        $identifier = (string)EntityIdentity::fromRow(['uid' => 2, '_LOCALIZED_UID' => 22], $languageAspect);

        // Then all entries show up in order
        self::assertSame('2_22@3-includeFloating-2,pageNotFound', $identifier);
    }

    #[Test]
    public function languageIdDoesNotAffectIdentifier(): void
    {
        // Given a language id that differs from the content id
        $languageAspect = new LanguageAspect(5, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [1, 0]);

        // When the identifier is built
        $identifier = (string)EntityIdentity::fromRow(['uid' => 2], $languageAspect);

        // Then only the content id is used
        self::assertSame('2@1-includeFloating-1,0', $identifier);
    }

    #[Test]
    public function newObjectWithoutLocalizedUidUsesDefaultLanguageConfiguration(): void
    {
        // Given the row of a newly inserted object without a language aspect
        $row = ['uid' => 5, '_LOCALIZED_UID' => null];

        // When the identifier is built
        $identifier = (string)EntityIdentity::fromRow($row);

        // Then the default language configuration is used
        self::assertSame('5@0-includeFloating-', $identifier);
    }

    #[Test]
    public function newObjectWithLocalizedUidUsesDefaultLanguageConfiguration(): void
    {
        // Given the row of a newly inserted translation without a language aspect
        $row = ['uid' => 5, '_LOCALIZED_UID' => 7];

        // When the identifier is built
        $identifier = (string)EntityIdentity::fromRow($row);

        // Then the localized uid is appended and the default language configuration is used
        self::assertSame('5_7@0-includeFloating-', $identifier);
    }

    #[Test]
    public function baseIdentifierStringGetsContentIdentifierAppended(): void
    {
        // Given a base identifier given as string
        // When the identifier is built with and without language aspect
        $withAspect = (string)EntityIdentity::fromBaseIdentifier('2_11', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [0]));
        $withoutAspect = (string)EntityIdentity::fromBaseIdentifier('42');

        // Then the content identifier is appended
        self::assertSame('2_11@1-includeFloating-0', $withAspect);
        self::assertSame('42@0-includeFloating-', $withoutAspect);
    }

    #[Test]
    public function baseIdentifierIsExtractedFromIdentifier(): void
    {
        // Given identifiers with and without content identifier
        // When the base identifier is extracted
        // Then everything before the @ is returned
        self::assertSame('2_11', EntityIdentity::baseIdentifierOf('2_11@1-includeFloating-0'));
        self::assertSame('42', EntityIdentity::baseIdentifierOf('42'));
    }
}
