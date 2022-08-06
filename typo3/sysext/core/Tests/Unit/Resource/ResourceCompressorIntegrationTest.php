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

namespace TYPO3\CMS\Core\Tests\Unit\Resource;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Tests\Unit\Resource\ResourceCompressorTest\Fixtures\TestableResourceCompressor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class ResourceCompressorIntegrationTest extends BaseTestCase
{
    protected bool $backupEnvironment = true;

    protected ?TestableResourceCompressor $resourceCompressor;
    protected ?string $fixtureDir;
    protected ?string $fixtureDirFromTest;

    public function setUp(): void
    {
        parent::setUp();
        $this->fixtureDir = 'typo3/sysext/core/Tests/Unit/Resource/ResourceCompressorTest/Fixtures/';
        $this->fixtureDirFromTest = GeneralUtility::fixWindowsFilePath(__DIR__ . '/ResourceCompressorTest/Fixtures/');
    }

    public function tearDown(): void
    {
        $this->testFilesToDelete[] = Environment::getPublicPath() . '/' . $this->resourceCompressor->getTargetDirectory();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function initializeCreatesTargetDirectory(): void
    {
        $this->resourceCompressor = $this->getAccessibleMock(TestableResourceCompressor::class, null);
        $this->resourceCompressor->_call('initialize');
        $dir = Environment::getPublicPath() . '/' . $this->resourceCompressor->getTargetDirectory();
        self::assertFileExists($dir);
    }

    /**
     * @test
     */
    public function initializeCreatesHtaccessFileIfSet(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess'] = true;
        $this->resourceCompressor = $this->getAccessibleMock(TestableResourceCompressor::class, null);
        $this->resourceCompressor->_call('initialize');
        $htaccessPath = Environment::getPublicPath() . '/' . $this->resourceCompressor->getTargetDirectory() . '.htaccess';
        self::assertStringEqualsFile($htaccessPath, $this->resourceCompressor->getHtaccessTemplate());
    }

    /**
     * @test
     */
    public function initializeDoesNotCreateHtaccessFileIfSetToFalse(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess'] = false;
        $this->resourceCompressor = $this->getAccessibleMock(TestableResourceCompressor::class, null);
        $this->resourceCompressor->_call('initialize');
        $htaccessPath = Environment::getPublicPath() . '/' . $this->resourceCompressor->getTargetDirectory() . '.htaccess';
        self::assertFileDoesNotExist($htaccessPath);
    }

    /**
     * @test
     */
    public function concatenateCssFiles(): void
    {
        $files = [
            'sampleFile1' => [
                'excludeFromConcatenation' => false,
                'file' => $this->fixtureDir . 'css_input_with_import.css',
                'media' => 'screen',
                'forceOnTop' => false,
            ],
        ];
        $this->resourceCompressor = new TestableResourceCompressor();
        $concatFiles = $this->resourceCompressor->concatenateCssFiles($files);
        $mergedFile = array_pop($concatFiles);
        $expected = file_get_contents(
            $this->fixtureDirFromTest . 'expected' . DIRECTORY_SEPARATOR . 'merged-css_input_with_import.css'
        );
        self::assertStringEqualsFile(GeneralUtility::fixWindowsFilePath(Environment::getPublicPath() . '/' . $mergedFile['file']), $expected);
    }
    /**
     * @test
     */
    public function concatenateCssFilesWorksWithFileFromNonRootPath(): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $testFile = Environment::getPublicPath() . '/typo3temp/var/transient/css_input_with_import.css';
        $this->testFilesToDelete[] = $testFile;
        copy(Environment::getPublicPath() . '/' . $this->fixtureDir . 'css_input_with_import.css', $testFile);
        $files = [
            'sampleFile1' => [
                'excludeFromConcatenation' => false,
                'file' => 'typo3temp/var/transient/css_input_with_import.css',
                'media' => 'screen',
                'forceOnTop' => false,
            ],
        ];
        $this->resourceCompressor = new TestableResourceCompressor();
        $concatFiles = $this->resourceCompressor->concatenateCssFiles($files);
        $mergedFile = array_pop($concatFiles);
        $expected = file_get_contents(
            $this->fixtureDirFromTest . 'expected' . DIRECTORY_SEPARATOR . 'merged-css_input_with_import_non_root.css'
        );
        self::assertStringEqualsFile(GeneralUtility::fixWindowsFilePath(Environment::getPublicPath() . '/' . $mergedFile['file']), $expected);
    }
}
