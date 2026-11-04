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

namespace TYPO3\CMS\Core\Tests\Unit\Context;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LanguageAspectTest extends UnitTestCase
{
    /**
     * @deprecated Remove in v16 together with LanguageAspect::OVERLAYS_ON
     */
    #[Test]
    #[IgnoreDeprecations]
    public function overlayTypeOnTriggersDeprecationAndIsKept(): void
    {
        $this->expectUserDeprecationMessageMatches('/LanguageAspect::OVERLAYS_ON\) is deprecated/');
        $subject = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON);
        self::assertSame(LanguageAspect::OVERLAYS_ON, $subject->getOverlayType());
        self::assertTrue($subject->doOverlays());
    }

    public static function legacyLanguageModeDataProvider(): array
    {
        return [
            'empty fallback chain' => [[], 'strict'],
            'fallback chain off' => [['off'], ''],
            'fallback chain of a site language' => [[0, 'pageNotFound'], 'content_fallback'],
        ];
    }

    /**
     * @deprecated Remove in v16 together with LanguageAspect::getLegacyLanguageMode()
     */
    #[DataProvider('legacyLanguageModeDataProvider')]
    #[Test]
    #[IgnoreDeprecations]
    public function getLegacyLanguageModeTriggersDeprecationAndIsKept(array $fallbackChain, string $expected): void
    {
        $this->expectUserDeprecationMessageMatches('/LanguageAspect::getLegacyLanguageMode\(\) .* deprecated/');
        $subject = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED, $fallbackChain);
        self::assertSame($expected, $subject->getLegacyLanguageMode());
        self::assertSame($expected, $subject->get('legacyLanguageMode'));
    }

    public static function legacyOverlayTypeDataProvider(): array
    {
        return [
            'overlays off' => [LanguageAspect::OVERLAYS_OFF, '0'],
            'overlays mixed' => [LanguageAspect::OVERLAYS_MIXED, '1'],
            'overlays on with floating' => [LanguageAspect::OVERLAYS_ON_WITH_FLOATING, 'hideNonTranslated'],
        ];
    }

    /**
     * @deprecated Remove in v16 together with LanguageAspect::getLegacyOverlayType()
     */
    #[DataProvider('legacyOverlayTypeDataProvider')]
    #[Test]
    #[IgnoreDeprecations]
    public function getLegacyOverlayTypeTriggersDeprecationAndIsKept(string $overlayType, string $expected): void
    {
        $this->expectUserDeprecationMessageMatches('/LanguageAspect::getLegacyOverlayType\(\) .* deprecated/');
        $subject = new LanguageAspect(1, 1, $overlayType);
        self::assertSame($expected, $subject->getLegacyOverlayType());
        self::assertSame($expected, $subject->get('legacyOverlayType'));
    }
}
