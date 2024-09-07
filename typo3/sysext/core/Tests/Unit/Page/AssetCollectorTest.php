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

namespace TYPO3\CMS\Core\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AssetCollectorTest extends UnitTestCase
{
    public static function filesDataProvider(): array
    {
        return [
            '1 file from fileadmin' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', [], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            '1 file from extension' => [
                'files' => [
                    ['file1', 'EXT:core/Resource/Public/foo.ext', [], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'EXT:core/Resource/Public/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            '1 file with suspicious source' => [
                'files' => [
                    ['file1', '"><script>alert(1)</script><x "', [], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => '"><script>alert(1)</script><x "',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            '1 file from external source' => [
                'files' => [
                    ['file1', 'https://typo3.org/foo.ext', [], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'https://typo3.org/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            '1 file from external source with one parameter' => [
                'files' => [
                    ['file1', 'https://typo3.org/foo.ext?foo=bar', [], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'https://typo3.org/foo.ext?foo=bar',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            '1 file from external source with two parameters' => [
                'files' => [
                    ['file1', 'https://typo3.org/foo.ext?foo=bar&bar=baz', [], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'https://typo3.org/foo.ext?foo=bar&bar=baz',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            '2 files' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', [], []],
                    ['file2', 'EXT:core/Resource/Public/foo.ext', [], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ],
                    'file2' => [
                        'source' => 'EXT:core/Resource/Public/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            '2 files with override' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', [], []],
                    ['file2', 'EXT:core/Resource/Public/foo.ext', [], []],
                    ['file1', 'EXT:core/Resource/Public/bar.ext', [], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'EXT:core/Resource/Public/bar.ext',
                        'attributes' => [],
                        'options' => [],
                    ],
                    'file2' => [
                        'source' => 'EXT:core/Resource/Public/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            '1 file with attributes' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', ['rel' => 'foo'], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [
                            'rel' => 'foo',
                        ],
                        'options' => [],
                    ],
                ],
            ],
            '1 file with controlled type' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', ['type' => 'module'], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [
                            'type' => 'module',
                        ],
                        'options' => [],
                    ],
                ],
            ],
            '1 file with attributes override' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', ['rel' => 'foo', 'another' => 'keep on override'], []],
                    ['file1', 'fileadmin/foo.ext', ['rel' => 'bar'], []],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [
                            'rel' => 'bar',
                            'another' => 'keep on override',
                        ],
                        'options' => [],
                    ],
                ],
            ],
            '1 file with options' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', [], ['priority' => true]],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [],
                        'options' => [
                            'priority' => true,
                        ],
                    ],
                ],
            ],
            '1 file with options override' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', [], ['priority' => true, 'another' => 'keep on override']],
                    ['file1', 'fileadmin/foo.ext', [], ['priority' => false]],
                ],
                'expectedResult' => [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [],
                        'options' => [
                            'priority' => false,
                            'another' => 'keep on override',
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('filesDataProvider')]
    #[Test]
    public function styleSheets(array $files, array $expectedResult): void
    {
        $assetCollector = new AssetCollector();
        foreach ($files as $file) {
            [$identifier, $source, $attributes, $options] = $file;
            $assetCollector->addStyleSheet($identifier, $source, $attributes, $options);
            self::assertTrue($assetCollector->hasStyleSheet($identifier));
            self::assertFalse($assetCollector->hasInlineStyleSheet($identifier));
            self::assertFalse($assetCollector->hasInlineJavaScript($identifier));
            self::assertFalse($assetCollector->hasJavaScript($identifier));
            self::assertFalse($assetCollector->hasMedia($identifier));
        }
        self::assertSame($expectedResult, $assetCollector->getStyleSheets());
        self::assertSame([], $assetCollector->getInlineStyleSheets());
        self::assertSame([], $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getJavaScripts());
        self::assertSame([], $assetCollector->getMedia());
        foreach ($files as $file) {
            [$identifier] = $file;
            $assetCollector->removeStyleSheet($identifier);
        }
        self::assertSame([], $assetCollector->getStyleSheets());
        self::assertSame([], $assetCollector->getInlineStyleSheets());
        self::assertSame([], $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getJavaScripts());
        self::assertSame([], $assetCollector->getMedia());
    }

    #[DataProvider('filesDataProvider')]
    #[Test]
    public function javaScript(array $files, array $expectedResult): void
    {
        $assetCollector = new AssetCollector();
        foreach ($files as $file) {
            [$identifier, $source, $attributes, $options] = $file;
            $assetCollector->addJavaScript($identifier, $source, $attributes, $options);
            self::assertTrue($assetCollector->hasJavaScript($identifier));
            self::assertFalse($assetCollector->hasInlineStyleSheet($identifier));
            self::assertFalse($assetCollector->hasInlineJavaScript($identifier));
            self::assertFalse($assetCollector->hasStyleSheet($identifier));
            self::assertFalse($assetCollector->hasMedia($identifier));
        }
        self::assertSame($expectedResult, $assetCollector->getJavaScripts());
        self::assertSame([], $assetCollector->getInlineStyleSheets());
        self::assertSame([], $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getStyleSheets());
        self::assertSame([], $assetCollector->getMedia());
        foreach ($files as $file) {
            [$identifier] = $file;
            $assetCollector->removeJavaScript($identifier);
        }
        self::assertSame([], $assetCollector->getJavaScripts());
        self::assertSame([], $assetCollector->getInlineStyleSheets());
        self::assertSame([], $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getStyleSheets());
        self::assertSame([], $assetCollector->getMedia());
    }

    public static function inlineDataProvider(): array
    {
        return [
            'simple data' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', [], []],
                ],
                'expectedResult' => [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            '2 times simple data' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', [], []],
                    ['identifier_2', 'bar baz foo', [], []],
                ],
                'expectedResult' => [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [],
                        'options' => [],
                    ],
                    'identifier_2' => [
                        'source' => 'bar baz foo',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            '2 times simple data with override' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', [], []],
                    ['identifier_2', 'bar baz foo', [], []],
                    ['identifier_1', 'baz foo bar', [], []],
                ],
                'expectedResult' => [
                    'identifier_1' => [
                        'source' => 'baz foo bar',
                        'attributes' => [],
                        'options' => [],
                    ],
                    'identifier_2' => [
                        'source' => 'bar baz foo',
                        'attributes' => [],
                        'options' => [],
                    ],
                ],
            ],
            'simple data with attributes' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', ['rel' => 'foo'], []],
                ],
                'expectedResult' => [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [
                            'rel' => 'foo',
                        ],
                        'options' => [],
                    ],
                ],
            ],
            'simple data with attributes override' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', ['rel' => 'foo', 'another' => 'keep on override'], []],
                    ['identifier_1', 'foo bar baz', ['rel' => 'bar'], []],
                ],
                'expectedResult' => [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [
                            'rel' => 'bar',
                            'another' => 'keep on override',
                        ],
                        'options' => [],
                    ],
                ],
            ],
            'simple data with options' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', [], ['priority' => true]],
                ],
                'expectedResult' => [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [],
                        'options' => [
                            'priority' => true,
                        ],
                    ],
                ],
            ],
            'simple data with options override' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', [], ['priority' => true, 'another' => 'keep on override']],
                    ['identifier_1', 'foo bar baz', [], ['priority' => false]],
                ],
                'expectedResult' => [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [],
                        'options' => [
                            'priority' => false,
                            'another' => 'keep on override',
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('inlineDataProvider')]
    #[Test]
    public function inlineJavaScript(array $sources, array $expectedResult): void
    {
        $assetCollector = new AssetCollector();
        foreach ($sources as $source) {
            [$identifier, $source, $attributes, $options] = $source;
            $assetCollector->addInlineJavaScript($identifier, $source, $attributes, $options);
            self::assertTrue($assetCollector->hasInlineJavaScript($identifier));
            self::assertFalse($assetCollector->hasInlineStyleSheet($identifier));
            self::assertFalse($assetCollector->hasJavaScript($identifier));
            self::assertFalse($assetCollector->hasStyleSheet($identifier));
            self::assertFalse($assetCollector->hasMedia($identifier));
        }
        self::assertSame($expectedResult, $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getInlineStyleSheets());
        self::assertSame([], $assetCollector->getJavaScripts());
        self::assertSame([], $assetCollector->getStyleSheets());
        self::assertSame([], $assetCollector->getMedia());
        foreach ($sources as $source) {
            [$identifier] = $source;
            $assetCollector->removeInlineJavaScript($identifier);
        }
        self::assertSame([], $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getInlineStyleSheets());
        self::assertSame([], $assetCollector->getJavaScripts());
        self::assertSame([], $assetCollector->getStyleSheets());
        self::assertSame([], $assetCollector->getMedia());
    }

    #[DataProvider('inlineDataProvider')]
    #[Test]
    public function inlineStyles(array $sources, array $expectedResult): void
    {
        $assetCollector = new AssetCollector();
        foreach ($sources as $source) {
            [$identifier, $source, $attributes, $options] = $source;
            $assetCollector->addInlineStyleSheet($identifier, $source, $attributes, $options);
            self::assertTrue($assetCollector->hasInlineStyleSheet($identifier));
            self::assertFalse($assetCollector->hasInlineJavaScript($identifier));
            self::assertFalse($assetCollector->hasJavaScript($identifier));
            self::assertFalse($assetCollector->hasStyleSheet($identifier));
            self::assertFalse($assetCollector->hasMedia($identifier));
        }
        self::assertSame($expectedResult, $assetCollector->getInlineStyleSheets());
        self::assertSame([], $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getJavaScripts());
        self::assertSame([], $assetCollector->getStyleSheets());
        self::assertSame([], $assetCollector->getMedia());
        foreach ($sources as $source) {
            [$identifier] = $source;
            $assetCollector->removeInlineStyleSheet($identifier);
        }
        self::assertSame([], $assetCollector->getInlineStyleSheets());
        self::assertSame([], $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getJavaScripts());
        self::assertSame([], $assetCollector->getStyleSheets());
        self::assertSame([], $assetCollector->getMedia());
    }

    public static function mediaDataProvider(): array
    {
        return [
            '1 image no additional information' => [
                'images' => [
                    ['fileadmin/foo.png', []],
                ],
                'expectedResult' => [
                    'fileadmin/foo.png' => [],
                ],
            ],
            '2 images no additional information' => [
                'images' => [
                    ['fileadmin/foo.png', []],
                    ['fileadmin/bar.png', []],
                ],
                'expectedResult' => [
                    'fileadmin/foo.png' => [],
                    'fileadmin/bar.png' => [],
                ],
            ],
            '1 image with additional information' => [
                'images' => [
                    ['fileadmin/foo.png', ['foo' => 'bar']],
                ],
                'expectedResult' => [
                    'fileadmin/foo.png' => ['foo' => 'bar'],
                ],
            ],
            '2 images with additional information' => [
                'images' => [
                    ['fileadmin/foo.png', ['foo' => 'bar']],
                    ['fileadmin/bar.png', ['foo' => 'baz']],
                ],
                'expectedResult' => [
                    'fileadmin/foo.png' => ['foo' => 'bar'],
                    'fileadmin/bar.png' => ['foo' => 'baz'],
                ],
            ],
            '2 images with additional information override' => [
                'images' => [
                    ['fileadmin/foo.png', ['foo' => 'bar']],
                    ['fileadmin/bar.png', ['foo' => 'baz']],
                    ['fileadmin/foo.png', ['foo' => 'baz']],
                ],
                'expectedResult' => [
                    'fileadmin/foo.png' => ['foo' => 'baz'],
                    'fileadmin/bar.png' => ['foo' => 'baz'],
                ],
            ],
            '2 images with additional information override keep existing' => [
                'images' => [
                    ['fileadmin/foo.png', ['foo' => 'bar', 'bar' => 'baz']],
                    ['fileadmin/bar.png', ['foo' => 'baz']],
                    ['fileadmin/foo.png', ['foo' => 'baz']],
                ],
                'expectedResult' => [
                    'fileadmin/foo.png' => ['foo' => 'baz', 'bar' => 'baz'],
                    'fileadmin/bar.png' => ['foo' => 'baz'],
                ],
            ],
        ];
    }

    #[DataProvider('mediaDataProvider')]
    #[Test]
    public function media(array $images, array $expectedResult): void
    {
        $assetCollector = new AssetCollector();
        foreach ($images as $image) {
            [$fileName, $additionalInformation] = $image;
            $assetCollector->addMedia($fileName, $additionalInformation);
            self::assertTrue($assetCollector->hasMedia($fileName));
            self::assertFalse($assetCollector->hasInlineStyleSheet($fileName));
            self::assertFalse($assetCollector->hasInlineJavaScript($fileName));
            self::assertFalse($assetCollector->hasJavaScript($fileName));
            self::assertFalse($assetCollector->hasStyleSheet($fileName));
        }
        self::assertSame($expectedResult, $assetCollector->getMedia());
        self::assertSame([], $assetCollector->getInlineStyleSheets());
        self::assertSame([], $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getJavaScripts());
        self::assertSame([], $assetCollector->getStyleSheets());
        foreach ($images as $image) {
            [$fileName] = $image;
            $assetCollector->removeMedia($fileName);
        }
        self::assertSame([], $assetCollector->getMedia());
        self::assertSame([], $assetCollector->getInlineStyleSheets());
        self::assertSame([], $assetCollector->getInlineJavaScripts());
        self::assertSame([], $assetCollector->getJavaScripts());
        self::assertSame([], $assetCollector->getStyleSheets());
    }
}
