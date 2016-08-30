<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\WindowsPathUtilityFixture;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\PathUtility
 */
class PathUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @param array $paths
     * @param string $expected
     * @dataProvider isCommonPrefixResolvedCorrectlyDataProvider
     * @test
     */
    public function isCommonPrefixResolvedCorrectly(array $paths, $expected)
    {
        $commonPrefix = \TYPO3\CMS\Core\Utility\PathUtility::getCommonPrefix($paths);
        $this->assertEquals($expected, $commonPrefix);
    }

    /**
     * @return array
     */
    public function isCommonPrefixResolvedCorrectlyDataProvider()
    {
        return [
            [
                [
                    '/var/www/myhost.com/t3lib/'
                ],
                '/var/www/myhost.com/t3lib/'
            ],
            [
                [
                    '/var/www/myhost.com/t3lib/',
                    '/var/www/myhost.com/t3lib/'
                ],
                '/var/www/myhost.com/t3lib/'
            ],
            [
                [
                    '/var/www/myhost.com/typo3/',
                    '/var/www/myhost.com/t3lib/'
                ],
                '/var/www/myhost.com/'
            ],
            [
                [
                    '/var/www/myhost.com/uploads/',
                    '/var/www/myhost.com/typo3/',
                    '/var/www/myhost.com/t3lib/'
                ],
                '/var/www/myhost.com/'
            ],
            [
                [
                    '/var/www/myhost.com/uploads/directory/',
                    '/var/www/myhost.com/typo3/sysext/',
                    '/var/www/myhost.com/t3lib/utility/'
                ],
                '/var/www/myhost.com/'
            ],
            [
                [
                    'C:\\www\\myhost.com\\t3lib\\'
                ],
                'C:/www/myhost.com/t3lib/'
            ],
            [
                [
                    'C:\\www\\myhost.com\\t3lib\\',
                    'C:\\www\\myhost.com\\t3lib\\'
                ],
                'C:/www/myhost.com/t3lib/'
            ],
            [
                [
                    'C:\\www\\myhost.com\\typo3\\',
                    'C:\\www\\myhost.com\\t3lib\\'
                ],
                'C:/www/myhost.com/'
            ],
            [
                [
                    'C:\\www\\myhost.com\\uploads\\',
                    'C:\\www\\myhost.com\\typo3\\',
                    'C:\\www\\myhost.com\\t3lib\\'
                ],
                'C:/www/myhost.com/'
            ],
            [
                [
                    'C:\\www\\myhost.com\\uploads\\directory\\',
                    'C:\\www\\myhost.com\\typo3\\sysext\\',
                    'C:\\www\\myhost.com\\t3lib\\utility\\'
                ],
                'C:/www/myhost.com/'
            ]
        ];
    }

    /**
     * @param string $source
     * @param string $target
     * @param string $expected
     * @dataProvider isRelativePathResolvedCorrectlyDataProvider
     * @test
     */
    public function isRelativePathResolvedCorrectly($source, $target, $expected)
    {
        $relativePath = \TYPO3\CMS\Core\Utility\PathUtility::getRelativePath($source, $target);
        $this->assertEquals($expected, $relativePath);
    }

    /**
     * @return array
     */
    public function isRelativePathResolvedCorrectlyDataProvider()
    {
        return [
            [
                '/',
                PATH_site . 'directory',
                null
            ],
            [
                PATH_site . 't3lib/',
                PATH_site . 't3lib/',
                ''
            ],
            [
                PATH_site . 'typo3/',
                PATH_site . 't3lib/',
                '../t3lib/'
            ],
            [
                PATH_site,
                PATH_site . 't3lib/',
                't3lib/'
            ],
            [
                PATH_site . 't3lib/',
                PATH_site . 't3lib/stddb/',
                'stddb/'
            ],
            [
                PATH_site . 'typo3/sysext/frontend/',
                PATH_site . 't3lib/utility/',
                '../../../t3lib/utility/'
            ],
        ];
    }

    /**
     * @param string $path
     * @param string $separator
     * @param string $expected
     * @dataProvider isTrailingSeparatorSanitizedCorrectlyDataProvider
     * @test
     */
    public function isTrailingSeparatorSanitizedCorrectly($path, $separator, $expected)
    {
        $sanitizedPath = \TYPO3\CMS\Core\Utility\PathUtility::sanitizeTrailingSeparator($path, $separator);
        $this->assertEquals($expected, $sanitizedPath);
    }

    /**
     * @return array
     */
    public function isTrailingSeparatorSanitizedCorrectlyDataProvider()
    {
        return [
            ['/var/www//', '/', '/var/www/'],
            ['/var/www/', '/', '/var/www/'],
            ['/var/www', '/', '/var/www/']
        ];
    }

    /**
     * Data Provider for getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectly
     *
     * @return array
     */
    public function getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectlyDataProvider()
    {
        return [
            'basic' => [
                '/abc/def/one.txt',
                '../two.txt',
                '/abc/two.txt'
            ],
            'same folder' => [
                '/abc/one.txt',
                './two.txt',
                '/abc/two.txt'
            ],
            'preserve relative path if path goes above start path' => [
                'abc/one.txt',
                '../../two.txt',
                '../two.txt'
            ],
            'preserve absolute path even if path goes above start path' => [
                '/abc/one.txt',
                '../../two.txt',
                '/two.txt',
            ],
            'base folder with same folder path' => [
                '/abc/',
                './two.txt',
                '/abc/two.txt'
            ],
            'base folder with parent folder path' => [
                '/abc/bar/',
                '../foo.txt',
                '/abc/foo.txt'
            ],
        ];
    }

    /**
     * @param $baseFileName
     * @param $includeFileName
     * @param $expectedFileName
     * @test
     * @dataProvider getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectlyDataProvider
     */
    public function getAbsolutePathOfRelativeReferencedFileOrPathResolvesFileCorrectly($baseFileName, $includeFileName, $expectedFileName)
    {
        $resolvedFilename = \TYPO3\CMS\Core\Utility\PathUtility::getAbsolutePathOfRelativeReferencedFileOrPath($baseFileName, $includeFileName);
        $this->assertEquals($expectedFileName, $resolvedFilename);
    }

    /**
     * Data provider for getCanonicalPathCorrectlyCleansPath
     *
     * @return array
     */
    public function getCanonicalPathCorrectlyCleansPathDataProvider()
    {
        return [
            'removes single-dot-elements' => [
                'abc/./def/././ghi',
                'abc/def/ghi'
            ],
            'removes ./ at beginning' => [
                './abc/def/ghi',
                'abc/def/ghi'
            ],
            'removes double-slashes' => [
                'abc//def/ghi',
                'abc/def/ghi'
            ],
            'removes double-slashes from front, but keeps absolute path' => [
                '//abc/def/ghi',
                '/abc/def/ghi'
            ],
            'makes double-dot-elements go one level higher, test #1' => [
                'abc/def/ghi/../..',
                'abc'
            ],
            'makes double-dot-elements go one level higher, test #2' => [
                'abc/def/ghi/../123/456/..',
                'abc/def/123'
            ],
            'makes double-dot-elements go one level higher, test #3' => [
                'abc/../../def/ghi',
                '../def/ghi'
            ],
            'makes double-dot-elements go one level higher, test #4' => [
                'abc/def/ghi//../123/456/..',
                'abc/def/123'
            ],
            'truncates slash at the end' => [
                'abc/def/ghi/',
                'abc/def/ghi'
            ],
            'keeps slash in front of absolute paths' => [
                '/abc/def/ghi',
                '/abc/def/ghi'
            ],
            'keeps slash in front of absolute paths even if double-dot-elements want to go higher' => [
                '/abc/../../def/ghi',
                '/def/ghi'
            ],
            'works with EXT-syntax-paths' => [
                'EXT:abc/def/ghi/',
                'EXT:abc/def/ghi'
            ],
            'truncates ending slash with space' => [
                'abc/def/ ',
                'abc/def'
            ],
            'truncates ending space' => [
                'abc/def ',
                'abc/def'
            ],
            'truncates ending dot' => [
                'abc/def/.',
                'abc/def'
            ],
            'does not truncates ending dot if part of name' => [
                'abc/def.',
                'abc/def.'
            ],
            'protocol is not removed' => [
                'vfs://def/../text.txt',
                'vfs://text.txt'
            ],
            'works with filenames' => [
                '/def/../text.txt',
                '/text.txt'
            ],
            'absolute windwos path' => [
                'C:\def\..\..\test.txt',
                'C:/test.txt'
            ],
            'double slashaes' => [
                'abc//def',
                'abc/def'
            ],
            'multiple slashes' => [
                'abc///////def',
                'abc/def'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getCanonicalPathCorrectlyCleansPathDataProvider
     */
    public function getCanonicalPathCorrectlyCleansPath($inputName, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            WindowsPathUtilityFixture::getCanonicalPath($inputName)
        );
    }
}
