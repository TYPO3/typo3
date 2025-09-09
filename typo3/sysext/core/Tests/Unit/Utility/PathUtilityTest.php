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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @todo Defining the method parameter types further leads to test bench errors
 */
final class PathUtilityTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    /**
     * @param string $expected
     */
    #[DataProvider('isCommonPrefixResolvedCorrectlyDataProvider')]
    #[Test]
    public function isCommonPrefixResolvedCorrectly(array $paths, $expected): void
    {
        $commonPrefix = PathUtility::getCommonPrefix($paths);
        self::assertEquals($expected, $commonPrefix);
    }

    public static function isCommonPrefixResolvedCorrectlyDataProvider(): array
    {
        return [
            [
                [
                    '/var/www/myhost.com/t3lib/',
                ],
                '/var/www/myhost.com/t3lib/',
            ],
            [
                [
                    '/var/www/myhost.com/t3lib/',
                    '/var/www/myhost.com/t3lib/',
                ],
                '/var/www/myhost.com/t3lib/',
            ],
            [
                [
                    '/var/www/myhost.com/typo3/',
                    '/var/www/myhost.com/t3lib/',
                ],
                '/var/www/myhost.com/',
            ],
            [
                [
                    '/var/www/myhost.com/typo3/',
                    '/var/www/myhost.com/typo3',
                ],
                '/var/www/myhost.com/typo3/',
            ],
            [
                [
                    '/var/www/myhost.com/typo3',
                    '/var/www/myhost.com/typo3',
                ],
                '/var/www/myhost.com/typo3/',
            ],
            [
                [
                    '/var/www/myhost.com/uploads/',
                    '/var/www/myhost.com/typo3/',
                    '/var/www/myhost.com/t3lib/',
                ],
                '/var/www/myhost.com/',
            ],
            [
                [
                    '/var/www/myhost.com/uploads/directory/',
                    '/var/www/myhost.com/typo3/sysext/',
                    '/var/www/myhost.com/t3lib/utility/',
                ],
                '/var/www/myhost.com/',
            ],
            [
                [
                    'C:\\www\\myhost.com\\t3lib\\',
                ],
                'C:/www/myhost.com/t3lib/',
            ],
            [
                [
                    'C:\\www\\myhost.com\\t3lib\\',
                    'C:\\www\\myhost.com\\t3lib\\',
                ],
                'C:/www/myhost.com/t3lib/',
            ],
            [
                [
                    'C:\\www\\myhost.com\\typo3\\',
                    'C:\\www\\myhost.com\\t3lib\\',
                ],
                'C:/www/myhost.com/',
            ],
            [
                [
                    'C:\\www\\myhost.com\\uploads\\',
                    'C:\\www\\myhost.com\\typo3\\',
                    'C:\\www\\myhost.com\\t3lib\\',
                ],
                'C:/www/myhost.com/',
            ],
            [
                [
                    'C:\\www\\myhost.com\\uploads\\directory\\',
                    'C:\\www\\myhost.com\\typo3\\sysext\\',
                    'C:\\www\\myhost.com\\t3lib\\utility\\',
                ],
                'C:/www/myhost.com/',
            ],
        ];
    }

    /**
     * @param string $source
     * @param string $target
     * @param string $expected
     */
    #[DataProvider('isRelativePathResolvedCorrectlyDataProvider')]
    #[Test]
    #[IgnoreDeprecations]
    public function isRelativePathResolvedCorrectly($source, $target, $expected): void
    {
        $relativePath = PathUtility::getRelativePath($source, $target);
        self::assertEquals($expected, $relativePath);
    }

    public static function isRelativePathResolvedCorrectlyDataProvider(): array
    {
        return [
            [
                '/',
                Environment::getPublicPath() . '/directory',
                null,
            ],
            [
                Environment::getPublicPath() . '/t3lib/',
                Environment::getPublicPath() . '/t3lib/',
                '',
            ],
            [
                Environment::getPublicPath() . '/typo3/',
                Environment::getPublicPath() . '/t3lib/',
                '../t3lib/',
            ],
            [
                Environment::getPublicPath() . '/',
                Environment::getPublicPath() . '/t3lib/',
                't3lib/',
            ],
            [
                Environment::getPublicPath() . '/t3lib/',
                Environment::getPublicPath() . '/t3lib/stddb/',
                'stddb/',
            ],
            [
                Environment::getPublicPath() . '/typo3/sysext/frontend/',
                Environment::getPublicPath() . '/t3lib/utility/',
                '../../../t3lib/utility/',
            ],
        ];
    }

    /**
     * @param string $path
     * @param string $separator
     * @param string $expected
     */
    #[DataProvider('isTrailingSeparatorSanitizedCorrectlyDataProvider')]
    #[Test]
    public function isTrailingSeparatorSanitizedCorrectly($path, $separator, $expected): void
    {
        $sanitizedPath = PathUtility::sanitizeTrailingSeparator($path, $separator);
        self::assertEquals($expected, $sanitizedPath);
    }

    public static function isTrailingSeparatorSanitizedCorrectlyDataProvider(): array
    {
        return [
            ['/var/www//', '/', '/var/www/'],
            ['/var/www/', '/', '/var/www/'],
            ['/var/www', '/', '/var/www/'],
        ];
    }

    /**
     * Data Provider for getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectly
     */
    public static function getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectlyDataProvider(): array
    {
        return [
            'basic' => [
                '/abc/def/one.txt',
                '../two.txt',
                '/abc/two.txt',
            ],
            'same folder' => [
                '/abc/one.txt',
                './two.txt',
                '/abc/two.txt',
            ],
            'preserve relative path if path goes above start path' => [
                'abc/one.txt',
                '../../two.txt',
                '../two.txt',
            ],
            'preserve absolute path even if path goes above start path' => [
                '/abc/one.txt',
                '../../two.txt',
                '/two.txt',
            ],
            'base folder with same folder path' => [
                '/abc/',
                './two.txt',
                '/abc/two.txt',
            ],
            'base folder with parent folder path' => [
                '/abc/bar/',
                '../foo.txt',
                '/abc/foo.txt',
            ],
        ];
    }

    #[DataProvider('getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectlyDataProvider')]
    #[Test]
    public function getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectly(string $baseFileName, string $includeFileName, string $expectedFileName): void
    {
        $resolvedFilename = PathUtility::getAbsolutePathOfRelativeReferencedFileOrPath($baseFileName, $includeFileName);
        self::assertEquals($expectedFileName, $resolvedFilename);
    }

    /**
     * Data provider for getCanonicalPathCorrectlyCleansPath
     *
     * @return string[][]
     */
    public static function getCanonicalPathCorrectlyCleansPathDataProvider(): array
    {
        return [
            'removes single-dot-elements' => [
                'abc/./def/././ghi',
                'abc/def/ghi',
            ],
            'removes ./ at beginning' => [
                './abc/def/ghi',
                'abc/def/ghi',
            ],
            'removes double-slashes' => [
                'abc//def/ghi',
                'abc/def/ghi',
            ],
            'removes double-slashes from front, but keeps absolute path' => [
                '//abc/def/ghi',
                '/abc/def/ghi',
            ],
            'makes double-dot-elements go one level higher, test #1' => [
                'abc/def/ghi/../..',
                'abc',
            ],
            'makes double-dot-elements go one level higher, test #2' => [
                'abc/def/ghi/../123/456/..',
                'abc/def/123',
            ],
            'makes double-dot-elements go one level higher, test #3' => [
                'abc/../../def/ghi',
                '../def/ghi',
            ],
            'makes double-dot-elements go one level higher, test #4' => [
                'abc/def/ghi//../123/456/..',
                'abc/def/123',
            ],
            'truncates slash at the end' => [
                'abc/def/ghi/',
                'abc/def/ghi',
            ],
            'keeps slash in front of absolute paths' => [
                '/abc/def/ghi',
                '/abc/def/ghi',
            ],
            'keeps slash in front of absolute paths even if double-dot-elements want to go higher' => [
                '/abc/../../def/ghi',
                '/def/ghi',
            ],
            'double-dot-elements want to go higher, more than one segment' => [
                '/abc/../../../../def/ghi',
                '/def/ghi',
            ],
            'works with EXT-syntax-paths' => [
                'EXT:abc/def/ghi/',
                'EXT:abc/def/ghi',
            ],
            'truncates ending slash with space' => [
                'abc/def/ ',
                'abc/def',
            ],
            'truncates ending space' => [
                'abc/def ',
                'abc/def',
            ],
            'truncates ending dot' => [
                'abc/def/.',
                'abc/def',
            ],
            'does not truncates ending dot if part of name' => [
                'abc/def.',
                'abc/def.',
            ],
            'protocol is not removed' => [
                'vfs://def/../text.txt',
                'vfs://text.txt',
            ],
            'works with filenames' => [
                '/def/../text.txt',
                '/text.txt',
            ],
            'absolute windows path' => [
                'C:\def\..\..\test.txt',
                'C:/test.txt',
            ],
            'absolute windows path with more segments' => [
                'C:\def\def2\def3\..\..\folder\subfolder\test.txt',
                'C:/def/folder/subfolder/test.txt',
            ],
            'double slashaes' => [
                'abc//def',
                'abc/def',
            ],
            'multiple slashes' => [
                'abc///////def',
                'abc/def',
            ],
        ];
    }

    #[DataProvider('getCanonicalPathCorrectlyCleansPathDataProvider')]
    #[Test]
    public function getCanonicalPathCorrectlyCleansPath(string $inputName, string $expectedResult): void
    {
        // Ensure Environment runs as Windows test
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            'WINDOWS'
        );
        self::assertSame(
            $expectedResult,
            PathUtility::getCanonicalPath($inputName)
        );
    }

    /**
     * Data provider for dirnameDuringBootstrapCorrectlyFetchesParent
     *
     * @return string[][]
     */
    public static function dirnameDuringBootstrapCorrectlyFetchesParentDataProvider(): array
    {
        return [
            'relative path' => [
                'abc/def/ghi',
                'abc/def',
            ],
            'absolute path 1' => [
                '/var/www/html/index.php',
                '/var/www/html',
            ],
            'absolute path 2' => [
                '/var/www/html/typo3/index.php',
                '/var/www/html/typo3',
            ],
            'windows path' => [
                'C:\\inetpub\\index.php',
                'C:\\inetpub',
            ],
        ];
    }

    #[DataProvider('dirnameDuringBootstrapCorrectlyFetchesParentDataProvider')]
    #[Test]
    public function dirnameDuringBootstrapCorrectlyFetchesParent(string $inputPath, string $expectedResult): void
    {
        self::assertSame(
            $expectedResult,
            PathUtility::dirnameDuringBootstrap($inputPath)
        );
    }

    /**
     * Data provider for basenameDuringBootstrapCorrectlyFetchesBasename
     */
    public static function basenameDuringBootstrapCorrectlyFetchesBasenameDataProvider(): array
    {
        return [
            'relative path' => [
                'abc/def/ghi',
                'ghi',
            ],
            'absolute path 1' => [
                '/var/www/html/index.php',
                'index.php',
            ],
            'absolute path 2' => [
                '/var/www/html/typo3/index.php',
                'index.php',
            ],
            'windows path' => [
                'C:\\inetpub\\index.php',
                'index.php',
            ],
        ];
    }

    #[DataProvider('basenameDuringBootstrapCorrectlyFetchesBasenameDataProvider')]
    #[Test]
    public function basenameDuringBootstrapCorrectlyFetchesBasename(string $inputPath, string $expectedResult): void
    {
        self::assertSame(
            $expectedResult,
            PathUtility::basenameDuringBootstrap($inputPath)
        );
    }

    /**
     * Data provider for isAbsolutePathRespectsAllOperatingSystems
     *
     * @return array[]
     */
    public static function isAbsolutePathRespectsAllOperatingSystemsPathDataProvider(): array
    {
        return [
            'starting slash' => [
                '/path',
                false,
                true,
            ],
            'starting slash on windows' => [
                '/path',
                true,
                true,
            ],
            'no match' => [
                'path',
                false,
                false,
            ],
            'no match on windows' => [
                'path',
                true,
                false,
            ],
            'path starts with C:/' => [
                'C:/folder',
                false,
                false,
            ],
            'path starts with C:/ on windows' => [
                'C:/folder',
                true,
                true,
            ],
            'path starts with C:\\' => [
                'C:\\folder',
                false,
                false,
            ],
            'path starts with C:\\ on windows' => [
                'C:\\folder',
                true,
                true,
            ],
            'path empty' => [
                '',
                false,
                false,
            ],
        ];
    }

    #[DataProvider('isAbsolutePathRespectsAllOperatingSystemsPathDataProvider')]
    #[Test]
    public function isAbsolutePathRespectsAllOperatingSystems(string $inputPath, bool $isWindows, bool $expectedResult): void
    {
        if ($isWindows) {
            // Ensure Environment runs as Windows test
            Environment::initialize(
                Environment::getContext(),
                true,
                false,
                Environment::getProjectPath(),
                Environment::getPublicPath(),
                Environment::getVarPath(),
                Environment::getConfigPath(),
                Environment::getCurrentScript(),
                'WINDOWS'
            );
        }

        self::assertSame($expectedResult, PathUtility::isAbsolutePath($inputPath));
    }

    public static function hasProtocolAndSchemeDataProvider(): array
    {
        return [
            ['//example.com/demo.html', true],
            ['http://example.com/demo.html', true],
            ['https://example.com/demo.html', true],
            ['f://example.com/demo.html', true],
            ['f:/example.com/demo.html', false],
            ['://example.com/demo.html', false],
            [':/example.com/demo.html', false],
            ['/example.com/demo.html', false],
            ['example.com/demo.html', false],
            ['demo.html', false],
            ['\\\\server\\unc-path\\demo.html', false],
            ['\\\\example.com\\demo.html', false],
        ];
    }

    #[DataProvider('hasProtocolAndSchemeDataProvider')]
    #[Test]
    public function hasProtocolAndScheme(string $url, bool $result): void
    {
        self::assertSame($result, PathUtility::hasProtocolAndScheme($url));
    }

    public static function allowedAdditionalPathsAreEvaluatedDataProvider(): \Generator
    {
        // empty settings
        yield [null, '/var/shared', false];
        yield ['', '/var/shared', false];
        yield [' ', '/var/shared', false];
        yield [[], '/var/shared', false];
        yield [[''], '/var/shared', false];
        yield [[' '], '/var/shared', false];
        // string settings
        yield ['/var', '/var/shared', true];
        yield ['/var/shared/', '/var/shared', true];
        yield ['/var/shared', '/var/shared/', true];
        yield ['/var/shared/', '/var/shared/', true];
        yield ['/var/shared/', '/var/shared/file.png', true];
        yield ['/var/shared/', '/var/shared-secret', false];
        yield ['/var/shared/', '/var', false];
        // array settings
        yield [['/var'], '/var/shared', true];
        yield [['/var/shared/'], '/var/shared', true];
        yield [['/var/shared'], '/var/shared/', true];
        yield [['/var/shared/'], '/var/shared/', true];
        yield [['/var/shared/'], '/var/shared/file.png', true];
        yield [['/var/shared/'], '/var/shared-secret', false];
        yield [['/var/shared/'], '/var', false];
    }

    #[DataProvider('allowedAdditionalPathsAreEvaluatedDataProvider')]
    #[Test]
    public function allowedAdditionalPathsAreEvaluated(mixed $lockRootPath, string $path, bool $expectation): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath'] = $lockRootPath;
        self::assertSame($expectation, PathUtility::isAllowedAdditionalPath($path));
    }
}
