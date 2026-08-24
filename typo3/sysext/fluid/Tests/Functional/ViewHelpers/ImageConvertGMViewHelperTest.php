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
use TYPO3\CMS\Core\Core\Environment;
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
        GeneralUtility::rmdir(Environment::getPublicPath() . '/fileadmin/_processed_', true);
        parent::tearDown();
    }

    /**
     * Render string fluid template.
     */
    private function renderTemplate(string $template): string
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        return new TemplateView($context)->render();
    }

    public static function filesConvertedToDefaultFileFormatUsingGraphicsMagickDataProvider(): \Generator
    {
        // web-format
        yield 'ai to default' => [
            'template' => '<f:image src="fileadmin/file.ai" width="80" alt="ai-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="34" alt="ai-to-default" />',
            'message' => 'File extension is "png" for "ai" (processed)',
        ];
        yield 'avif to default' => [
            'template' => '<f:image src="fileadmin/file.avif" width="80" alt="avif-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="60" alt="avif-to-default" />',
            'message' => 'File extension is "png" for "avif" (processed)',
        ];
        yield 'bmp to default' => [
            'template' => '<f:image src="fileadmin/file.bmp" width="80" alt="bmp-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="34" alt="bmp-to-default" />',
            'message' => 'File extension is "png" for "bmp" (processed)',
        ];
        yield 'gif to default' => [
            'template' => '<f:image src="fileadmin/file.gif" width="80" alt="gif-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.gif" width="80" height="34" alt="gif-to-default" />',
            'message' => 'File extension is "gif" for "gif" (processed)',
        ];
        yield 'jpg to default' => [
            'template' => '<f:image src="fileadmin/file.jpg" width="80" alt="jpg-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" alt="jpg-to-default" />',
            'message' => 'File extension is "jpg" for "jpg" (processed)',
        ];
        yield 'pdf to default' => [
            'template' => '<f:image src="fileadmin/file.pdf" width="80" alt="pdf-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="34" alt="pdf-to-default" />',
            'message' => 'File extension is "png" for "pdf" (processed)',
        ];
        yield 'png to default' => [
            'template' => '<f:image src="fileadmin/file.png" width="80" alt="png-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="34" alt="png-to-default" />',
            'message' => 'File extension is "png" for "png" (processed)',
        ];
        yield 'tif to default' => [
            'template' => '<f:image src="fileadmin/file.tif" width="80" alt="tif-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.png" width="80" height="34" alt="tif-to-default" />',
            'message' => 'File extension is "png" for "tif" (processed)',
        ];
        yield 'webp to default' => [
            'template' => '<f:image src="fileadmin/file.webp" width="80" alt="webp-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" alt="webp-to-default" />',
            'message' => 'File extension is "webp" for "webp" (processed)',
        ];
        // non-web format
        yield 'eps to default' => [
            'template' => '<f:image src="fileadmin/file.eps" width="80" alt="eps-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.eps" alt="eps-to-default" />',
            'message' => 'File extension is "eps" for "eps" (unprocessed/original/non-web-format)',
        ];
        yield 'fax to default' => [
            'template' => '<f:image src="fileadmin/file.fax" width="80" alt="fax-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.fax" alt="fax-to-default" />',
            'message' => 'File extension is "fax" for "fax" (unprocessed/original/non-web-format)',
        ];
        yield 'ps to default' => [
            'template' => '<f:image src="fileadmin/file.ps" width="80" alt="ps-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.ps" alt="ps-to-default" />',
            'message' => 'File extension is "ps" for "ps" (unprocessed/original/non-web-format)',
        ];
        // pass-through format
        yield 'svg to default' => [
            'template' => '<f:image src="fileadmin/file.svg" width="80" alt="svg-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.svg" width="80" height="34" alt="svg-to-default" />',
            'message' => 'File extension is "ps" for "ps" (unprocessed/original/passthrough)',
        ];
        // invalid
        yield 'avi to default' => [
            'template' => '<f:image src="fileadmin/invalid-file.avi" width="80" alt="avi-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.avi" alt="avi-to-default" />',
            'message' => 'File extension is "avi" for "avi" (unprocessed/original/invalid)',
        ];
        yield 'exe to default' => [
            'template' => '<f:image src="fileadmin/invalid-file.exe" width="80" alt="exe-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.exe" alt="exe-to-default" />',
            'message' => 'File extension is "exe" for "exe" (unprocessed/original/invalid)',
        ];
        yield 'zip to default' => [
            'template' => '<f:image src="fileadmin/invalid-file.zip" width="80" alt="zip-to-default" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.zip" alt="zip-to-default" />',
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
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" alt="ai-to-jpg" />',
            'message' => 'File extension is "jpg" for "ai" (processed/forced)',
        ];
        yield 'avif to jpg' => [
            'template' => '<f:image src="fileadmin/file.avif" fileExtension="jpg" width="80" alt="avif-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="60" alt="avif-to-jpg" />',
            'message' => 'File extension is "jpg" for "avif" (processed/forced)',
        ];
        yield 'bmp to jpg' => [
            'template' => '<f:image src="fileadmin/file.bmp" fileExtension="jpg" width="80" alt="bmp-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" alt="bmp-to-jpg" />',
            'message' => 'File extension is "jpg" for "bmp" (processed/forced)',
        ];
        yield 'gif to jpg' => [
            'template' => '<f:image src="fileadmin/file.gif" fileExtension="jpg" width="80" alt="gif-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" alt="gif-to-jpg" />',
            'message' => 'File extension is "jpg" for "gif" (processed/forced)',
        ];
        yield 'jpg to jpg' => [
            'template' => '<f:image src="fileadmin/file.jpg" fileExtension="jpg" width="80" alt="jpg-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" alt="jpg-to-jpg" />',
            'message' => 'File extension is "jpg" for "gif" (processed/forced)',
        ];
        yield 'pdf to jpg' => [
            'template' => '<f:image src="fileadmin/file.pdf" fileExtension="jpg" width="80" alt="pdf-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" alt="pdf-to-jpg" />',
            'message' => 'File extension is "jpg" for "pdf" (processed/forced)',
        ];
        yield 'png to jpg' => [
            'template' => '<f:image src="fileadmin/file.png" fileExtension="jpg" width="80" alt="png-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" alt="png-to-jpg" />',
            'message' => 'File extension is "jpg" for "png" (processed/forced)',
        ];
        yield 'tif to jpg' => [
            'template' => '<f:image src="fileadmin/file.tif" fileExtension="jpg" width="80" alt="tif-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" alt="tif-to-jpg" />',
            'message' => 'File extension is "jpg" for "tif" (processed/forced)',
        ];
        yield 'webp to jpg' => [
            'template' => '<f:image src="fileadmin/file.webp" fileExtension="jpg" width="80" alt="webp-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" alt="webp-to-jpg" />',
            'message' => 'File extension is "jpg" for "webp" (processed/forced)',
        ];
        // non-web-format
        yield 'eps to jpg' => [
            'template' => '<f:image src="fileadmin/file.eps" fileExtension="jpg" width="80" alt="eps-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.eps" alt="eps-to-jpg" />',
            'message' => 'File extension is "eps" for "eps" (unprocessed/non-web format/not-enforceable)',
        ];
        yield 'fax to jpg' => [
            'template' => '<f:image src="fileadmin/file.fax" fileExtension="jpg" width="80" alt="fax-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.fax" alt="fax-to-jpg" />',
            'message' => 'File extension is "fax" for "fax" (unprocessed/non-web format/not-enforceable)',
        ];
        yield 'ps to jpg' => [
            'template' => '<f:image src="fileadmin/file.ps" fileExtension="jpg" width="80" alt="ps-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.ps" alt="ps-to-jpg" />',
            'message' => 'File extension is "ps" for "ps" (unprocessed/non-web format/not-enforceable)',
        ];
        // pass-through-format
        yield 'svg to jpg' => [
            'template' => '<f:image src="fileadmin/file.svg" fileExtension="jpg" width="80" alt="svg-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.jpg" width="80" height="34" alt="svg-to-jpg" />',
            'message' => 'File extension is "jpg" for "svg" (processed/forced)',
        ];
        // invalid
        yield 'avi to jpg' => [
            'template' => '<f:image src="fileadmin/invalid-file.avi" fileExtension="jpg" width="80" alt="avi-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.avi" alt="avi-to-jpg" />',
            'message' => 'File extension is "avi" for "avi" (unprocessed/invalid/not-enforceable)',
        ];
        yield 'exe to jpg' => [
            'template' => '<f:image src="fileadmin/invalid-file.exe" fileExtension="jpg" width="80" alt="exe-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.exe" alt="exe-to-jpg" />',
            'message' => 'File extension is "avi" for "avi" (unprocessed/invalid/not-enforceable)',
        ];
        yield 'zip to jpg' => [
            'template' => '<f:image src="fileadmin/invalid-file.zip" fileExtension="jpg" width="80" alt="zip-to-jpg" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.zip" alt="zip-to-jpg" />',
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
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" alt="ai-to-webp" />',
            'message' => 'File extension is "webp" for "ai" (processed/forced)',
        ];
        yield 'avif to webp' => [
            'template' => '<f:image src="fileadmin/file.avif" fileExtension="webp" width="80" alt="avif-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="60" alt="avif-to-webp" />',
            'message' => 'File extension is "webp" for "avif" (processed/forced)',
        ];
        yield 'bmp to webp' => [
            'template' => '<f:image src="fileadmin/file.bmp" fileExtension="webp" width="80" alt="bmp-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" alt="bmp-to-webp" />',
            'message' => 'File extension is "webp" for "bmp" (processed/forced)',
        ];
        yield 'gif to webp' => [
            'template' => '<f:image src="fileadmin/file.gif" fileExtension="webp" width="80" alt="gif-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" alt="gif-to-webp" />',
            'message' => 'File extension is "webp" for "gif" (processed/forced)',
        ];
        yield 'jpg to webp' => [
            'template' => '<f:image src="fileadmin/file.jpg" fileExtension="webp" width="80" alt="jpg-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" alt="jpg-to-webp" />',
            'message' => 'File extension is "webp" for "jpg" (processed/forced)',
        ];
        yield 'pdf to webp' => [
            'template' => '<f:image src="fileadmin/file.pdf" fileExtension="webp" width="80" alt="pdf-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" alt="pdf-to-webp" />',
            'message' => 'File extension is "webp" for "pdf" (processed/forced)',
        ];
        yield 'png to webp' => [
            'template' => '<f:image src="fileadmin/file.png" fileExtension="webp" width="80" alt="png-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" alt="png-to-webp" />',
            'message' => 'File extension is "webp" for "png" (processed/forced)',
        ];
        yield 'tif to webp' => [
            'template' => '<f:image src="fileadmin/file.tif" fileExtension="webp" width="80" alt="tif-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" alt="tif-to-webp" />',
            'message' => 'File extension is "webp" for "tif" (processed/forced)',
        ];
        yield 'webp to webp' => [
            'template' => '<f:image src="fileadmin/file.webp" fileExtension="webp" width="80" alt="webp-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" alt="webp-to-webp" />',
            'message' => 'File extension is "webp" for "tif" (processed/forced)',
        ];
        // non-web-format
        yield 'eps to webp' => [
            'template' => '<f:image src="fileadmin/file.eps" fileExtension="webp" width="80" alt="eps-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.eps" alt="eps-to-webp" />',
            'message' => 'File extension is "eps" for "eps" (not-processed/not-forced/non-web-format)',
        ];
        yield 'fax to webp' => [
            'template' => '<f:image src="fileadmin/file.fax" fileExtension="webp" width="80" alt="fax-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.fax" alt="fax-to-webp" />',
            'message' => 'File extension is "eps" for "eps" (not-processed/not-forced/non-web-format)',
        ];
        yield 'ps to webp' => [
            'template' => '<f:image src="fileadmin/file.ps" fileExtension="webp" width="80" alt="ps-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.ps" alt="ps-to-webp" />',
            'message' => 'File extension is "eps" for "eps" (not-processed/not-forced/non-web-format)',
        ];
        // pass-through-format
        yield 'svg to webp' => [
            'template' => '<f:image src="fileadmin/file.svg" fileExtension="webp" width="80" alt="svg-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.webp" width="80" height="34" alt="svg-to-webp" />',
            'message' => 'File extension is "webp" for "svg" (processed/forced)',
        ];
        // invalid
        yield 'avi to webp' => [
            'template' => '<f:image src="fileadmin/invalid-file.avi" fileExtension="webp" width="80" alt="avi-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.avi" alt="avi-to-webp" />',
            'message' => 'File extension is "avi" for "avi" (not-processed/not-forced/invalid)',
        ];
        yield 'exe to webp' => [
            'template' => '<f:image src="fileadmin/invalid-file.exe" fileExtension="webp" width="80" alt="exe-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.exe" alt="exe-to-webp" />',
            'message' => 'File extension is "exe" for "exe" (not-processed/not-forced/invalid)',
        ];
        yield 'zip to webp' => [
            'template' => '<f:image src="fileadmin/invalid-file.zip" fileExtension="webp" width="80" alt="zip-to-webp" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.zip" alt="zip-to-webp" />',
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
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="34" alt="ai-to-avif" />',
            'message' => 'File extension is "avif.jpg" for "ai" (processed/forced)',
        ];
        yield 'avif to avif' => [
            'template' => '<f:image src="fileadmin/file.avif" fileExtension="avif" width="80" alt="avif-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="60" alt="avif-to-avif" />',
            'message' => 'File extension is "avif.jpg" for "avif" (processed/forced)',
        ];
        yield 'bmp to avif' => [
            'template' => '<f:image src="fileadmin/file.bmp" fileExtension="avif" width="80" alt="bmp-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="34" alt="bmp-to-avif" />',
            'message' => 'File extension is "avif.jpg" for "bmp" (processed/forced)',
        ];
        yield 'gif to avif' => [
            'template' => '<f:image src="fileadmin/file.gif" fileExtension="avif" width="80" alt="gif-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.gif" width="80" height="34" alt="gif-to-avif" />',
            'message' => 'File extension is "avif.jpg" for "gif" (processed/forced)',
        ];
        yield 'jpg to avif' => [
            'template' => '<f:image src="fileadmin/file.jpg" fileExtension="avif" width="80" alt="jpg-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.jpg" width="80" height="34" alt="jpg-to-avif" />',
            'message' => 'File extension is "avif.jpg" for "jpg" (processed/forced)',
        ];
        yield 'pdf to avif' => [
            'template' => '<f:image src="fileadmin/file.pdf" fileExtension="avif" width="80" alt="pdf-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="34" alt="pdf-to-avif" />',
            'message' => 'File extension is "avif.jpg" for "pdf" (processed/forced)',
        ];
        yield 'png to avif' => [
            'template' => '<f:image src="fileadmin/file.png" fileExtension="avif" width="80" alt="png-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="34" alt="png-to-avif" />',
            'message' => 'File extension is "avif.jpg" for "png" (processed/forced)',
        ];
        yield 'tif to avif' => [
            'template' => '<f:image src="fileadmin/file.tif" fileExtension="avif" width="80" alt="tif-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.png" width="80" height="34" alt="tif-to-avif" />',
            'message' => 'File extension is "avif.jpg" for "tif" (processed/forced)',
        ];
        yield 'webp to avif' => [
            'template' => '<f:image src="fileadmin/file.webp" fileExtension="avif" width="80" alt="webp-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.avif\.webp" width="80" height="34" alt="webp-to-avif" />',
            'message' => 'File extension is "avif.jpg" for "webp" (processed/forced)',
        ];
        // non-web-format
        yield 'eps to avif' => [
            'template' => '<f:image src="fileadmin/file.eps" fileExtension="avif" width="80" alt="eps-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.eps" alt="eps-to-avif" />',
            'message' => 'File extension is "eps" for "eps" (not-processed/not-forced/non-web-format)',
        ];
        yield 'fax to avif' => [
            'template' => '<f:image src="fileadmin/file.fax" fileExtension="avif" width="80" alt="fax-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.fax" alt="fax-to-avif" />',
            'message' => 'File extension is "fax" for "fax" (not-processed/not-forced/non-web-format)',
        ];
        yield 'ps to avif' => [
            'template' => '<f:image src="fileadmin/file.ps" fileExtension="avif" width="80" alt="ps-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.ps" alt="ps-to-avif" />',
            'message' => 'File extension is "fax" for "fax" (not-processed/not-forced/non-web-format)',
        ];
        // pass-through-format (GM does not support writing AVIF)
        yield 'svg to avif' => [
            'template' => '<f:image src="fileadmin/file.svg" fileExtension="avif" width="80" alt="svg-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.svg" width="481" height="203" alt="svg-to-avif" />',
            'message' => 'File extension is "avif.jpg" for "svg" (processed/forced)',
        ];
        // invalid
        yield 'avi to avif' => [
            'template' => '<f:image src="fileadmin/invalid-file.avi" fileExtension="avif" width="80" alt="avi-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.avi" alt="avi-to-avif" />',
            'message' => 'File extension is "avi" for "avi" (not-processed/not-forced/invalid)',
        ];
        yield 'exe to avif' => [
            'template' => '<f:image src="fileadmin/invalid-file.exe" fileExtension="avif" width="80" alt="exe-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.exe" alt="exe-to-avif" />',
            'message' => 'File extension is "exe" for "exe" (not-processed/not-forced/invalid)',
        ];
        yield 'zip to avif' => [
            'template' => '<f:image src="fileadmin/invalid-file.zip" fileExtension="avif" width="80" alt="zip-to-avif" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.zip" alt="zip-to-avif" />',
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
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" alt="ai-to-tif" />',
            'message' => 'File extension is "tif" for "ai" (processed/forced)',
        ];
        yield 'avif to tif' => [
            'template' => '<f:image src="fileadmin/file.avif" fileExtension="tif" width="80" alt="avif-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="60" alt="avif-to-tif" />',
            'message' => 'File extension is "tif" for "avif" (processed/forced)',
        ];
        yield 'bmp to tif' => [
            'template' => '<f:image src="fileadmin/file.bmp" fileExtension="tif" width="80" alt="bmp-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" alt="bmp-to-tif" />',
            'message' => 'File extension is "tif" for "bmp" (processed/forced)',
        ];
        yield 'gif to tif' => [
            'template' => '<f:image src="fileadmin/file.gif" fileExtension="tif" width="80" alt="gif-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" alt="gif-to-tif" />',
            'message' => 'File extension is "tif" for "gif" (processed/forced)',
        ];
        yield 'jpg to tif' => [
            'template' => '<f:image src="fileadmin/file.jpg" fileExtension="tif" width="80" alt="jpg-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" alt="jpg-to-tif" />',
            'message' => 'File extension is "tif" for "jpg" (processed/forced)',
        ];
        yield 'pdf to tif' => [
            'template' => '<f:image src="fileadmin/file.pdf" fileExtension="tif" width="80" alt="pdf-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" alt="pdf-to-tif" />',
            'message' => 'File extension is "tif" for "pdf" (processed/forced)',
        ];
        yield 'png to tif' => [
            'template' => '<f:image src="fileadmin/file.png" fileExtension="tif" width="80" alt="png-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" alt="png-to-tif" />',
            'message' => 'File extension is "tif" for "png" (processed/forced)',
        ];
        yield 'tif to tif' => [
            'template' => '<f:image src="fileadmin/file.tif" fileExtension="tif" width="80" alt="tif-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" alt="tif-to-tif" />',
            'message' => 'File extension is "tif" for "tif" (processed/forced)',
        ];
        yield 'webp to tif' => [
            'template' => '<f:image src="fileadmin/file.webp" fileExtension="tif" width="80" alt="webp-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" alt="webp-to-tif" />',
            'message' => 'File extension is "tif" for "webp" (processed/forced)',
        ];
        // non-web-format
        yield 'eps to tif' => [
            'template' => '<f:image src="fileadmin/file.eps" fileExtension="tif" width="80" alt="eps-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.eps" alt="eps-to-tif" />',
            'message' => 'File extension is "eps" for "eps" (not-processed/not-forced/non-web-format)',
        ];
        yield 'fax to tif' => [
            'template' => '<f:image src="fileadmin/file.fax" fileExtension="tif" width="80" alt="fax-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.fax" alt="fax-to-tif" />',
            'message' => 'File extension is "fax" for "fax" (not-processed/not-forced/non-web-format)',
        ];
        yield 'ps to tif' => [
            'template' => '<f:image src="fileadmin/file.ps" fileExtension="tif" width="80" alt="ps-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/file.ps" alt="ps-to-tif" />',
            'message' => 'File extension is "ps" for "ps" (not-processed/not-forced/non-web-format)',
        ];
        // pass-through-format
        yield 'svg to tif' => [
            'template' => '<f:image src="fileadmin/file.svg" fileExtension="tif" width="80" alt="svg-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/_processed_/.*/.*/csm_file_.*\.tif" width="80" height="34" alt="svg-to-tif" />',
            'message' => 'File extension is "tif" for "svg" (processed/forced)',
        ];
        // invalid
        yield 'avi to tif' => [
            'template' => '<f:image src="fileadmin/invalid-file.avi" fileExtension="tif" width="80" alt="avi-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.avi" alt="avi-to-tif" />',
            'message' => 'File extension is "avi" for "avi" (not-processed/not-forced/invalid)',
        ];
        yield 'exe to tif' => [
            'template' => '<f:image src="fileadmin/invalid-file.exe" fileExtension="tif" width="80" alt="exe-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.exe" alt="exe-to-tif" />',
            'message' => 'File extension is "exe" for "exe" (not-processed/not-forced/invalid)',
        ];
        yield 'zip to tif' => [
            'template' => '<f:image src="fileadmin/invalid-file.zip" fileExtension="tif" width="80" alt="zip-to-tif" />',
            'contentMatchRegExp' => '<img src="fileadmin/invalid-file.zip" alt="zip-to-tif" />',
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
