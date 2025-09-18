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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceCompressor;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ResourceCompressorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3temp/assets/compressed/', true);
        parent::tearDown();
    }

    #[Test]
    public function initializeCreatesTargetDirectory(): void
    {
        $subject = $this->getAccessibleMock(originalClassName: ResourceCompressor::class, methods: null, callOriginalConstructor: false);
        $subject->_call('initialize');
        self::assertFileExists($this->instancePath . '/typo3temp/assets/compressed');
    }

    #[Test]
    public function initializeCreatesHtaccessFileIfSet(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess'] = true;
        $subject = $this->getAccessibleMock(originalClassName: ResourceCompressor::class, methods: null, callOriginalConstructor: false);
        $subject->_call('initialize');
        $htaccessPath = $this->instancePath . '/typo3temp/assets/compressed/.htaccess';
        self::assertStringEqualsFile($htaccessPath, $subject->_get('htaccessTemplate'));
    }

    #[Test]
    public function initializeDoesNotCreateHtaccessFileIfSetToFalse(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess'] = false;
        $subject = $this->getAccessibleMock(originalClassName: ResourceCompressor::class, methods: null, callOriginalConstructor: false);
        $subject->_call('initialize');
        $htaccessPath = $this->instancePath . '/typo3temp/assets/compressed/.htaccess';
        self::assertFileDoesNotExist($htaccessPath);
    }

    #[Test]
    public function concatenateCssFiles(): void
    {
        $files = [
            'sampleFile1' => [
                'excludeFromConcatenation' => false,
                'file' => 'typo3/sysext/core/Tests/Functional/Resource/Fixtures/ResourceCompressor/css_input_with_import.css',
                'media' => 'screen',
                'forceOnTop' => false,
            ],
        ];
        $subject = $this->get(ResourceCompressor::class);
        $concatFiles = $subject->concatenateCssFiles($files);
        $mergedFile = array_pop($concatFiles);
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/ResourceCompressor/css_input_with_import.expected.css',
            file_get_contents(Environment::getPublicPath() . '/' . $mergedFile['file']) . chr(10)
        );
    }

    public static function compressCssFileContentDataProvider(): array
    {
        return [
            // File. Tests:
            // - Stripped comments and white-space.
            // - Retain white-space in selectors. (http://drupal.org/node/472820)
            // - Retain pseudo-selectors. (http://drupal.org/node/460448)
            0 => [
                __DIR__ . '/Fixtures/ResourceCompressor/css_input_without_import.css',
                __DIR__ . '/Fixtures/ResourceCompressor/css_input_without_import.css.optimized.css',
            ],
            // File. Tests:
            // - Retain comment hacks.
            2 => [
                __DIR__ . '/Fixtures/ResourceCompressor/comment_hacks.css',
                __DIR__ . '/Fixtures/ResourceCompressor/comment_hacks.css.optimized.css',
            ], /*
            // File. Tests:
            // - Any @charset declaration at the beginning of a file should be
            //   removed without breaking subsequent CSS.*/
            6 => [
                __DIR__ . '/Fixtures/ResourceCompressor/charset_sameline.css',
                __DIR__ . '/Fixtures/ResourceCompressor/charset.css.optimized.css',
            ],
            7 => [
                __DIR__ . '/Fixtures/ResourceCompressor/charset_newline.css',
                __DIR__ . '/Fixtures/ResourceCompressor/charset.css.optimized.css',
            ],
        ];
    }

    /**
     * Tests optimizing a CSS asset group.
     */
    #[DataProvider('compressCssFileContentDataProvider')]
    #[Test]
    public function compressCssFileContent(string $cssFile, string $expected): void
    {
        $cssContent = file_get_contents($cssFile);
        $subject = $this->getAccessibleMock(originalClassName: ResourceCompressor::class, methods: ['compressCssFile', 'compressJsFile', 'createMergedCssFile', 'createMergedJsFile', 'getFilenameFromMainDir'], callOriginalConstructor: false);
        $subject->_call('initialize');
        $compressedCss = $subject->_call('compressCssString', $cssContent);
        // we have to fix relative paths, if we aren't working on a file in our target directory
        $relativeFilename = str_replace(Environment::getPublicPath() . '/', '', $cssFile);
        $compressedCss = $subject->_call('cssFixRelativeUrlPaths', $compressedCss, PathUtility::dirname($relativeFilename) . '/');
        self::assertStringEqualsFile($expected, $compressedCss);
    }

    public static function getFilenamesFromMainDirInFrontendContextDataProvider(): array
    {
        return [
            // Get filename using EXT:
            [
                'EXT:backend/Resources/Public/Css/backend.css',
                'typo3/sysext/backend/Resources/Public/Css/backend.css',
            ],
            // Get filename using relative path
            [
                'typo3/sysext/core/Tests/Functional/Resource/Fixtures/ResourceCompressor/Resources/Public/charset.css',
                'typo3/sysext/core/Tests/Functional/Resource/Fixtures/ResourceCompressor/Resources/Public/charset.css',
            ],
            [
                'typo3temp/assets/compressed/.htaccess',
                'typo3temp/assets/compressed/.htaccess',
            ],
        ];
    }

    #[DataProvider('getFilenamesFromMainDirInFrontendContextDataProvider')]
    #[Test]
    public function getFilenamesFromMainDirInFrontendContext(string $filename, string $expected): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            // Frontend Context
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $subject = $this->getAccessibleMock(originalClassName: ResourceCompressor::class, methods: null, callOriginalConstructor: false);
        $subject->_call('initialize');
        $relativeToRootPath = $subject->_call('getFilenameFromMainDir', $filename);
        self::assertSame($expected, $relativeToRootPath);
    }
}
