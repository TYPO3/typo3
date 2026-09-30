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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\ExtensionScanner\ExtensionScannerService;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ClassNameMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\MatcherFactory;
use TYPO3\CMS\Install\ExtensionScanner\Php\MatcherRegistry;
use TYPO3\CMS\Install\ExtensionScanner\Result\Indicator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtensionScannerServiceTest extends UnitTestCase
{
    #[Test]
    public function scanFileReportsAWeakAndAStrongMatchWithProvenance(): void
    {
        $result = $this->createSubject()->scanFile(__DIR__ . '/Fixtures/ScanFixture.php');

        self::assertNull($result->getParseError());
        self::assertCount(2, $result->getMatches());

        $byMatcher = [];
        foreach ($result->getMatches() as $match) {
            $byMatcher[$match->getMatcher()] = $match;
        }

        self::assertSame(Indicator::Weak, $byMatcher['MethodCallMatcher']->getIndicator());
        self::assertSame(['Deprecation-12345-Test.rst'], $byMatcher['MethodCallMatcher']->getRestFiles());
        self::assertStringContainsString('aMethodToCheck', $byMatcher['MethodCallMatcher']->getMessage());
        self::assertGreaterThan(0, $byMatcher['MethodCallMatcher']->getLine());

        self::assertSame(Indicator::Strong, $byMatcher['ClassNameMatcher']->getIndicator());
        self::assertSame(['Breaking-67890-Test.rst'], $byMatcher['ClassNameMatcher']->getRestFiles());
    }

    #[Test]
    public function scanFileCarriesTheStatisticsAndTheFilePath(): void
    {
        $file = __DIR__ . '/Fixtures/ScanFixture.php';
        $result = $this->createSubject()->scanFile($file);

        self::assertSame($file, $result->getFile());
        self::assertFalse($result->isFileIgnored());
        self::assertGreaterThan(0, $result->getEffectiveCodeLines());
        self::assertSame(0, $result->getIgnoredLines());
    }

    /**
     * Review Focus 5: a file annotated with @extensionScannerIgnoreFile is flagged as ignored
     * and every matcher suppresses its findings, because each of them tests isFileIgnored()
     * first. The install tool counts ignored files separately and shows no hits for them.
     *
     * The suppression starts when the class node is entered, so code in front of the class
     * declaration can still produce matches. This test pins the ordinary case.
     */
    #[Test]
    public function anIgnoredFileIsFlaggedAndReportsNoMatches(): void
    {
        $result = $this->createSubject()->scanFile(__DIR__ . '/Fixtures/IgnoredFileFixture.php');

        self::assertTrue($result->isFileIgnored());
        self::assertSame([], $result->getMatches());
        // @extensionScannerIgnoreFile and @extensionScannerIgnoreLine are separate mechanisms:
        // the file flag says nothing about the ignored line count.
        self::assertSame(0, $result->getIgnoredLines());
    }

    #[Test]
    public function anUnparsableFileYieldsAParseErrorInsteadOfThrowing(): void
    {
        $result = $this->createSubject()->scanFile(__DIR__ . '/Fixtures/BrokenSyntaxFixture.txt');

        self::assertNotNull($result->getParseError());
        self::assertSame([], $result->getMatches());
    }

    #[Test]
    public function scanFileRejectsAPathThatIsNoFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1790540102);

        $this->createSubject()->scanFile(__DIR__ . '/Fixtures/DoesNotExist.php');
    }

    #[Test]
    public function findScannableFilesReturnsSortedAbsolutePhpFilesOnly(): void
    {
        $files = $this->createSubject()->findScannableFiles(__DIR__ . '/Fixtures');

        foreach ($files as $file) {
            self::assertStringEndsWith('.php', $file);
            self::assertTrue(is_file($file));
            self::assertStringStartsWith('/', $file);
        }
        self::assertContains(__DIR__ . '/Fixtures/ScanFixture.php', $files);
        self::assertNotContains(__DIR__ . '/Fixtures/BrokenSyntaxFixture.txt', $files);

        $sorted = $files;
        sort($sorted);
        self::assertSame($sorted, $files);
    }

    /**
     * Review Focus 2: a missing directory is a clear RuntimeException, not a Finder exception
     * leaking out of the service.
     */
    #[Test]
    public function findScannableFilesRejectsAPathThatIsNoDirectory(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1790540101);

        $this->createSubject()->findScannableFiles(__DIR__ . '/Fixtures/NoSuchDirectory');
    }

    /**
     * The rule set is 23 configuration files that are required from disk. Reading them once per
     * scanned file is pure loss for a run over a whole extension. The matcher instances however
     * must stay fresh per file, because AbstractCoreMatcher remembers a file level ignore flag:
     * a reused instance would silence every file after the first ignored one.
     */
    #[Test]
    public function theRuleSetIsResolvedOnceWhileMatchersStayFreshPerFile(): void
    {
        $registry = $this->createMock(MatcherRegistry::class);
        $registry->expects($this->once())->method('getMatcherConfigurations')->willReturn([
            [
                'class' => MethodCallMatcher::class,
                'configurationArray' => [
                    'TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Fixtures\Whatever->aMethodToCheck' => [
                        'numberOfMandatoryArguments' => 0,
                        'maximumNumberOfArguments' => 0,
                        'restFiles' => ['Deprecation-12345-Test.rst'],
                    ],
                ],
            ],
        ]);
        $subject = new ExtensionScannerService($registry, new MatcherFactory());

        // An ignored file first: a leaking matcher instance would suppress everything after it.
        $ignored = $subject->scanFile(__DIR__ . '/Fixtures/IgnoredFileFixture.php');
        $first = $subject->scanFile(__DIR__ . '/Fixtures/ScanFixture.php');
        $second = $subject->scanFile(__DIR__ . '/Fixtures/ScanFixture.php');

        self::assertSame([], $ignored->getMatches());
        self::assertCount(1, $first->getMatches());
        self::assertCount(1, $second->getMatches());
        self::assertSame($first->getMatches()[0]->getLine(), $second->getMatches()[0]->getLine());
    }

    /**
     * A two rule configuration, handed in as configurationArray so the test does not depend
     * on the core changelog of the day: one weak rule on a bare method name, one strong rule
     * on a fully qualified class name.
     */
    private function createSubject(): ExtensionScannerService
    {
        $registry = self::createStub(MatcherRegistry::class);
        $registry->method('getMatcherConfigurations')->willReturn([
            [
                'class' => MethodCallMatcher::class,
                'configurationArray' => [
                    'TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Fixtures\Whatever->aMethodToCheck' => [
                        'numberOfMandatoryArguments' => 0,
                        'maximumNumberOfArguments' => 0,
                        'restFiles' => ['Deprecation-12345-Test.rst'],
                    ],
                ],
            ],
            [
                'class' => ClassNameMatcher::class,
                'configurationArray' => [
                    'TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Fixtures\RemovedFixtureClass' => [
                        'restFiles' => ['Breaking-67890-Test.rst'],
                    ],
                ],
            ],
        ]);

        return new ExtensionScannerService($registry, new MatcherFactory());
    }
}
