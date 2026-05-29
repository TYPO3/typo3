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

namespace TYPO3\CMS\Frontend\Tests\Unit\Typolink;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Tests\Unit\Typolink\Fixtures\AbstractTypolinkBuilderFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractTypolinkBuilderTest extends UnitTestCase
{
    public static function forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider(): array
    {
        return [
            'Missing forceAbsoluteUrl leaves URL untouched' => [
                'foo',
                'foo',
                [],
            ],
            'Absolute URL stays unchanged' => [
                'http://example.org/',
                'http://example.org/',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Absolute URL stays unchanged 2' => [
                'http://example.org/resource.html',
                'http://example.org/resource.html',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Scheme and host w/o ending slash stays unchanged' => [
                'http://example.org',
                'http://example.org',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Scheme can be forced' => [
                'typo3://example.org',
                'http://example.org',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3',
                    ],
                ],
            ],
            'Relative path old-style' => [
                'http://localhost/fileadmin/dummy.txt',
                '/fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Relative path' => [
                'http://localhost/fileadmin/dummy.txt',
                'fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Scheme can be forced with pseudo-relative path' => [
                'typo3://localhost/fileadmin/dummy.txt',
                '/fileadmin/dummy.txt',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3',
                    ],
                ],
            ],
            'Hostname only is not treated as valid absolute URL' => [
                'http://localhost/example.org',
                'example.org',
                [
                    'forceAbsoluteUrl' => '1',
                ],
            ],
            'Scheme and host is added to local file path' => [
                'typo3://localhost/fileadmin/my.pdf',
                'fileadmin/my.pdf',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3',
                    ],
                ],
            ],
            'Scheme can be forced with full URL with path' => [
                'typo3://example.org/subfolder/file.txt',
                'http://example.org/subfolder/file.txt',
                [
                    'forceAbsoluteUrl' => '1',
                    'forceAbsoluteUrl.' => [
                        'scheme' => 'typo3',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('forceAbsoluteUrlReturnsCorrectAbsoluteUrlDataProvider')]
    #[Test]
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrl(string $expected, string $url, array $configuration): void
    {
        $serverRequest = new ServerRequest(
            'http://localhost/index.php',
            'GET',
            null,
            [],
            ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/index.php']
        );
        $subject = new AbstractTypolinkBuilderFixture();
        self::assertEquals($expected, $subject->forceAbsoluteUrl($url, $configuration, $serverRequest));
    }

    #[Test]
    public function forceAbsoluteUrlReturnsCorrectAbsoluteUrlWithSubfolder(): void
    {
        $serverRequest = new ServerRequest(
            'http://localhost/subfolder/index.php',
            'GET',
            null,
            [],
            ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/subfolder/index.php']
        );
        $subject = new AbstractTypolinkBuilderFixture();
        $expected = 'http://localhost/subfolder/fileadmin/my.pdf';
        $url = 'fileadmin/my.pdf';
        $configuration = [
            'forceAbsoluteUrl' => '1',
        ];
        self::assertEquals($expected, $subject->forceAbsoluteUrl($url, $configuration, $serverRequest));
    }

    public static function resolveTargetAttributeDataProvider(): array
    {
        $targetName = StringUtility::getUniqueId('name_');
        $target = StringUtility::getUniqueId('target_');
        return [
            'Take target from $conf, if $conf[$targetName] is set.' => [
                'expected' => $target,
                'conf' => [$targetName => $target], // $targetName is set
                'name' => $targetName,
            ],
            ' If all hopes fail, an empty string is returned. ' => [
                'expected' => '',
                'conf' => [],
                'name' => $targetName,
            ],
        ];
    }

    #[DataProvider('resolveTargetAttributeDataProvider')]
    #[Test]
    public function canResolveTheTargetAttribute(string $expected, array $conf, string $name): void
    {
        $cObj = $this->createMock(ContentObjectRenderer::class);
        $cObj->method('stdWrap')->willReturnArgument(0);
        $subject = new AbstractTypolinkBuilderFixture();
        self::assertEquals($expected, $subject->resolveTargetAttribute($conf, $name, $cObj));
    }

    #[Test]
    public function resolveTargetAttributeAppliesStdWrap(): void
    {
        $cObj = $this->createMock(ContentObjectRenderer::class);
        $cObj->expects($this->once())->method('stdWrap')->willReturn('called');
        $subject = new AbstractTypolinkBuilderFixture();
        $conf = ['target' . '.' => ['ifEmpty' => 'wrap_target']];
        self::assertEquals('called', $subject->resolveTargetAttribute($conf, 'target', $cObj));
    }
}
