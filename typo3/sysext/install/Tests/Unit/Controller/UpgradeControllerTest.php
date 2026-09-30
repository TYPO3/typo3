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

namespace TYPO3\CMS\Install\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Service\DatabaseUpgradeWizardsService;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Install\Controller\UpgradeController;
use TYPO3\CMS\Install\ExtensionScanner\ExtensionScannerService;
use TYPO3\CMS\Install\ExtensionScanner\Result\Indicator;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanMatch;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanResult;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\LoadTcaService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UpgradeControllerTest extends UnitTestCase
{
    public static function versionDataProviderWithoutException(): array
    {
        return [
            ['master'],
            ['1.0'],
            ['1.10'],
            ['2.3.4'],
            ['2.3.20'],
            ['7.6.x'],
            ['10.0'],
            ['10.10'],
            ['10.10.5'],
        ];
    }

    #[DataProvider('versionDataProviderWithoutException')]
    #[Test]
    #[DoesNotPerformAssertions]
    public function versionIsAccepted(string $version): void
    {
        $request = (new ServerRequest())->withQueryParams([
            'install' => [
                'version' => $version,
            ],
        ]);
        $subject = $this->getMockBuilder(UpgradeController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDocumentationFiles', 'initializeView'])
            ->getMock();
        $subject->method('getDocumentationFiles')->willReturn([
            'normalFiles' => [],
            'readFiles' => [],
            'notAffectedFiles' => [],
        ]);
        $viewMock = $this->getMockBuilder(ViewInterface::class)->getMock();
        $viewMock->expects($this->any())->method('assignMultiple')->willReturn($viewMock);
        $viewMock->expects($this->any())->method('render')->willReturn('');
        $subject->method('initializeView')->willReturn($viewMock);
        $subject->upgradeDocsGetChangelogForVersionAction($request);
    }
    public static function versionDataProvider(): array
    {
        return [
            ['10.10.husel'],
            ['1.2.3.4'],
            ['9.8.x.x'],
            ['a.b.c'],
            ['4.3.x.1'],
            ['../../../../../../../etc/passwd'],
            ['husel'],
        ];
    }

    #[DataProvider('versionDataProvider')]
    #[Test]
    public function versionIsNotAccepted(string $version): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1537209128);
        $request = (new ServerRequest())->withQueryParams([
            'install' => [
                'version' => $version,
            ],
        ]);
        $subject = $this->getMockBuilder(UpgradeController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDocumentationFiles', 'initializeView'])
            ->getMock();
        $subject->method('getDocumentationFiles')->willReturn([
            'normalFiles' => [],
            'readFiles' => [],
            'notAffectedFiles' => [],
        ]);
        $viewMock = $this->getMockBuilder(ViewInterface::class)->getMock();
        $viewMock->expects($this->any())->method('assignMultiple')->willReturn($viewMock);
        $viewMock->expects($this->any())->method('render')->willReturn('');
        $subject->method('initializeView')->willReturn($viewMock);
        $subject->upgradeDocsGetChangelogForVersionAction($request);
    }

    #[Test]
    public function extensionScannerScanFileActionKeepsItsJsonContract(): void
    {
        // dirname() instead of __DIR__ . '/../': GeneralUtility::isAllowedAbsPath() rejects '..'
        $fixtureDirectory = dirname(__DIR__) . '/ExtensionScanner/Fixtures';
        $fixtureFile = 'ScanFixture.php';
        // Looked up instead of hard-coded, so that edits to the fixture header do not break this test.
        $callLine = 1 + (int)array_key_first(preg_grep('/\$this->aMethodToCheck\(\);/', file($fixtureDirectory . '/' . $fixtureFile)));

        $scannerStub = self::createStub(ExtensionScannerService::class);
        $scannerStub->method('scanFile')->willReturn(new ScanResult(
            $fixtureDirectory . '/' . $fixtureFile,
            [new ScanMatch($callLine, Indicator::Weak, 'MethodCallMatcher', 'Call to method "aMethodToCheck()"', [])],
            null,
            false,
            7,
            2
        ));

        $packageStub = self::createStub(PackageInterface::class);
        $packageStub->method('getPackagePath')->willReturn($fixtureDirectory . '/');
        $packageManagerStub = self::createStub(PackageManager::class);
        $packageManagerStub->method('getPackage')->willReturn($packageStub);

        $subject = new UpgradeController(
            $packageManagerStub,
            self::createStub(LateBootService::class),
            self::createStub(DatabaseUpgradeWizardsService::class),
            self::createStub(FormProtectionFactory::class),
            self::createStub(LoadTcaService::class),
            self::createStub(Registry::class),
            $scannerStub,
        );

        $request = (new ServerRequest())->withParsedBody([
            'install' => ['extension' => 'a_fixture', 'file' => $fixtureFile],
        ]);
        $response = $subject->extensionScannerScanFileAction($request);
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(
            ['success', 'matches', 'isFileIgnored', 'effectiveCodeLines', 'ignoredLines'],
            array_keys($payload)
        );
        self::assertTrue($payload['success']);
        self::assertFalse($payload['isFileIgnored']);
        self::assertSame(7, $payload['effectiveCodeLines']);
        self::assertSame(2, $payload['ignoredLines']);
        self::assertCount(1, $payload['matches']);
        self::assertSame(
            ['uniqueId', 'message', 'line', 'indicator', 'lineContent', 'restFiles'],
            array_keys($payload['matches'][0])
        );
        self::assertSame('weak', $payload['matches'][0]['indicator']);
        self::assertSame($callLine, $payload['matches'][0]['line']);
        self::assertSame('Call to method "aMethodToCheck()"', $payload['matches'][0]['message']);
        self::assertNotSame('', $payload['matches'][0]['uniqueId']);
        self::assertStringContainsString('aMethodToCheck', $payload['matches'][0]['lineContent']);
        self::assertSame([], $payload['matches'][0]['restFiles']);
    }
}
