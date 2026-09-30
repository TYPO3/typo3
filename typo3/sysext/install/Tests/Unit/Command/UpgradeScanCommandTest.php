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

namespace TYPO3\CMS\Install\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Install\Command\UpgradeScanCommand;
use TYPO3\CMS\Install\ExtensionScanner\ExtensionScannerService;
use TYPO3\CMS\Install\ExtensionScanner\Result\Indicator;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanMatch;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanResult;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UpgradeScanCommandTest extends UnitTestCase
{
    #[Test]
    public function tableListsFileLineIndicatorAndMessage(): void
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn(['/tmp/my_extension/Classes/Foo.php']);
        $scannerStub->method('scanFile')->willReturn(new ScanResult(
            '/tmp/my_extension/Classes/Foo.php',
            [new ScanMatch(12, Indicator::Strong, 'ClassNameMatcher', 'Usage of removed class', ['Breaking-1-Test.rst'])],
            null,
            false,
            10,
            0
        ));

        $tester = new CommandTester(new UpgradeScanCommand('upgrade:scan', $this->createPackageManagerStub(), $scannerStub, new Typo3Version()));
        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        // A strong finding fails the run with the default --fail-on=strong; this test is about
        // what the table contains, the exit code semantics are covered separately below.
        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Classes/Foo.php', $display);
        self::assertStringContainsString('12', $display);
        self::assertStringContainsString('strong', $display);
        self::assertStringContainsString('Usage of removed class', $display);
    }

    /**
     * Review Focus 1: an unknown extension key is a clear message and INVALID, not a
     * PackageManager exception travelling up.
     */
    #[Test]
    public function anUnknownExtensionKeyIsRejected(): void
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);

        $tester = new CommandTester(new UpgradeScanCommand('upgrade:scan', $this->createPackageManagerStub(), $scannerStub, new Typo3Version()));
        $exitCode = $tester->execute(['extensionKey' => ['no_such_extension']]);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('no_such_extension', $tester->getDisplay());
    }

    #[Test]
    public function anInstallationWithoutOwnExtensionsSaysSoAndSucceeds(): void
    {
        $metaDataStub = self::createStub(MetaData::class);
        $metaDataStub->method('isExtensionType')->willReturn(true);
        // Everything installed is part of the Core, so there is nothing to scan.
        $metaDataStub->method('isFrameworkType')->willReturn(true);
        $packageStub = self::createStub(PackageInterface::class);
        $packageStub->method('getPackageKey')->willReturn('core');
        $packageStub->method('getPackageMetaData')->willReturn($metaDataStub);
        $packageManagerStub = self::createStub(PackageManager::class);
        $packageManagerStub->method('getAvailablePackages')->willReturn([$packageStub]);

        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $packageManagerStub,
            self::createStub(ExtensionScannerService::class),
            new Typo3Version()
        ));
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('no extensions outside the Core', $tester->getDisplay());
    }

    /**
     * Review Focus 3: an extension without a single scannable file reports that and succeeds.
     */
    #[Test]
    public function anExtensionWithoutScannableFilesSucceedsWithAMessage(): void
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn([]);

        $tester = new CommandTester(new UpgradeScanCommand('upgrade:scan', $this->createPackageManagerStub(), $scannerStub, new Typo3Version()));
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('No scannable files', $tester->getDisplay());
    }

    #[Test]
    public function jsonCarriesTheCoreVersionSummaryAndResults(): void
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn(['/tmp/my_extension/Classes/Foo.php']);
        $scannerStub->method('scanFile')->willReturn(new ScanResult(
            '/tmp/my_extension/Classes/Foo.php',
            [
                new ScanMatch(12, Indicator::Strong, 'ClassNameMatcher', 'Usage of removed class', ['Breaking-1-Test.rst']),
                new ScanMatch(14, Indicator::Weak, 'MethodCallMatcher', 'Call to method "foo()"', []),
            ],
            null,
            false,
            10,
            0
        ));

        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $scannerStub,
            new Typo3Version()
        ));
        $tester->execute(['--json' => true]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(new Typo3Version()->getVersion(), $payload['typo3Version']);
        self::assertSame(1, $payload['summary']['filesScanned']);
        self::assertSame(2, $payload['summary']['hits']['total']);
        self::assertSame(1, $payload['summary']['hits']['byIndicator']['strong']);
        self::assertSame(1, $payload['summary']['hits']['byIndicator']['weak']);
        self::assertSame(0, $payload['summary']['filesWithParseErrors']);
        self::assertNotSame('', $payload['summary']['note']);
        self::assertCount(1, $payload['results']);
        self::assertSame('strong', $payload['results'][0]['hits'][0]['indicator']);
        self::assertSame('ClassNameMatcher', $payload['results'][0]['hits'][0]['matcher']);
    }

    #[Test]
    public function anEmptyScanStillEmitsValidJson(): void
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn([]);

        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $scannerStub,
            new Typo3Version()
        ));
        $exitCode = $tester->execute(['--json' => true]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertSame(0, $payload['summary']['filesScanned']);
        self::assertSame(0, $payload['summary']['hits']['total']);
        self::assertSame([], $payload['results']);
    }

    #[Test]
    public function weakFindingsAloneDoNotFailTheRunByDefault(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithOneWeakHit(),
            new Typo3Version()
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    #[Test]
    public function failOnAnyAlsoFailsOnWeakFindings(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithOneWeakHit(),
            new Typo3Version()
        ));

        self::assertSame(Command::FAILURE, $tester->execute(['--fail-on' => 'any']));
    }

    #[Test]
    public function strongFindingsFailTheRunByDefault(): void
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn(['/tmp/my_extension/Classes/Foo.php']);
        $scannerStub->method('scanFile')->willReturn(new ScanResult(
            '/tmp/my_extension/Classes/Foo.php',
            [new ScanMatch(12, Indicator::Strong, 'ClassNameMatcher', 'Usage of removed class', [])],
            null,
            false,
            10,
            0
        ));

        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $scannerStub,
            new Typo3Version()
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertSame(Command::SUCCESS, $tester->execute(['--fail-on' => 'none']));
    }

    #[Test]
    public function anUnknownFailOnValueIsRejected(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            self::createStub(ExtensionScannerService::class),
            new Typo3Version()
        ));

        self::assertSame(Command::INVALID, $tester->execute(['--fail-on' => 'maybe']));
    }

    /**
     * A scanner that could not read a file has not cleared it. Reporting success would tell a
     * CI pipeline the extension is fine when nothing was actually looked at.
     */
    #[Test]
    public function aFileThatCouldNotBeParsedFailsTheRunByDefault(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAnUnparsableFile(),
            new Typo3Version()
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('could not be parsed', $tester->getDisplay());
        self::assertStringContainsString('Broken.php', $tester->getDisplay());
    }

    #[Test]
    public function aFileThatCouldNotBeParsedAlsoFailsWithFailOnAny(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAnUnparsableFile(),
            new Typo3Version()
        ));

        self::assertSame(Command::FAILURE, $tester->execute(['--fail-on' => 'any']));
    }

    #[Test]
    public function failOnNoneKeepsAnUnparsableFileGreen(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAnUnparsableFile(),
            new Typo3Version()
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--fail-on' => 'none']));
    }

    #[Test]
    public function jsonReportsTheParseErrorAndCountsIt(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAnUnparsableFile(),
            new Typo3Version()
        ));
        $tester->execute(['--json' => true]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $payload['summary']['filesWithParseErrors']);
        self::assertSame(0, $payload['summary']['hits']['total']);
        self::assertCount(1, $payload['results']);
        self::assertSame(
            'Syntax error, unexpected T_STRING on line 3',
            $payload['results'][0]['parseError']
        );
        self::assertSame([], $payload['results'][0]['hits']);
    }

    /**
     * Errors belong on standard error. A consumer piping standard output into a parser must
     * not receive a human readable block in the data stream.
     */
    #[Test]
    public function errorsGoToStandardErrorAndLeaveStandardOutputEmpty(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            self::createStub(ExtensionScannerService::class),
            new Typo3Version()
        ));
        $exitCode = $tester->execute(['--json' => true, '--fail-on' => 'maybe'], ['capture_stderr_separately' => true]);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('maybe', $tester->getErrorOutput());
        self::assertSame('', $tester->getDisplay());
    }

    /**
     * The JSON payload must reach standard output untouched. Console style tags that happen to
     * appear in a message would otherwise be eaten by the output formatter.
     */
    #[Test]
    public function jsonPayloadIsNotRewrittenByTheOutputFormatter(): void
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn(['/tmp/my_extension/Classes/Foo.php']);
        $scannerStub->method('scanFile')->willReturn(new ScanResult(
            '/tmp/my_extension/Classes/Foo.php',
            [new ScanMatch(12, Indicator::Strong, 'ClassNameMatcher', 'Usage of <info>', [])],
            null,
            false,
            10,
            0
        ));

        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $scannerStub,
            new Typo3Version()
        ));
        $tester->execute(['--json' => true]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Usage of <info>', $payload['results'][0]['hits'][0]['message']);
    }

    #[Test]
    public function jsonCarriesTheExtensionKeyOfEachFile(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAStrongAndAWeakHit(),
            new Typo3Version()
        ));
        $tester->execute(['--json' => true]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('my_extension', $payload['results'][0]['extensionKey']);
    }

    public static function indicatorDataProvider(): \Generator
    {
        yield 'strong' => ['strong', 'Usage of removed class', 'Call to method "foo()"'];
        yield 'weak' => ['weak', 'Call to method "foo()"', 'Usage of removed class'];
    }

    #[Test]
    #[DataProvider('indicatorDataProvider')]
    public function indicatorListsOnlyMatchingFindingsInTheTableButCountsAll(string $indicator, string $listed, string $hidden): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAStrongAndAWeakHit(),
            new Typo3Version()
        ));
        $tester->execute(['--indicator' => $indicator, '--fail-on' => 'none']);
        $display = $tester->getDisplay();

        self::assertStringContainsString($listed, $display);
        self::assertStringNotContainsString($hidden, $display);
        self::assertStringContainsString('2 findings in 1 scanned files: 1 strong, 1 weak.', $display);
    }

    #[Test]
    #[DataProvider('indicatorDataProvider')]
    public function indicatorListsOnlyMatchingFindingsInJsonButCountsAll(string $indicator, string $listed, string $hidden): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAStrongAndAWeakHit(),
            new Typo3Version()
        ));
        $tester->execute(['--json' => true, '--indicator' => $indicator]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($indicator, $payload['indicator']);
        self::assertSame(2, $payload['summary']['hits']['total']);
        self::assertSame(1, $payload['summary']['hits']['byIndicator']['strong']);
        self::assertSame(1, $payload['summary']['hits']['byIndicator']['weak']);
        self::assertCount(1, $payload['results'][0]['hits']);
        self::assertSame($indicator, $payload['results'][0]['hits'][0]['indicator']);
        self::assertSame($listed, $payload['results'][0]['hits'][0]['message']);
        self::assertStringNotContainsString($hidden, $tester->getDisplay());
    }

    #[Test]
    public function withoutIndicatorAllFindingsAreListed(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAStrongAndAWeakHit(),
            new Typo3Version()
        ));
        $tester->execute(['--json' => true]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertNull($payload['indicator']);
        self::assertCount(2, $payload['results'][0]['hits']);
    }

    #[Test]
    public function jsonOmitsFilesWhoseFindingsHaveAnotherIndicator(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithOneWeakHit(),
            new Typo3Version()
        ));
        $tester->execute(['--json' => true, '--indicator' => 'strong']);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $payload['summary']['hits']['total']);
        self::assertSame([], $payload['results']);
    }

    #[Test]
    public function indicatorDoesNotHideParseErrors(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAnUnparsableFile(),
            new Typo3Version()
        ));
        $exitCode = $tester->execute(['--indicator' => 'weak']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Broken.php', $tester->getDisplay());
    }

    /**
     * The exit code ignores --indicator. When the findings that fail the run are not listed,
     * the table must say so instead of leaving an unexplained non-zero exit code.
     */
    #[Test]
    public function indicatorHidingTheFindingsThatFailTheRunSaysSo(): void
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn(['/tmp/my_extension/Classes/Foo.php']);
        $scannerStub->method('scanFile')->willReturn(new ScanResult(
            '/tmp/my_extension/Classes/Foo.php',
            [new ScanMatch(12, Indicator::Strong, 'ClassNameMatcher', 'Usage of removed class', [])],
            null,
            false,
            10,
            0
        ));

        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $scannerStub,
            new Typo3Version()
        ));
        $exitCode = $tester->execute(['--indicator' => 'weak']);
        $display = $tester->getDisplay();

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringNotContainsString('Usage of removed class', $display);
        self::assertStringContainsString('No findings match --indicator=weak', $display);
        self::assertStringContainsString('hidden by --indicator=weak make this run fail', $display);
        self::assertStringNotContainsString('no findings.', $display);
    }

    #[Test]
    public function indicatorHidingFindingsThatDoNotFailTheRunSucceedsWithoutWarning(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithOneWeakHit(),
            new Typo3Version()
        ));
        $exitCode = $tester->execute(['--indicator' => 'strong']);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('No findings match --indicator=strong', $display);
        self::assertStringNotContainsString('make this run fail', $display);
    }

    #[Test]
    public function indicatorHidingSomeOfTheFindingsThatFailTheRunSaysSo(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAStrongAndAWeakHit(),
            new Typo3Version()
        ));
        $exitCode = $tester->execute(['--indicator' => 'weak']);
        $display = $tester->getDisplay();

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Call to method "foo()"', $display);
        self::assertStringContainsString('hidden by --indicator=weak make this run fail', $display);
    }

    #[Test]
    public function indicatorShowingTheFindingsThatFailTheRunDoesNotWarn(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithAStrongAndAWeakHit(),
            new Typo3Version()
        ));
        $exitCode = $tester->execute(['--indicator' => 'strong']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringNotContainsString('make this run fail', $tester->getDisplay());
    }

    #[Test]
    public function anUnknownIndicatorIsRejected(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            self::createStub(ExtensionScannerService::class),
            new Typo3Version()
        ));

        self::assertSame(Command::INVALID, $tester->execute(['--indicator' => 'medium']));
        self::assertStringContainsString('--indicator', $tester->getDisplay());
    }

    #[Test]
    public function verboseTableListsTheChangelogFilesOfAFinding(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithTwoChangelogFiles(),
            new Typo3Version()
        ));
        $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $lines = explode("\n", $tester->getDisplay());

        $messageLine = $this->findLineContaining($lines, 'Usage of removed class');
        $firstRstLine = $this->findLineContaining($lines, 'Breaking-1-Test.rst');
        $secondRstLine = $this->findLineContaining($lines, 'Deprecation-2-Test.rst');
        self::assertSame($messageLine + 1, $firstRstLine);
        self::assertSame($messageLine + 2, $secondRstLine);
    }

    #[Test]
    public function tableWithoutVerboseOmitsTheChangelogFiles(): void
    {
        $tester = new CommandTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            $this->createScannerStubWithTwoChangelogFiles(),
            new Typo3Version()
        ));
        $tester->execute([]);

        self::assertStringContainsString('Usage of removed class', $tester->getDisplay());
        self::assertStringNotContainsString('.rst', $tester->getDisplay());
    }

    #[Test]
    public function extensionKeysAreCompletedWithoutCoreAndAlreadyGivenOnes(): void
    {
        $packages = [];
        foreach (['news' => false, 'my_extension' => false, 'core' => true] as $key => $isFramework) {
            $metaDataStub = self::createStub(MetaData::class);
            $metaDataStub->method('isExtensionType')->willReturn(true);
            $metaDataStub->method('isFrameworkType')->willReturn($isFramework);
            $packageStub = self::createStub(PackageInterface::class);
            $packageStub->method('getPackageKey')->willReturn($key);
            $packageStub->method('getPackageMetaData')->willReturn($metaDataStub);
            $packages[] = $packageStub;
        }
        $packageManagerStub = self::createStub(PackageManager::class);
        $packageManagerStub->method('getAvailablePackages')->willReturn($packages);

        $tester = new CommandCompletionTester(new UpgradeScanCommand(
            'upgrade:scan',
            $packageManagerStub,
            self::createStub(ExtensionScannerService::class),
            new Typo3Version()
        ));

        self::assertSame(['my_extension', 'news'], $tester->complete(['']));
        self::assertSame(['my_extension'], $tester->complete(['news', '']));
    }

    public static function optionCompletionDataProvider(): \Generator
    {
        yield 'indicator' => ['--indicator', ['strong', 'weak']];
        yield 'fail-on' => ['--fail-on', ['strong', 'any', 'none']];
    }

    #[Test]
    #[DataProvider('optionCompletionDataProvider')]
    public function optionValuesAreCompleted(string $option, array $expected): void
    {
        $tester = new CommandCompletionTester(new UpgradeScanCommand(
            'upgrade:scan',
            $this->createPackageManagerStub(),
            self::createStub(ExtensionScannerService::class),
            new Typo3Version()
        ));

        self::assertSame($expected, $tester->complete([$option, '']));
    }

    /**
     * @param list<string> $lines
     */
    private function findLineContaining(array $lines, string $needle): int
    {
        foreach ($lines as $index => $line) {
            if (str_contains($line, $needle)) {
                return $index;
            }
        }
        self::fail('No line contains "' . $needle . '".');
    }

    private function createPackageManagerStub(string $key = 'my_extension'): PackageManager
    {
        $metaDataStub = self::createStub(MetaData::class);
        $metaDataStub->method('isExtensionType')->willReturn(true);
        $metaDataStub->method('isFrameworkType')->willReturn(false);
        $packageStub = self::createStub(PackageInterface::class);
        $packageStub->method('getPackageKey')->willReturn($key);
        $packageStub->method('getPackagePath')->willReturn('/tmp/' . $key . '/');
        $packageStub->method('getPackageMetaData')->willReturn($metaDataStub);

        $packageManagerStub = self::createStub(PackageManager::class);
        $packageManagerStub->method('getAvailablePackages')->willReturn([$packageStub]);
        $packageManagerStub->method('isPackageAvailable')->willReturnCallback(
            static fn(string $requested): bool => $requested === $key
        );
        $packageManagerStub->method('getPackage')->willReturn($packageStub);

        return $packageManagerStub;
    }

    private function createScannerStubWithOneWeakHit(): ExtensionScannerService
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn(['/tmp/my_extension/Classes/Foo.php']);
        $scannerStub->method('scanFile')->willReturn(new ScanResult(
            '/tmp/my_extension/Classes/Foo.php',
            [new ScanMatch(14, Indicator::Weak, 'MethodCallMatcher', 'Call to method "foo()"', [])],
            null,
            false,
            10,
            0
        ));

        return $scannerStub;
    }

    private function createScannerStubWithAStrongAndAWeakHit(): ExtensionScannerService
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn(['/tmp/my_extension/Classes/Foo.php']);
        $scannerStub->method('scanFile')->willReturn(new ScanResult(
            '/tmp/my_extension/Classes/Foo.php',
            [
                new ScanMatch(12, Indicator::Strong, 'ClassNameMatcher', 'Usage of removed class', []),
                new ScanMatch(14, Indicator::Weak, 'MethodCallMatcher', 'Call to method "foo()"', []),
            ],
            null,
            false,
            10,
            0
        ));

        return $scannerStub;
    }

    private function createScannerStubWithTwoChangelogFiles(): ExtensionScannerService
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn(['/tmp/my_extension/Classes/Foo.php']);
        $scannerStub->method('scanFile')->willReturn(new ScanResult(
            '/tmp/my_extension/Classes/Foo.php',
            [new ScanMatch(12, Indicator::Strong, 'ClassNameMatcher', 'Usage of removed class', ['Breaking-1-Test.rst', 'Deprecation-2-Test.rst'])],
            null,
            false,
            10,
            0
        ));

        return $scannerStub;
    }

    private function createScannerStubWithAnUnparsableFile(): ExtensionScannerService
    {
        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('findScannableFiles')->willReturn(['/tmp/my_extension/Classes/Broken.php']);
        $scannerStub->method('scanFile')->willReturn(new ScanResult(
            '/tmp/my_extension/Classes/Broken.php',
            [],
            'Syntax error, unexpected T_STRING on line 3',
            false,
            0,
            0
        ));

        return $scannerStub;
    }
}
