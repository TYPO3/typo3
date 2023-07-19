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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariant;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ImageViewHelperTest extends FunctionalTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelper/Folders/fileadmin/' => 'fileadmin/',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ViewHelpers/ImageViewHelper/fal_image.csv');
    }

    public static function invalidArgumentsDataProvider(): array
    {
        return [
            [
                '<f:image />',
                1382284106,
                'Unable to render image tag: You must either specify a string src or a File object.',
            ],
            [
                '<f:image src="" />',
                1382284106,
                'Unable to render image tag: You must either specify a string src or a File object.',
            ],
            [
                '<f:image src="something" />',
                1509741911,
                'Unable to render image tag: Folder "/something/" does not exist.',
            ],
            [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/" />',
                1509741914,
                'Unable to render image tag: File /typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers does not exist.',
            ],
            [
                '<f:image src="fileadmin/image.jpg" />',
                1509741912,
                'Unable to render image tag: Supplied fileadmin/image.jpg could not be resolved to a File or FileReference.',
            ],
            [
                '<f:image src="something" fileExtension="dummy" />',
                1618989190,
                'Unable to render image tag: The extension dummy is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\'] as a valid image file extension and can not be processed.',
            ],
        ];
    }

    #[DataProvider('invalidArgumentsDataProvider')]
    #[Test]
    public function renderThrowsExceptionOnInvalidArguments(string $template, int $expectedExceptionCode, string $message): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedExceptionCode);
        $this->expectExceptionMessage($message);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        (new TemplateView($context))->render();
    }

    public static function invalidArgumentsWithContentObjectPresentDataProvider(): array
    {
        return [
            [
                '<f:image />',
                1382284106,
                'Unable to render image tag in "tt_content:123": You must either specify a string src or a File object.',
            ],
            [
                '<f:image src="" />',
                1382284106,
                'Unable to render image tag in "tt_content:123": You must either specify a string src or a File object.',
            ],
            [
                '<f:image src="something" />',
                1509741911,
                'Unable to render image tag in "tt_content:123": Folder "/something/" does not exist.',
            ],
            [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/" />',
                1509741914,
                'Unable to render image tag in "tt_content:123": File /typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers does not exist.',
            ],
            [
                '<f:image src="fileadmin/image.jpg" />',
                1509741912,
                'Unable to render image tag in "tt_content:123": Supplied fileadmin/image.jpg could not be resolved to a File or FileReference.',
            ],
            [
                '<f:image src="something" fileExtension="dummy" />',
                1618989190,
                'Unable to render image tag in "tt_content:123": The extension dummy is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\'] as a valid image file extension and can not be processed.',
            ],
        ];
    }

    #[DataProvider('invalidArgumentsWithContentObjectPresentDataProvider')]
    #[Test]
    public function renderThrowsExceptionWithContentObjectPresentOnInvalidArguments(string $template, int $expectedExceptionCode, string $message): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedExceptionCode);
        $this->expectExceptionMessage($message);

        $cObj = new ContentObjectRenderer();
        $cObj->start(['uid' => 123], 'tt_content');
        $serverRequest = (new ServerRequest())
            ->withAttribute('currentContentObject', $cObj)
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->setRequest(new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource($template);
        (new TemplateView($context))->render();
    }

    public static function basicUsageScalingCroppingDataProvider(): \Generator
    {
        yield 'original size' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" />',
            '@^<img src="(fileadmin/ImageViewHelperTest\.jpg)" width="400" height="300" alt="" />$@',
            400,
            300,
        ];
        yield 'half width' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" width="200" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="200" height="150" alt="" />$@',
            200,
            150,
        ];
        yield 'stretched' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" width="200" height="200" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="200" height="200" alt="" />$@',
            200,
            200,
        ];
        yield 'inline-cropped' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" width="100c" height="100c" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="100" height="100" alt="" />$@',
            100,
            100,
        ];
        yield 'inline-max width' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" width="300m" height="300m" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="300" height="225" alt="" />$@',
            300,
            225,
        ];
        yield 'inline-max height' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" width="400m" height="150m" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="200" height="150" alt="" />$@',
            200,
            150,
        ];
        yield 'inline-max width & height' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" width="200m" height="150m" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="200" height="150" alt="" />$@',
            200,
            150,
        ];
        yield 'inline-max width does not upscale' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" width="500m" />',
            '@^<img src="(fileadmin/ImageViewHelperTest\.jpg)" width="400" height="300" alt="" />$@',
            400,
            300,
        ];
        yield 'inline-max height does not upscale' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" height="350m" />',
            '@^<img src="(fileadmin/ImageViewHelperTest\.jpg)" width="400" height="300" alt="" />$@',
            400,
            300,
        ];
        yield 'min width' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" height="150" minWidth="250" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="250" height="188" alt="" />$@',
            250,
            188,
        ];
        // would be 200x150, but image will be scaled down to have a width of 100
        yield 'max width' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" height="150" maxWidth="100" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="100" height="75" alt="" />$@',
            100,
            75,
        ];
        yield 'min height' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" width="200" minHeight="200" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="267" height="200" alt="" />$@',
            267,
            200,
        ];
        // would be 200x150, but image will be scaled down to have a height of 75
        yield 'max height' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" width="200" maxHeight="75" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="100" height="75" alt="" />$@',
            100,
            75,
        ];
        yield 'file record' => [
            '<f:image image="{fileReference.originalFile}" />',
            '@^<img src="(fileadmin/user_upload/ImageViewHelperFalTest\.jpg)" width="400" height="300" alt="alt text from metadata" />$@',
            400,
            300,
        ];
        yield 'file id' => [
            '<f:image src="1" />',
            '@^<img src="(fileadmin/user_upload/ImageViewHelperFalTest\.jpg)" width="400" height="300" alt="alt text from metadata" />$@',
            400,
            300,
        ];
        yield 'file reference record' => [
            '<f:image image="{fileReference}" />',
            '@^<img src="(fileadmin/user_upload/ImageViewHelperFalTest\.jpg)" width="400" height="300" alt="alt text from reference" title="title from reference" />$@',
            400,
            300,
        ];
        yield 'file reference id' => [
            '<f:image src="1" treatIdAsReference="1" />',
            '@^<img src="(fileadmin/user_upload/ImageViewHelperFalTest\.jpg)" width="400" height="300" alt="alt text from reference" title="title from reference" />$@',
            400,
            300,
        ];
    }

    #[DataProvider('basicUsageScalingCroppingDataProvider')]
    #[Test]
    public function basicUsageScalingCropping(string $template, string $expected, int $expectedWidth, int $expectedHeight): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $context->getVariableProvider()->add('fileReference', $this->get(ResourceFactory::class)->getFileReferenceObject(1));
        $result = (new TemplateView($context))->render();
        self::assertMatchesRegularExpression($expected, $result);

        $matches = [];
        preg_match($expected, $result, $matches);
        [$width, $height] = getimagesize($this->instancePath . '/' . $matches[1]);
        self::assertEquals($expectedWidth, $width, 'width of generated image does not match expected width');
        self::assertEquals($expectedHeight, $height, 'height of generated image does not match expected height');
    }

    public static function cropVariantDataProvider(): \Generator
    {
        yield 'crop false' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" crop="false" />',
            '@^<img src="(fileadmin/ImageViewHelperTest\.jpg)" width="400" height="300" alt="" />$@',
            400,
            300,
        ];
        yield 'crop null' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" crop="null" />',
            '@^<img src="(fileadmin/ImageViewHelperTest\.jpg)" width="400" height="300" alt="" />$@',
            400,
            300,
        ];
        yield 'crop as array' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" crop="{default: {cropArea: {x: 0.2, y: 0.2, width: 0.5, height: 0.5}}}" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="200" height="150" alt="" />$@',
            200,
            150,
        ];
        yield 'default crop variant' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" crop="{crop}" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="200" height="225" alt="" />$@',
            200,
            225,
        ];
        yield 'square crop variant' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" crop="{crop}" cropVariant="square" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="300" height="300" alt="" />$@',
            300,
            300,
        ];
        yield 'wide crop variant' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" crop="{crop}" cropVariant="wide" />',
            '@^<img src="(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)" width="400" height="200" alt="" />$@',
            400,
            200,
        ];
    }

    #[DataProvider('cropVariantDataProvider')]
    #[Test]
    public function cropVariant(string $template, string $expected, int $expectedWidth, int $expectedHeight): void
    {
        // Based on 400x300 dimensions
        $cropVariantCollection = new CropVariantCollection([
            new CropVariant('default', 'Default', new Area(0.25, 0.25, 0.5, 0.75)),
            new CropVariant('square', 'Square', new Area(0.125, 0, 0.75, 1)),
            new CropVariant('wide', 'Wide', new Area(0, 1 / 6, 1, 2 / 3)),
        ]);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getVariableProvider()->add('crop', (string)$cropVariantCollection);
        $context->getTemplatePaths()->setTemplateSource($template);
        $result = (new TemplateView($context))->render();
        self::assertMatchesRegularExpression($expected, $result);

        $matches = [];
        preg_match($expected, $result, $matches);
        [$width, $height] = getimagesize($this->instancePath . '/' . $matches[1]);
        self::assertEquals($expectedWidth, $width, 'width of generated image does not match expected width');
        self::assertEquals($expectedHeight, $height, 'height of generated image does not match expected height');
    }

    public static function tagAttributesDataProvider(): \Generator
    {
        yield 'css' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" class="myClass" style="border: none" />',
            '<img class="myClass" style="border: none" src="fileadmin/ImageViewHelperTest.jpg" width="400" height="300" alt="" />',
        ];
        yield 'loading' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" loading="lazy" />',
            '<img loading="lazy" src="fileadmin/ImageViewHelperTest.jpg" width="400" height="300" alt="" />',
        ];
        yield 'decoding' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" decoding="async" />',
            '<img decoding="async" src="fileadmin/ImageViewHelperTest.jpg" width="400" height="300" alt="" />',
        ];
        yield 'alt' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" alt="alternative text" />',
            '<img alt="alternative text" src="fileadmin/ImageViewHelperTest.jpg" width="400" height="300" />',
        ];
        yield 'title' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" title="image title" />',
            '<img title="image title" src="fileadmin/ImageViewHelperTest.jpg" width="400" height="300" alt="" />',
        ];
        yield 'longdesc' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" longdesc="description" />',
            '<img longdesc="description" src="fileadmin/ImageViewHelperTest.jpg" width="400" height="300" alt="" />',
        ];
        yield 'usemap' => [
            '<f:image src="fileadmin/ImageViewHelperTest.jpg" usemap="#map" />',
            '<img usemap="#map" src="fileadmin/ImageViewHelperTest.jpg" width="400" height="300" alt="" />',
        ];
        yield 'alt from file' => [
            '<f:image src="1" />',
            '<img src="fileadmin/user_upload/ImageViewHelperFalTest.jpg" width="400" height="300" alt="alt text from metadata" />',
        ];
        yield 'overwrite alt from file' => [
            '<f:image src="1" alt="" />',
            '<img src="fileadmin/user_upload/ImageViewHelperFalTest.jpg" width="400" height="300" alt="" />',
        ];
        yield 'title from file reference' => [
            '<f:image src="1" treatIdAsReference="1" />',
            '<img src="fileadmin/user_upload/ImageViewHelperFalTest.jpg" width="400" height="300" alt="alt text from reference" title="title from reference" />',
        ];
        yield 'overwrite title from file reference' => [
            '<f:image src="1" treatIdAsReference="1" title="overwritten title" />',
            '<img title="overwritten title" src="fileadmin/user_upload/ImageViewHelperFalTest.jpg" width="400" height="300" alt="alt text from reference" />',
        ];
    }

    #[DataProvider('tagAttributesDataProvider')]
    #[Test]
    public function tagAttributes(string $template, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $result = (new TemplateView($context))->render();
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function fileExtensionArgument(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:image src="fileadmin/ImageViewHelperTest.jpg" fileExtension="png" />');
        self::assertMatchesRegularExpression(
            '@^<img src="fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.png" width="400" height="300" alt="" />$@',
            (new TemplateView($context))->render(),
        );
    }

    #[Test]
    public function absoluteArgument(): void
    {
        GeneralUtility::setIndpEnv('TYPO3_REQUEST_DIR', 'https://typo3-testing.local/');

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:image src="fileadmin/ImageViewHelperTest.jpg" absolute="1" />');
        self::assertEquals(
            '<img src="https://typo3-testing.local/fileadmin/ImageViewHelperTest.jpg" width="400" height="300" alt="" />',
            (new TemplateView($context))->render(),
        );
    }

    #[Test]
    public function base64Argument(): void
    {
        GeneralUtility::setIndpEnv('TYPO3_REQUEST_DIR', 'https://typo3-testing.local/');

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:image src="fileadmin/ImageViewHelperTest.jpg" base64="1" width="5" height="5" />');
        self::assertEquals(
            '<img src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQIAEgASAAD/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAAFAAUDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAdEAEAAgICAwAAAAAAAAAAAAABAgMAEQQhEiIl/8QAFQEBAQAAAAAAAAAAAAAAAAAAAQL/xAAXEQADAQAAAAAAAAAAAAAAAAABAhEA/9oADAMBAAIRAxEAPwCBv53C5IfMiW1vjKyVqsgjEDRo0JJOt+3a6xjGU4jQZYC7/9k=" width="5" height="5" alt="" />',
            (new TemplateView($context))->render(),
        );
    }

    #[Test]
    public function focusAreaAttribute(): void
    {
        // Based on 400x300 dimensions
        $cropVariantCollection = new CropVariantCollection([
            new CropVariant('default', 'Default', Area::createEmpty(), null, null, new Area(0.25, 0.25, 0.5, 0.75)),
        ]);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getVariableProvider()->add('crop', (string)$cropVariantCollection);
        $context->getTemplatePaths()->setTemplateSource('<f:image src="fileadmin/ImageViewHelperTest.jpg" crop="{crop}" />');
        self::assertEquals(
            '<img data-focus-area="{&quot;x&quot;:100,&quot;y&quot;:75,&quot;width&quot;:200,&quot;height&quot;:225}" src="fileadmin/ImageViewHelperTest.jpg" width="400" height="300" alt="" />',
            (new TemplateView($context))->render(),
        );
    }
}
