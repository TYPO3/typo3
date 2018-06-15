<?php

namespace TYPO3\CMS\Core\Tests\Unit\Resource;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Tests\Unit\Resource\ResourceCompressorTest\Fixtures\TestableResourceCompressor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class ResourceCompressorIntegrationTest extends BaseTestCase
{
    /**
     * Subject is not notice free, disable E_NOTICES
     */
    protected static $suppressNotices = true;

    /**
     * @var TestableResourceCompressor
     */
    protected $resourceCompressor;

    /**
     * @var string
     */
    protected $fixtureDir;

    /**
     * @var string
     */
    protected $fixtureDirFromTest;

    public function setUp()
    {
        $this->fixtureDir = 'sysext/core/Tests/Unit/Resource/ResourceCompressorTest/Fixtures/';
        $this->fixtureDirFromTest = GeneralUtility::fixWindowsFilePath(__DIR__ . '/ResourceCompressorTest/Fixtures/');
    }

    /**
     * @test
     */
    public function constructorCreatesTargetDirectory()
    {
        $this->resourceCompressor = new TestableResourceCompressor();
        $dir = Environment::getPublicPath() . '/' . $this->resourceCompressor->getTargetDirectory();
        self::assertFileExists($dir);
    }

    /**
     * @test
     */
    public function constructorCreatesHtaccessFileIfSet()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess'] = true;
        $this->resourceCompressor = new TestableResourceCompressor();
        $htaccessPath = Environment::getPublicPath() . '/' . $this->resourceCompressor->getTargetDirectory() . '.htaccess';
        self::assertStringEqualsFile($htaccessPath, $this->resourceCompressor->getHtaccessTemplate());
    }

    /**
     * @test
     */
    public function constructorDoesNotCreateHtaccessFileIfSetToFalse()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess'] = false;
        $this->resourceCompressor = new TestableResourceCompressor();
        $htaccessPath = Environment::getPublicPath() . '/' . $this->resourceCompressor->getTargetDirectory() . '.htaccess';
        self::assertFileNotExists($htaccessPath);
    }

    /**
     * @test
     */
    public function concatenateCssFiles()
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
    public function concatenateCssFilesWorksWithFileFromNonRootPath()
    {
        $testFile = Environment::getPublicPath() . '/typo3temp/var/transient/css_input_with_import.css';
        $this->testFilesToDelete[] = $testFile;
        copy(Environment::getBackendPath() . '/' . $this->fixtureDir . 'css_input_with_import.css', $testFile);
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

    public function tearDown()
    {
        $this->testFilesToDelete[] = Environment::getPublicPath() . '/' . $this->resourceCompressor->getTargetDirectory();
        parent::tearDown();
    }
}
