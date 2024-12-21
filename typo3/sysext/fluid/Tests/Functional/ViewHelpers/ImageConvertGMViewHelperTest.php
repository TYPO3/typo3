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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ImageConvertGMViewHelperTest extends FunctionalTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Imaging' => 'fileadmin/',
    ];

    protected array $configurationToUseInTestInstance = [
        'GFX' => [
            'processor' => 'GraphicsMagick',
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ViewHelpers/ImageViewHelper/fal_image.csv');
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/fileadmin/_processed_', true);
        parent::tearDown();
    }

    /**
     * Render string fluid template.
     */
    private function renderTemplate(string $template): string
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        return (new TemplateView($context))->render();
    }

    public static function filesConvertedToDefaultFileFormatUsingGraphicsMagickDataProvider(): \Generator
    {
        // web-format
        yield 'ai to default' => [
            'template' => '<f:image src="fileadmin/file.ai" width="80" alt="ai-to-default" />',
            'contentMatchRegExp' => '<img alt="ai-to-default" src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="34" />',
            'message' => 'File extension is "png" for "ai" (processed)',
        ];
        yield 'avif to default' => [
            'template' => '<f:image src="fileadmin/file.avif" width="80" alt="avif-to-default" />',
            'contentMatchRegExp' => '<img alt="avif-to-default" src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="60" />',
            'message' => 'File extension is "png" for "avif" (processed)',
        ];
        yield 'bmp to default' => [
            'template' => '<f:image src="fileadmin/file.bmp" width="80" alt="bmp-to-default" />',
            'contentMatchRegExp' => '<img alt="bmp-to-default" src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="34" />',
            'message' => 'File extension is "png" for "bmp" (processed)',
        ];
        yield 'gif to default' => [
            'template' => '<f:image src="fileadmin/file.gif" width="80" alt="gif-to-default" />',
            'contentMatchRegExp' => '<img alt="gif-to-default" src="fileadmin/_processed_/.*/.*/csm_file_.*\.gif" width="80" height="34" />',
            'message' => 'File extension is "gif" for "gif" (processed)',
        ];
        yield 'jpg to default' => [
            'template' => '<f:image src="fileadmin/file.jpg" width="80" alt="jpg-to-default" />',
            'contentMatchRegExp' => '<img alt="jpg-to-default" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" />',
            'message' => 'File extension is "jpg" for "jpg" (processed)',
        ];
        yield 'pdf to default' => [
            'template' => '<f:image src="fileadmin/file.pdf" width="80" alt="pdf-to-default" />',
            'contentMatchRegExp' => '<img alt="pdf-to-default" src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="34" />',
            'message' => 'File extension is "png" for "pdf" (processed)',
        ];
        yield 'png to default' => [
            'template' => '<f:image src="fileadmin/file.png" width="80" alt="png-to-default" />',
            'contentMatchRegExp' => '<img alt="png-to-default" src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="34" />',
            'message' => 'File extension is "png" for "png" (processed)',
        ];
        yield 'tif to default' => [
            'template' => '<f:image src="fileadmin/file.tif" width="80" alt="tif-to-default" />',
            'contentMatchRegExp' => '<img alt="tif-to-default" src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="34" />',
            'message' => 'File extension is "png" for "tif" (processed)',
        ];
        yield 'webp to default' => [
            'template' => '<f:image src="fileadmin/file.webp" width="80" alt="webp-to-default" />',
            'contentMatchRegExp' => '<img alt="webp-to-default" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" />',
            'message' => 'File extension is "webp" for "webp" (processed)',
        ];
        // non-web format
        yield 'eps to default' => [
            'template' => '<f:image src="fileadmin/file.eps" width="80" alt="eps-to-default" />',
            'contentMatchRegExp' => '<img alt="eps-to-default" src="fileadmin/file.eps" width="" height="" />',
            'message' => 'File extension is "eps" for "eps" (unprocessed/original/non-web-format)',
        ];
        yield 'fax to default' => [
            'template' => '<f:image src="fileadmin/file.fax" width="80" alt="fax-to-default" />',
            'contentMatchRegExp' => '<img alt="fax-to-default" src="fileadmin/file.fax" width="" height="" />',
            'message' => 'File extension is "fax" for "fax" (unprocessed/original/non-web-format)',
        ];
        yield 'ps to default' => [
            'template' => '<f:image src="fileadmin/file.ps" width="80" alt="ps-to-default" />',
            'contentMatchRegExp' => '<img alt="ps-to-default" src="fileadmin/file.ps" width="" height="" />',
            'message' => 'File extension is "ps" for "ps" (unprocessed/original/non-web-format)',
        ];
        // pass-through format
        yield 'svg to default' => [
            'template' => '<f:image src="fileadmin/file.svg" width="80" alt="svg-to-default" />',
            'contentMatchRegExp' => '<img alt="svg-to-default" src="fileadmin/file.svg" width="80" height="34" />',
            'message' => 'File extension is "ps" for "ps" (unprocessed/original/passthrough)',
        ];
        // invalid
        yield 'avi to default' => [
            'template' => '<f:image src="fileadmin/invalid-file.avi" width="80" alt="avi-to-default" />',
            'contentMatchRegExp' => '<img alt="avi-to-default" src="fileadmin/invalid-file.avi" width="" height="" />',
            'message' => 'File extension is "avi" for "avi" (unprocessed/original/invalid)',
        ];
        yield 'exe to default' => [
            'template' => '<f:image src="fileadmin/invalid-file.exe" width="80" alt="exe-to-default" />',
            'contentMatchRegExp' => '<img alt="exe-to-default" src="fileadmin/invalid-file.exe" width="" height="" />',
            'message' => 'File extension is "exe" for "exe" (unprocessed/original/invalid)',
        ];
        yield 'zip to default' => [
            'template' => '<f:image src="fileadmin/invalid-file.zip" width="80" alt="zip-to-default" />',
            'contentMatchRegExp' => '<img alt="zip-to-default" src="fileadmin/invalid-file.zip" width="" height="" />',
            'message' => 'File extension is "zip" for "zip" (unprocessed/original/invalid)',
        ];
    }

    #[DataProvider('filesConvertedToDefaultFileFormatUsingGraphicsMagickDataProvider')]
    #[Test]
    public function filesConvertedToDefaultFileFormatUsingGraphicsMagick(string $template, string $contentMatchRegExp, string $message): void
    {
        self::assertSame('GraphicsMagick', $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor']);
        self::assertMatchesRegularExpression(sprintf('@\s*%s\s*@', $contentMatchRegExp), $this->renderTemplate($template), $message);
    }

    public static function filesConvertedToJpegFileFormatUsingGraphicsMagickDataProvider(): \Generator
    {
        // web format
        yield 'ai to jpg' => [
            'template' => '<f:image src="fileadmin/file.ai" fileExtension="jpg" width="80" alt="ai-to-jpg" />',
            'contentMatchRegExp' => '<img alt="ai-to-jpg" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" />',
            'message' => 'File extension is "jpg" for "ai" (processed/forced)',
        ];
        yield 'avif to jpg' => [
            'template' => '<f:image src="fileadmin/file.avif" fileExtension="jpg" width="80" alt="avif-to-jpg" />',
            'contentMatchRegExp' => '<img alt="avif-to-jpg" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="60" />',
            'message' => 'File extension is "jpg" for "avif" (processed/forced)',
        ];
        yield 'bmp to jpg' => [
            'template' => '<f:image src="fileadmin/file.bmp" fileExtension="jpg" width="80" alt="bmp-to-jpg" />',
            'contentMatchRegExp' => '<img alt="bmp-to-jpg" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" />',
            'message' => 'File extension is "jpg" for "bmp" (processed/forced)',
        ];
        yield 'gif to jpg' => [
            'template' => '<f:image src="fileadmin/file.gif" fileExtension="jpg" width="80" alt="gif-to-jpg" />',
            'contentMatchRegExp' => '<img alt="gif-to-jpg" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" />',
            'message' => 'File extension is "jpg" for "gif" (processed/forced)',
        ];
        yield 'jpg to jpg' => [
            'template' => '<f:image src="fileadmin/file.jpg" fileExtension="jpg" width="80" alt="jpg-to-jpg" />',
            'contentMatchRegExp' => '<img alt="jpg-to-jpg" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" />',
            'message' => 'File extension is "jpg" for "gif" (processed/forced)',
        ];
        yield 'pdf to jpg' => [
            'template' => '<f:image src="fileadmin/file.pdf" fileExtension="jpg" width="80" alt="pdf-to-jpg" />',
            'contentMatchRegExp' => '<img alt="pdf-to-jpg" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" />',
            'message' => 'File extension is "jpg" for "pdf" (processed/forced)',
        ];
        yield 'png to jpg' => [
            'template' => '<f:image src="fileadmin/file.png" fileExtension="jpg" width="80" alt="png-to-jpg" />',
            'contentMatchRegExp' => '<img alt="png-to-jpg" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" />',
            'message' => 'File extension is "jpg" for "png" (processed/forced)',
        ];
        yield 'tif to jpg' => [
            'template' => '<f:image src="fileadmin/file.tif" fileExtension="jpg" width="80" alt="tif-to-jpg" />',
            'contentMatchRegExp' => '<img alt="tif-to-jpg" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" />',
            'message' => 'File extension is "jpg" for "tif" (processed/forced)',
        ];
        yield 'webp to jpg' => [
            'template' => '<f:image src="fileadmin/file.webp" fileExtension="jpg" width="80" alt="webp-to-jpg" />',
            'contentMatchRegExp' => '<img alt="webp-to-jpg" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" />',
            'message' => 'File extension is "jpg" for "webp" (processed/forced)',
        ];
        // non-web-format
        yield 'eps to jpg' => [
            'template' => '<f:image src="fileadmin/file.eps" fileExtension="jpg" width="80" alt="eps-to-jpg" />',
            'contentMatchRegExp' => '<img alt="eps-to-jpg" src="fileadmin/file.eps" width="" height="" />',
            'message' => 'File extension is "eps" for "eps" (unprocessed/non-web format/not-enforceable)',
        ];
        yield 'fax to jpg' => [
            'template' => '<f:image src="fileadmin/file.fax" fileExtension="jpg" width="80" alt="fax-to-jpg" />',
            'contentMatchRegExp' => '<img alt="fax-to-jpg" src="fileadmin/file.fax" width="" height="" />',
            'message' => 'File extension is "fax" for "fax" (unprocessed/non-web format/not-enforceable)',
        ];
        yield 'ps to jpg' => [
            'template' => '<f:image src="fileadmin/file.ps" fileExtension="jpg" width="80" alt="ps-to-jpg" />',
            'contentMatchRegExp' => '<img alt="ps-to-jpg" src="fileadmin/file.ps" width="" height="" />',
            'message' => 'File extension is "ps" for "ps" (unprocessed/non-web format/not-enforceable)',
        ];
        // pass-through-format
        yield 'svg to jpg' => [
            'template' => '<f:image src="fileadmin/file.svg" fileExtension="jpg" width="80" alt="svg-to-jpg" />',
            'contentMatchRegExp' => '<img alt="svg-to-jpg" src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" />',
            'message' => 'File extension is "jpg" for "svg" (processed/forced)',
        ];
        // invalid
        yield 'avi to jpg' => [
            'template' => '<f:image src="fileadmin/invalid-file.avi" fileExtension="jpg" width="80" alt="avi-to-jpg" />',
            'contentMatchRegExp' => '<img alt="avi-to-jpg" src="fileadmin/invalid-file.avi" width="" height="" />',
            'message' => 'File extension is "avi" for "avi" (unprocessed/invalid/not-enforceable)',
        ];
        yield 'exe to jpg' => [
            'template' => '<f:image src="fileadmin/invalid-file.exe" fileExtension="jpg" width="80" alt="exe-to-jpg" />',
            'contentMatchRegExp' => '<img alt="exe-to-jpg" src="fileadmin/invalid-file.exe" width="" height="" />',
            'message' => 'File extension is "avi" for "avi" (unprocessed/invalid/not-enforceable)',
        ];
        yield 'zip to jpg' => [
            'template' => '<f:image src="fileadmin/invalid-file.zip" fileExtension="jpg" width="80" alt="zip-to-jpg" />',
            'contentMatchRegExp' => '<img alt="zip-to-jpg" src="fileadmin/invalid-file.zip" width="" height="" />',
            'message' => 'File extension is "avi" for "avi" (unprocessed/invalid/not-enforceable)',
        ];
    }

    #[DataProvider('filesConvertedToJpegFileFormatUsingGraphicsMagickDataProvider')]
    #[Test]
    public function filesConvertedToJpegFileFormatUsingGraphicsMagick(string $template, string $contentMatchRegExp, string $message): void
    {
        self::assertSame('GraphicsMagick', $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor']);
        self::assertMatchesRegularExpression(sprintf('@\s*%s\s*@', $contentMatchRegExp), $this->renderTemplate($template), $message);
    }

    public static function filesConvertedToWebpFileFormatUsingGraphicsMagickDataProvider(): \Generator
    {
        // web-format
        yield 'ai to webp' => [
            'template' => '<f:image src="fileadmin/file.ai" fileExtension="webp" width="80" alt="ai-to-webp" />',
            'contentMatchRegExp' => '<img alt="ai-to-webp" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" />',
            'message' => 'File extension is "webp" for "ai" (processed/forced)',
        ];
        yield 'avif to webp' => [
            'template' => '<f:image src="fileadmin/file.avif" fileExtension="webp" width="80" alt="avif-to-webp" />',
            'contentMatchRegExp' => '<img alt="avif-to-webp" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="60" />',
            'message' => 'File extension is "webp" for "avif" (processed/forced)',
        ];
        yield 'bmp to webp' => [
            'template' => '<f:image src="fileadmin/file.bmp" fileExtension="webp" width="80" alt="bmp-to-webp" />',
            'contentMatchRegExp' => '<img alt="bmp-to-webp" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" />',
            'message' => 'File extension is "webp" for "bmp" (processed/forced)',
        ];
        yield 'gif to webp' => [
            'template' => '<f:image src="fileadmin/file.gif" fileExtension="webp" width="80" alt="gif-to-webp" />',
            'contentMatchRegExp' => '<img alt="gif-to-webp" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" />',
            'message' => 'File extension is "webp" for "gif" (processed/forced)',
        ];
        yield 'jpg to webp' => [
            'template' => '<f:image src="fileadmin/file.jpg" fileExtension="webp" width="80" alt="jpg-to-webp" />',
            'contentMatchRegExp' => '<img alt="jpg-to-webp" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" />',
            'message' => 'File extension is "webp" for "jpg" (processed/forced)',
        ];
        yield 'pdf to webp' => [
            'template' => '<f:image src="fileadmin/file.pdf" fileExtension="webp" width="80" alt="pdf-to-webp" />',
            'contentMatchRegExp' => '<img alt="pdf-to-webp" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" />',
            'message' => 'File extension is "webp" for "pdf" (processed/forced)',
        ];
        yield 'png to webp' => [
            'template' => '<f:image src="fileadmin/file.png" fileExtension="webp" width="80" alt="png-to-webp" />',
            'contentMatchRegExp' => '<img alt="png-to-webp" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" />',
            'message' => 'File extension is "webp" for "png" (processed/forced)',
        ];
        yield 'tif to webp' => [
            'template' => '<f:image src="fileadmin/file.tif" fileExtension="webp" width="80" alt="tif-to-webp" />',
            'contentMatchRegExp' => '<img alt="tif-to-webp" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" />',
            'message' => 'File extension is "webp" for "tif" (processed/forced)',
        ];
        yield 'webp to webp' => [
            'template' => '<f:image src="fileadmin/file.webp" fileExtension="webp" width="80" alt="webp-to-webp" />',
            'contentMatchRegExp' => '<img alt="webp-to-webp" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" />',
            'message' => 'File extension is "webp" for "tif" (processed/forced)',
        ];
        // non-web-format
        yield 'eps to webp' => [
            'template' => '<f:image src="fileadmin/file.eps" fileExtension="webp" width="80" alt="eps-to-webp" />',
            'contentMatchRegExp' => '<img alt="eps-to-webp" src="fileadmin/file.eps" width="" height="" />',
            'message' => 'File extension is "eps" for "eps" (not-processed/not-forced/non-web-format)',
        ];
        yield 'fax to webp' => [
            'template' => '<f:image src="fileadmin/file.fax" fileExtension="webp" width="80" alt="fax-to-webp" />',
            'contentMatchRegExp' => '<img alt="fax-to-webp" src="fileadmin/file.fax" width="" height="" />',
            'message' => 'File extension is "eps" for "eps" (not-processed/not-forced/non-web-format)',
        ];
        yield 'ps to webp' => [
            'template' => '<f:image src="fileadmin/file.ps" fileExtension="webp" width="80" alt="ps-to-webp" />',
            'contentMatchRegExp' => '<img alt="ps-to-webp" src="fileadmin/file.ps" width="" height="" />',
            'message' => 'File extension is "eps" for "eps" (not-processed/not-forced/non-web-format)',
        ];
        // pass-through-format
        yield 'svg to webp' => [
            'template' => '<f:image src="fileadmin/file.svg" fileExtension="webp" width="80" alt="svg-to-webp" />',
            'contentMatchRegExp' => '<img alt="svg-to-webp" src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" />',
            'message' => 'File extension is "webp" for "svg" (processed/forced)',
        ];
        // invalid
        yield 'avi to webp' => [
            'template' => '<f:image src="fileadmin/invalid-file.avi" fileExtension="webp" width="80" alt="avi-to-webp" />',
            'contentMatchRegExp' => '<img alt="avi-to-webp" src="fileadmin/invalid-file.avi" width="" height="" />',
            'message' => 'File extension is "avi" for "avi" (not-processed/not-forced/invalid)',
        ];
        yield 'exe to webp' => [
            'template' => '<f:image src="fileadmin/invalid-file.exe" fileExtension="webp" width="80" alt="exe-to-webp" />',
            'contentMatchRegExp' => '<img alt="exe-to-webp" src="fileadmin/invalid-file.exe" width="" height="" />',
            'message' => 'File extension is "exe" for "exe" (not-processed/not-forced/invalid)',
        ];
        yield 'zip to webp' => [
            'template' => '<f:image src="fileadmin/invalid-file.zip" fileExtension="webp" width="80" alt="zip-to-webp" />',
            'contentMatchRegExp' => '<img alt="zip-to-webp" src="fileadmin/invalid-file.zip" width="" height="" />',
            'message' => 'File extension is "zip" for "zip" (not-processed/not-forced/invalid)',
        ];
    }

    #[DataProvider('filesConvertedToWebpFileFormatUsingGraphicsMagickDataProvider')]
    #[Test]
    public function filesConvertedToWebpFileFormatUsingGraphicsMagick(string $template, string $contentMatchRegExp, string $message): void
    {
        self::assertSame('GraphicsMagick', $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor']);
        self::assertMatchesRegularExpression(sprintf('@\s*%s\s*@', $contentMatchRegExp), $this->renderTemplate($template), $message);
    }

    // Conversion to JPG/PNG is performed because of fallback format, as GraphicsMagick does not support
    // writing AVIF (yet)
    public static function filesConvertedToAvifFileFormatUsingGraphicsMagickDataProvider(): \Generator
    {
        // web-format
        yield 'ai to avif' => [
            'template' => '<f:image src="fileadmin/file.ai" fileExtension="avif" width="80" alt="ai-to-avif" />',
            'contentMatchRegExp' => '<img alt="ai-to-avif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="34" />',
            'message' => 'File extension is "avif.jpg" for "ai" (processed/forced)',
        ];
        yield 'avif to avif' => [
            'template' => '<f:image src="fileadmin/file.avif" fileExtension="avif" width="80" alt="avif-to-avif" />',
            'contentMatchRegExp' => '<img alt="avif-to-avif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="60" />',
            'message' => 'File extension is "avif.jpg" for "avif" (processed/forced)',
        ];
        yield 'bmp to avif' => [
            'template' => '<f:image src="fileadmin/file.bmp" fileExtension="avif" width="80" alt="bmp-to-avif" />',
            'contentMatchRegExp' => '<img alt="bmp-to-avif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="34" />',
            'message' => 'File extension is "avif.jpg" for "bmp" (processed/forced)',
        ];
        yield 'gif to avif' => [
            'template' => '<f:image src="fileadmin/file.gif" fileExtension="avif" width="80" alt="gif-to-avif" />',
            'contentMatchRegExp' => '<img alt="gif-to-avif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.gif" width="80" height="34" />',
            'message' => 'File extension is "avif.jpg" for "gif" (processed/forced)',
        ];
        yield 'jpg to avif' => [
            'template' => '<f:image src="fileadmin/file.jpg" fileExtension="avif" width="80" alt="jpg-to-avif" />',
            'contentMatchRegExp' => '<img alt="jpg-to-avif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.jpg" width="80" height="34" />',
            'message' => 'File extension is "avif.jpg" for "jpg" (processed/forced)',
        ];
        yield 'pdf to avif' => [
            'template' => '<f:image src="fileadmin/file.pdf" fileExtension="avif" width="80" alt="pdf-to-avif" />',
            'contentMatchRegExp' => '<img alt="pdf-to-avif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="34" />',
            'message' => 'File extension is "avif.jpg" for "pdf" (processed/forced)',
        ];
        yield 'png to avif' => [
            'template' => '<f:image src="fileadmin/file.png" fileExtension="avif" width="80" alt="png-to-avif" />',
            'contentMatchRegExp' => '<img alt="png-to-avif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="34" />',
            'message' => 'File extension is "avif.jpg" for "png" (processed/forced)',
        ];
        yield 'tif to avif' => [
            'template' => '<f:image src="fileadmin/file.tif" fileExtension="avif" width="80" alt="tif-to-avif" />',
            'contentMatchRegExp' => '<img alt="tif-to-avif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="34" />',
            'message' => 'File extension is "avif.jpg" for "tif" (processed/forced)',
        ];
        yield 'webp to avif' => [
            'template' => '<f:image src="fileadmin/file.webp" fileExtension="avif" width="80" alt="webp-to-avif" />',
            'contentMatchRegExp' => '<img alt="webp-to-avif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.webp" width="80" height="34" />',
            'message' => 'File extension is "avif.jpg" for "webp" (processed/forced)',
        ];
        // non-web-format
        yield 'eps to avif' => [
            'template' => '<f:image src="fileadmin/file.eps" fileExtension="avif" width="80" alt="eps-to-avif" />',
            'contentMatchRegExp' => '<img alt="eps-to-avif" src="fileadmin/file.eps" width="" height="" />',
            'message' => 'File extension is "eps" for "eps" (not-processed/not-forced/non-web-format)',
        ];
        yield 'fax to avif' => [
            'template' => '<f:image src="fileadmin/file.fax" fileExtension="avif" width="80" alt="fax-to-avif" />',
            'contentMatchRegExp' => '<img alt="fax-to-avif" src="fileadmin/file.fax" width="" height="" />',
            'message' => 'File extension is "fax" for "fax" (not-processed/not-forced/non-web-format)',
        ];
        yield 'ps to avif' => [
            'template' => '<f:image src="fileadmin/file.ps" fileExtension="avif" width="80" alt="ps-to-avif" />',
            'contentMatchRegExp' => '<img alt="ps-to-avif" src="fileadmin/file.ps" width="" height="" />',
            'message' => 'File extension is "fax" for "fax" (not-processed/not-forced/non-web-format)',
        ];
        // pass-through-format (GM does not support writing AVIF)
        yield 'svg to avif' => [
            'template' => '<f:image src="fileadmin/file.svg" fileExtension="avif" width="80" alt="svg-to-avif" />',
            'contentMatchRegExp' => '<img alt="svg-to-avif" src="fileadmin/file.svg" width="481" height="203" />',
            'message' => 'File extension is "avif.jpg" for "svg" (processed/forced)',
        ];
        // invalid
        yield 'avi to avif' => [
            'template' => '<f:image src="fileadmin/invalid-file.avi" fileExtension="avif" width="80" alt="avi-to-avif" />',
            'contentMatchRegExp' => '<img alt="avi-to-avif" src="fileadmin/invalid-file.avi" width="" height="" />',
            'message' => 'File extension is "avi" for "avi" (not-processed/not-forced/invalid)',
        ];
        yield 'exe to avif' => [
            'template' => '<f:image src="fileadmin/invalid-file.exe" fileExtension="avif" width="80" alt="exe-to-avif" />',
            'contentMatchRegExp' => '<img alt="exe-to-avif" src="fileadmin/invalid-file.exe" width="" height="" />',
            'message' => 'File extension is "exe" for "exe" (not-processed/not-forced/invalid)',
        ];
        yield 'zip to avif' => [
            'template' => '<f:image src="fileadmin/invalid-file.zip" fileExtension="avif" width="80" alt="zip-to-avif" />',
            'contentMatchRegExp' => '<img alt="zip-to-avif" src="fileadmin/invalid-file.zip" width="" height="" />',
            'message' => 'File extension is "zip" for "zip" (not-processed/not-forced/invalid)',
        ];
    }

    #[DataProvider('filesConvertedToAvifFileFormatUsingGraphicsMagickDataProvider')]
    #[Test]
    public function filesConvertedToAvifFileFormatUsingGraphicsMagick(string $template, string $contentMatchRegExp, string $message): void
    {
        self::assertSame('GraphicsMagick', $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor']);
        // Note: GraphicsMagick can NOT write AVIF currently. Processed files will end up in `.avif.jpg`
        // fallback extension to emphasize that conversion was done with a non-expected format.
        self::assertMatchesRegularExpression(sprintf('@\s*%s\s*@', $contentMatchRegExp), $this->renderTemplate($template), $message);
    }

    public static function filesConvertedToTifFileFormatUsingGraphicsMagickDataProvider(): \Generator
    {
        // web-format
        yield 'ai to tif' => [
            'template' => '<f:image src="fileadmin/file.ai" fileExtension="tif" width="80" alt="ai-to-tif" />',
            'contentMatchRegExp' => '<img alt="ai-to-tif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" />',
            'message' => 'File extension is "tif" for "ai" (processed/forced)',
        ];
        yield 'avif to tif' => [
            'template' => '<f:image src="fileadmin/file.avif" fileExtension="tif" width="80" alt="avif-to-tif" />',
            'contentMatchRegExp' => '<img alt="avif-to-tif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="60" />',
            'message' => 'File extension is "tif" for "avif" (processed/forced)',
        ];
        yield 'bmp to tif' => [
            'template' => '<f:image src="fileadmin/file.bmp" fileExtension="tif" width="80" alt="bmp-to-tif" />',
            'contentMatchRegExp' => '<img alt="bmp-to-tif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" />',
            'message' => 'File extension is "tif" for "bmp" (processed/forced)',
        ];
        yield 'gif to tif' => [
            'template' => '<f:image src="fileadmin/file.gif" fileExtension="tif" width="80" alt="gif-to-tif" />',
            'contentMatchRegExp' => '<img alt="gif-to-tif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" />',
            'message' => 'File extension is "tif" for "gif" (processed/forced)',
        ];
        yield 'jpg to tif' => [
            'template' => '<f:image src="fileadmin/file.jpg" fileExtension="tif" width="80" alt="jpg-to-tif" />',
            'contentMatchRegExp' => '<img alt="jpg-to-tif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" />',
            'message' => 'File extension is "tif" for "jpg" (processed/forced)',
        ];
        yield 'pdf to tif' => [
            'template' => '<f:image src="fileadmin/file.pdf" fileExtension="tif" width="80" alt="pdf-to-tif" />',
            'contentMatchRegExp' => '<img alt="pdf-to-tif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" />',
            'message' => 'File extension is "tif" for "pdf" (processed/forced)',
        ];
        yield 'png to tif' => [
            'template' => '<f:image src="fileadmin/file.png" fileExtension="tif" width="80" alt="png-to-tif" />',
            'contentMatchRegExp' => '<img alt="png-to-tif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" />',
            'message' => 'File extension is "tif" for "png" (processed/forced)',
        ];
        yield 'tif to tif' => [
            'template' => '<f:image src="fileadmin/file.tif" fileExtension="tif" width="80" alt="tif-to-tif" />',
            'contentMatchRegExp' => '<img alt="tif-to-tif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" />',
            'message' => 'File extension is "tif" for "tif" (processed/forced)',
        ];
        yield 'webp to tif' => [
            'template' => '<f:image src="fileadmin/file.webp" fileExtension="tif" width="80" alt="webp-to-tif" />',
            'contentMatchRegExp' => '<img alt="webp-to-tif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" />',
            'message' => 'File extension is "tif" for "webp" (processed/forced)',
        ];
        // non-web-format
        yield 'eps to tif' => [
            'template' => '<f:image src="fileadmin/file.eps" fileExtension="tif" width="80" alt="eps-to-tif" />',
            'contentMatchRegExp' => '<img alt="eps-to-tif" src="fileadmin/file.eps" width="" height="" />',
            'message' => 'File extension is "eps" for "eps" (not-processed/not-forced/non-web-format)',
        ];
        yield 'fax to tif' => [
            'template' => '<f:image src="fileadmin/file.fax" fileExtension="tif" width="80" alt="fax-to-tif" />',
            'contentMatchRegExp' => '<img alt="fax-to-tif" src="fileadmin/file.fax" width="" height="" />',
            'message' => 'File extension is "fax" for "fax" (not-processed/not-forced/non-web-format)',
        ];
        yield 'ps to tif' => [
            'template' => '<f:image src="fileadmin/file.ps" fileExtension="tif" width="80" alt="ps-to-tif" />',
            'contentMatchRegExp' => '<img alt="ps-to-tif" src="fileadmin/file.ps" width="" height="" />',
            'message' => 'File extension is "ps" for "ps" (not-processed/not-forced/non-web-format)',
        ];
        // pass-through-format
        yield 'svg to tif' => [
            'template' => '<f:image src="fileadmin/file.svg" fileExtension="tif" width="80" alt="svg-to-tif" />',
            'contentMatchRegExp' => '<img alt="svg-to-tif" src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" />',
            'message' => 'File extension is "tif" for "svg" (processed/forced)',
        ];
        // invalid
        yield 'avi to tif' => [
            'template' => '<f:image src="fileadmin/invalid-file.avi" fileExtension="tif" width="80" alt="avi-to-tif" />',
            'contentMatchRegExp' => '<img alt="avi-to-tif" src="fileadmin/invalid-file.avi" width="" height="" />',
            'message' => 'File extension is "avi" for "avi" (not-processed/not-forced/invalid)',
        ];
        yield 'exe to tif' => [
            'template' => '<f:image src="fileadmin/invalid-file.exe" fileExtension="tif" width="80" alt="exe-to-tif" />',
            'contentMatchRegExp' => '<img alt="exe-to-tif" src="fileadmin/invalid-file.exe" width="" height="" />',
            'message' => 'File extension is "exe" for "exe" (not-processed/not-forced/invalid)',
        ];
        yield 'zip to tif' => [
            'template' => '<f:image src="fileadmin/invalid-file.zip" fileExtension="tif" width="80" alt="zip-to-tif" />',
            'contentMatchRegExp' => '<img alt="zip-to-tif" src="fileadmin/invalid-file.zip" width="" height="" />',
            'message' => 'File extension is "zip" for "zip" (not-processed/not-forced/invalid)',
        ];
    }

    #[DataProvider('filesConvertedToTifFileFormatUsingGraphicsMagickDataProvider')]
    #[Test]
    public function filesConvertedToTifFileFormatUsingGraphicsMagick(string $template, string $contentMatchRegExp, string $message): void
    {
        self::assertSame('GraphicsMagick', $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor']);
        self::assertMatchesRegularExpression(sprintf('@\s*%s\s*@', $contentMatchRegExp), $this->renderTemplate($template), $message);
    }
}
