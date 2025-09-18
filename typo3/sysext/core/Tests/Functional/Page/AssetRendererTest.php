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

namespace TYPO3\CMS\Core\Tests\Functional\Page;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\AssetRenderer;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\DummyFileCreationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AssetRendererTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'BE' => [
            'versionNumberInFilename' => false,
        ],
    ];

    private DummyFileCreationService $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = new DummyFileCreationService($this->get(StorageRepository::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->file->cleanupCreatedFiles();
    }

    public static function filesDataProvider(): array
    {
        return [
            '1 file from fileadmin' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', [], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link href="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709"></script>',
                    'js_prio' => '',
                ],
            ],
            '1 file from extension' => [
                'files' => [
                    ['file1', 'EXT:core/Resources/Public/foo.ext', [], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link href="/typo3/sysext/core/Resources/Public/foo.ext" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="/typo3/sysext/core/Resources/Public/foo.ext"></script>',
                    'js_prio' => '',
                ],
            ],
            '1 file from external source' => [
                'files' => [
                    ['file1', 'https://typo3.org/foo.ext', [], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link href="https://typo3.org/foo.ext" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="https://typo3.org/foo.ext"></script>',
                    'js_prio' => '',
                ],
            ],
            '1 file from external source with one parameter' => [
                'files' => [
                    ['file1', 'https://typo3.org/foo.ext?foo=bar', [], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link href="https://typo3.org/foo.ext?foo=bar" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="https://typo3.org/foo.ext?foo=bar"></script>',
                    'js_prio' => '',
                ],
            ],
            '1 file from external source with two parameters' => [
                'files' => [
                    ['file1', 'https://typo3.org/foo.ext?foo=bar&bar=baz', [], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link href="https://typo3.org/foo.ext?foo=bar&amp;bar=baz" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="https://typo3.org/foo.ext?foo=bar&amp;bar=baz"></script>',
                    'js_prio' => '',
                ],
            ],
            '2 files' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', [], []],
                    ['file2', 'EXT:core/Resources/Public/foo.ext', [], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link href="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709" rel="stylesheet" >' . PHP_EOL . '<link href="/typo3/sysext/core/Resources/Public/foo.ext" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709"></script>' . PHP_EOL . '<script src="/typo3/sysext/core/Resources/Public/foo.ext"></script>',
                    'js_prio' => '',
                ],
            ],
            '2 files with override' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', [], []],
                    ['file2', 'EXT:core/Resources/Public/foo.ext', [], []],
                    ['file1', 'EXT:core/Resources/Public/bar.ext', [], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link href="/typo3/sysext/core/Resources/Public/bar.ext" rel="stylesheet" >' . PHP_EOL . '<link href="/typo3/sysext/core/Resources/Public/foo.ext" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="/typo3/sysext/core/Resources/Public/bar.ext"></script>' . PHP_EOL . '<script src="/typo3/sysext/core/Resources/Public/foo.ext"></script>',
                    'js_prio' => '',
                ],
            ],
            '1 file with attributes' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', ['rel' => 'foo'], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link rel="foo" href="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script rel="foo" src="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709"></script>',
                    'js_prio' => '',
                ],
            ],
            '1 file with controlled type' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', ['type' => 'module'], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link type="module" href="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script type="module" src="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709"></script>',
                    'js_prio' => '',
                ],
            ],
            '1 file with attributes override' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', ['rel' => 'foo', 'another' => 'keep on override'], []],
                    ['file1', 'fileadmin/foo.ext', ['rel' => 'bar'], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link rel="bar" another="keep on override" href="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script rel="bar" another="keep on override" src="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709"></script>',
                    'js_prio' => '',
                ],
            ],
            '1 file with options' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', [], ['priority' => true]],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '',
                    'css_prio' => '<link href="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709" rel="stylesheet" >',
                    'js_no_prio' => '',
                    'js_prio' => '<script src="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709"></script>',
                ],
            ],
            '1 file with options override' => [
                'files' => [
                    ['file1', 'fileadmin/foo.ext', [], ['priority' => true, 'another' => 'keep on override']],
                    ['file1', 'fileadmin/foo.ext', [], ['priority' => false]],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link href="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="/fileadmin/foo.ext?da39a3ee5e6b4b0d3255bfef95601890afd80709"></script>',
                    'js_prio' => '',
                ],
            ],
            '1 file with external option' => [
                'files' => [
                    ['file1', 'EXT:core/Resources/Public/foo.ext', [], ['external' => true]],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link href="EXT:core/Resources/Public/foo.ext" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="EXT:core/Resources/Public/foo.ext"></script>',
                    'js_prio' => '',
                ],
            ],
            '1 temp file with external option' => [
                'files' => [
                    ['file1', '/typo3temp/bla/foo.ext', [], ['external' => true]],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<link href="/typo3temp/bla/foo.ext" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="/typo3temp/bla/foo.ext"></script>',
                    'js_prio' => '',
                ],
            ],
        ];
    }

    #[DataProvider('filesDataProvider')]
    #[Test]
    public function styleSheets(array $files, array $expectedMarkup): void
    {
        $this->file->ensureFilesExistInStorage('/foo.ext');
        $assetCollector = $this->get(AssetCollector::class);
        $assetRenderer = $this->get(AssetRenderer::class);
        foreach ($files as $file) {
            [$identifier, $source, $attributes, $options] = $file;
            $assetCollector->addStyleSheet($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedMarkup['css_no_prio'], $assetRenderer->renderStyleSheets());
        self::assertSame($expectedMarkup['css_prio'], $assetRenderer->renderStyleSheets(true));
    }

    #[DataProvider('filesDataProvider')]
    #[Test]
    public function javaScript(array $files, array $expectedMarkup): void
    {
        $this->file->ensureFilesExistInStorage('/foo.ext');
        $assetCollector = $this->get(AssetCollector::class);
        $assetRenderer = $this->get(AssetRenderer::class);
        foreach ($files as $file) {
            [$identifier, $source, $attributes, $options] = $file;
            $assetCollector->addJavaScript($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedMarkup['js_no_prio'], $assetRenderer->renderJavaScript());
        self::assertSame($expectedMarkup['js_prio'], $assetRenderer->renderJavaScript(true));
    }

    public static function inlineDataProvider(): array
    {
        return [
            'simple data' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', [], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<style>foo bar baz</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script>foo bar baz</script>',
                    'js_prio' => '',
                ],
            ],
            '2 times simple data' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', [], []],
                    ['identifier_2', 'bar baz foo', [], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<style>foo bar baz</style>' . PHP_EOL . '<style>bar baz foo</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script>foo bar baz</script>' . PHP_EOL . '<script>bar baz foo</script>',
                    'js_prio' => '',
                ],
            ],
            '2 times simple data with override' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', [], []],
                    ['identifier_2', 'bar baz foo', [], []],
                    ['identifier_1', 'baz foo bar', [], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<style>baz foo bar</style>' . PHP_EOL . '<style>bar baz foo</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script>baz foo bar</script>' . PHP_EOL . '<script>bar baz foo</script>',
                    'js_prio' => '',
                ],
            ],
            'simple data with attributes' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', ['rel' => 'foo'], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<style rel="foo">foo bar baz</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script rel="foo">foo bar baz</script>',
                    'js_prio' => '',
                ],
            ],
            'simple data with attributes override' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', ['rel' => 'foo', 'another' => 'keep on override'], []],
                    ['identifier_1', 'foo bar baz', ['rel' => 'bar'], []],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<style rel="bar" another="keep on override">foo bar baz</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script rel="bar" another="keep on override">foo bar baz</script>',
                    'js_prio' => '',
                ],
            ],
            'simple data with options' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', [], ['priority' => true]],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '',
                    'css_prio' => '<style>foo bar baz</style>',
                    'js_no_prio' => '',
                    'js_prio' => '<script>foo bar baz</script>',
                ],
            ],
            'simple data with options override' => [
                'sources' => [
                    ['identifier_1', 'foo bar baz', [], ['priority' => true, 'another' => 'keep on override']],
                    ['identifier_1', 'foo bar baz', [], ['priority' => false]],
                ],
                'expectedMarkup' => [
                    'css_no_prio' => '<style>foo bar baz</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script>foo bar baz</script>',
                    'js_prio' => '',
                ],
            ],
        ];
    }

    #[DataProvider('inlineDataProvider')]
    #[Test]
    public function inlineJavaScript(array $sources, array $expectedMarkup): void
    {
        $assetCollector = $this->get(AssetCollector::class);
        $assetRenderer = $this->get(AssetRenderer::class);
        foreach ($sources as $source) {
            [$identifier, $source, $attributes, $options] = $source;
            $assetCollector->addInlineJavaScript($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedMarkup['js_no_prio'], $assetRenderer->renderInlineJavaScript());
        self::assertSame($expectedMarkup['js_prio'], $assetRenderer->renderInlineJavaScript(true));
    }

    #[DataProvider('inlineDataProvider')]
    #[Test]
    public function inlineStyleSheets(array $sources, array $expectedMarkup): void
    {
        $assetCollector = $this->get(AssetCollector::class);
        $assetRenderer = $this->get(AssetRenderer::class);
        foreach ($sources as $source) {
            [$identifier, $source, $attributes, $options] = $source;
            $assetCollector->addInlineStyleSheet($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedMarkup['css_no_prio'], $assetRenderer->renderInlineStyleSheets());
        self::assertSame($expectedMarkup['css_prio'], $assetRenderer->renderInlineStyleSheets(true));
    }
}
