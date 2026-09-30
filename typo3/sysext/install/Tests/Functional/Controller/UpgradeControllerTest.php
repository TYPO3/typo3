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

namespace TYPO3\CMS\Install\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\UpgradeController;
use TYPO3\CMS\Install\ExtensionScanner\ExtensionScannerService;
use TYPO3\CMS\Install\ExtensionScanner\Result\Indicator;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanMatch;
use TYPO3\CMS\Install\ExtensionScanner\Result\ScanResult;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The extension scanner runs in the install tool, which is served from the failsafe
 * container. These tests therefore boot a failsafe container like the install tool does and
 * dispatch the controller action from there: that is the only place where the wiring of
 * UpgradeController and ExtensionScannerService in EXT:install's ServiceProvider is exercised.
 */
final class UpgradeControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['install'];

    protected array $configurationToUseInTestInstance = [
        'BE' => [
            // Non-empty to satisfy the install tool integrity checks, value irrelevant.
            'installToolPassword' => '$argon2i$v=19$m=65536,t=16,p=1$placeholder',
        ],
    ];

    #[Test]
    public function extensionScannerFilesActionIsServedFromTheFailsafeContainer(): void
    {
        $response = $this->dispatchInstallToolAction(
            'extensionScannerFilesAction',
            ['extension' => 'install']
        );
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($payload['success']);
        self::assertNotSame([], $payload['files']);
        self::assertContains('Classes/Controller/UpgradeController.php', $payload['files']);
        foreach ($payload['files'] as $file) {
            self::assertStringEndsWith('.php', $file);
            // Relative to the extension directory, as the JavaScript expects it.
            self::assertStringStartsNotWith('/', $file);
        }
    }

    #[Test]
    public function extensionScannerScanFileActionScansAFileWithTheRealRuleSet(): void
    {
        $response = $this->dispatchInstallToolAction(
            'extensionScannerScanFileAction',
            [
                'extension' => 'install',
                // A file added together with the scanner service, so it can not use anything
                // the current rule set knows about.
                'file' => 'Classes/ExtensionScanner/Result/Indicator.php',
            ]
        );
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(
            ['success', 'matches', 'isFileIgnored', 'effectiveCodeLines', 'ignoredLines'],
            array_keys($payload)
        );
        self::assertTrue($payload['success']);
        self::assertSame([], $payload['matches']);
        self::assertFalse($payload['isFileIgnored']);
        self::assertGreaterThan(0, $payload['effectiveCodeLines']);
        self::assertSame(0, $payload['ignoredLines']);
    }

    /**
     * The only part of the mapper that still resolves something: a changelog file name is turned
     * into the full entry the install tool JavaScript renders. This pins the key set and its
     * order, which is the actual promise of the refactoring, and it is the one place that can
     * still throw (exception 1499803909 for a name that is missing or ambiguous).
     */
    #[Test]
    public function preparingMatchesResolvesChangelogFilesForTheUserInterface(): void
    {
        $subject = new class (
            $this->get(PackageManager::class),
            $this->get(LateBootService::class),
            $this->get(FormProtectionFactory::class),
            $this->get(ExtensionScannerService::class),
        ) extends UpgradeController {
            /**
             * @return list<array<string, mixed>>
             */
            public function prepareMatchesForTest(ScanResult $result, string $absoluteFilePath): array
            {
                return $this->extensionScannerPrepareMatches($result, $absoluteFilePath);
            }
        };

        $scannedFile = ExtensionManagementUtility::extPath('install') . 'Classes/ExtensionScanner/Result/Indicator.php';
        // The line holding "case Strong", so lineContent is verifiable. Looked up instead of
        // hard-coded, so that edits to the docblock above it do not break this test.
        $caseStrongLine = 1 + (int)array_key_first(preg_grep('/case Strong/', file($scannedFile)));
        $result = new ScanResult(
            $scannedFile,
            [new ScanMatch(
                $caseStrongLine,
                Indicator::Strong,
                'ClassNameMatcher',
                'Usage of class "RemoveXSS"',
                ['Breaking-80700-DeprecatedFunctionalityRemoved.rst']
            )],
            null,
            false,
            20,
            0
        );

        $prepared = $subject->prepareMatchesForTest($result, $scannedFile);

        self::assertCount(1, $prepared);
        self::assertSame(
            ['uniqueId', 'message', 'line', 'indicator', 'lineContent', 'restFiles'],
            array_keys($prepared[0])
        );
        self::assertSame('strong', $prepared[0]['indicator']);
        self::assertStringContainsString('case Strong', $prepared[0]['lineContent']);
        self::assertCount(1, $prepared[0]['restFiles']);

        $restFile = $prepared[0]['restFiles'][0];
        self::assertSame(
            [
                'version',
                'headline',
                'filepath',
                'filename',
                'tags',
                'class',
                'tagList',
                'tagArray',
                'content',
                'parsedContent',
                'file_hash',
                'url',
                'uniqueId',
            ],
            array_keys($restFile)
        );
        self::assertSame('Breaking-80700-DeprecatedFunctionalityRemoved', $restFile['filename']);
        self::assertNotSame('', $restFile['version']);
        self::assertNotSame('', $restFile['headline']);
        self::assertArrayHasKey('documentation', $restFile['url']);
    }

    #[Test]
    public function preparingMatchesRejectsAnUnknownChangelogFile(): void
    {
        $subject = new class (
            $this->get(PackageManager::class),
            $this->get(LateBootService::class),
            $this->get(FormProtectionFactory::class),
            $this->get(ExtensionScannerService::class),
        ) extends UpgradeController {
            /**
             * @return list<array<string, mixed>>
             */
            public function prepareMatchesForTest(ScanResult $result, string $absoluteFilePath): array
            {
                return $this->extensionScannerPrepareMatches($result, $absoluteFilePath);
            }
        };

        $scannedFile = ExtensionManagementUtility::extPath('install') . 'Classes/ExtensionScanner/Result/Indicator.php';
        $result = new ScanResult(
            $scannedFile,
            [new ScanMatch(29, Indicator::Strong, 'ClassNameMatcher', 'msg', ['No-Such-Changelog-File.rst'])],
            null,
            false,
            20,
            0
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1499803909);

        $subject->prepareMatchesForTest($result, $scannedFile);
    }

    /**
     * @param array<string, mixed> $installParameters
     */
    private function dispatchInstallToolAction(string $action, array $installParameters = []): ResponseInterface
    {
        $request = new ServerRequest(
            new Uri('http://localhost/typo3/install.php?install%5Bcontroller%5D=upgrade&install%5Bcontext%5D=install'),
            'POST',
            'php://input',
            [],
            [
                'SCRIPT_NAME' => '/typo3/install.php',
                'HTTP_HOST' => 'localhost',
                'SERVER_NAME' => 'localhost',
                'HTTPS' => 'off',
                'REMOTE_ADDR' => '127.0.0.1',
            ],
        )
            ->withQueryParams(['install' => ['controller' => 'upgrade', 'context' => 'install']])
            ->withParsedBody(['install' => array_merge(['controller' => 'upgrade', 'context' => 'install'], $installParameters)]);

        $testContainer = GeneralUtility::getContainer();
        $testSingletonInstances = GeneralUtility::getSingletonInstances();
        $outputBufferingLevel = ob_get_level();
        try {
            $failsafeContainer = Bootstrap::init(ClassLoadingInformation::getClassLoader(), true);
            $controller = $failsafeContainer->get(UpgradeController::class);
            return $controller->{$action}($request);
        } finally {
            // Drop output buffers opened by the bootstrap, but never phpunit's own one.
            while (ob_get_level() > $outputBufferingLevel) {
                ob_end_clean();
            }
            GeneralUtility::purgeInstances();
            GeneralUtility::setContainer($testContainer);
            GeneralUtility::resetSingletonInstances($testSingletonInstances);
            ExtensionManagementUtility::setPackageManager($testContainer->get(PackageManager::class));
        }
    }
}
