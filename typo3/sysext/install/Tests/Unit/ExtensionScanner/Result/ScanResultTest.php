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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Result;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\ExtensionScanner\Result\Indicator;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanMatch;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanResult;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ScanResultTest extends UnitTestCase
{
    #[Test]
    public function indicatorKeepsTheBackedValuesTheJavaScriptRelies(): void
    {
        self::assertSame('strong', Indicator::Strong->value);
        self::assertSame('weak', Indicator::Weak->value);
        self::assertNull(Indicator::tryFrom('silenced'));
    }

    #[Test]
    public function resultExposesMatchesAndStatistics(): void
    {
        $match = new ScanMatch(
            42,
            Indicator::Weak,
            'MethodCallMatcher',
            'Call to method "aMethodToCheck()"',
            ['Deprecation-12345-Test.rst']
        );
        $subject = new ScanResult('/tmp/a.php', [$match], null, false, 7, 2);

        self::assertSame('/tmp/a.php', $subject->getFile());
        self::assertSame([$match], $subject->getMatches());
        self::assertNull($subject->getParseError());
        self::assertFalse($subject->isFileIgnored());
        self::assertSame(7, $subject->getEffectiveCodeLines());
        self::assertSame(2, $subject->getIgnoredLines());
        self::assertSame(42, $match->getLine());
        self::assertSame(Indicator::Weak, $match->getIndicator());
        self::assertSame('MethodCallMatcher', $match->getMatcher());
        self::assertSame(['Deprecation-12345-Test.rst'], $match->getRestFiles());
    }

    #[Test]
    public function aParseErrorForbidsMatches(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1790540103);

        new ScanResult(
            '/tmp/a.php',
            [new ScanMatch(1, Indicator::Strong, 'ClassNameMatcher', 'msg', [])],
            'Syntax error, unexpected T_STRING on line 3',
            false,
            0,
            0
        );
    }
}
