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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Uri;

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
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ViewHelpers/ImageViewHelper/fal_image.csv');
    }

    public static function invalidArgumentsDataProvider(): array
    {
        return [
            [
                '<f:uri.image />',
                1460976233,
                'Unable to render image uri: You must either specify a string src or a File object.',
            ],
            [
                '<f:uri.image src="" />',
                1460976233,
                'Unable to render image uri: You must either specify a string src or a File object.',
            ],
            [
                '<f:uri.image src="something" />',
                1509741908,
                'Unable to render image uri: Supplied something could not be resolved to a File or FileReference.',
            ],
            [
                '<f:uri.image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/" />',
                1509741907,
                'Unable to render image uri: Tried to access a private resource file "EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/" from fallback compatibility storage. This storage only handles public files.',
            ],
            [
                '<f:uri.image src="fileadmin/image.jpg" />',
                1509741908,
                'Unable to render image uri: Supplied fileadmin/image.jpg could not be resolved to a File or FileReference.',
            ],
            [
                '<f:uri.image src="something" fileExtension="dummy" />',
                1618992262,
                'Unable to render image uri: The extension dummy is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\'] as a valid image file extension and can not be processed.',
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
                '<f:uri.image />',
                1460976233,
                'Unable to render image uri in "tt_content:123": You must either specify a string src or a File object.',
            ],
            [
                '<f:uri.image src="" />',
                1460976233,
                'Unable to render image uri in "tt_content:123": You must either specify a string src or a File object.',
            ],
            [
                '<f:uri.image src="something" />',
                1509741908,
                'Unable to render image uri in "tt_content:123": Supplied something could not be resolved to a File or FileReference.',
            ],
            [
                '<f:uri.image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/" />',
                1509741907,
                'Unable to render image uri in "tt_content:123": Tried to access a private resource file "EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/" from fallback compatibility storage. This storage only handles public files.',
            ],
            [
                '<f:uri.image src="fileadmin/image.jpg" />',
                1509741908,
                'Unable to render image uri in "tt_content:123": Supplied fileadmin/image.jpg could not be resolved to a File or FileReference.',
            ],
            [
                '<f:uri.image src="something" fileExtension="dummy" />',
                1618992262,
                'Unable to render image uri in "tt_content:123": The extension dummy is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\'] as a valid image file extension and can not be processed.',
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
        $serverRequest = (new ServerRequest())
            ->withAttribute('currentContentObject', $cObj)
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $cObj->setRequest($serverRequest);
        $cObj->start(['uid' => 123], 'tt_content');

        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource($template);
        (new TemplateView($context))->render();
    }

    public static function basicScalingCroppingDataProvider(): \Generator
    {
        yield 'original size' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" />',
            '@^(fileadmin/ImageViewHelperTest\.jpg)$@',
            400,
            300,
        ];
        yield 'half width' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" width="200" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            200,
            150,
        ];
        yield 'stretched' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" width="200" height="200" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            200,
            200,
        ];
        yield 'inline-cropped' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" width="100c" height="100c" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            100,
            100,
        ];
        yield 'inline-max width' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" width="300m" height="300m" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            300,
            225,
        ];
        yield 'inline-max height' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" width="400m" height="150m" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            200,
            150,
        ];
        yield 'inline-max width & height' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" width="200m" height="150m" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            200,
            150,
        ];
        yield 'inline-max width does not upscale' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" width="500m" />',
            '@^(fileadmin/ImageViewHelperTest\.jpg)$@',
            400,
            300,
        ];
        yield 'inline-max height does not upscale' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" height="350m" />',
            '@^(fileadmin/ImageViewHelperTest\.jpg)$@',
            400,
            300,
        ];
        yield 'min width' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" height="150" minWidth="250" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            250,
            188,
        ];
        // would be 200x150, but image will be scaled down to have a width of 100
        yield 'max width' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" height="150" maxWidth="100" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            100,
            75,
        ];
        yield 'min height' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" width="200" minHeight="200" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            267,
            200,
        ];
        // would be 200x150, but image will be scaled down to have a height of 75
        yield 'max height' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" width="200" maxHeight="75" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            100,
            75,
        ];
        yield 'file record' => [
            '<f:uri.image image="{fileReference.originalFile}" />',
            '@^(fileadmin/user_upload/ImageViewHelperFalTest\.jpg)$@',
            400,
            300,
        ];
        yield 'file id' => [
            '<f:uri.image src="1" />',
            '@^(fileadmin/user_upload/ImageViewHelperFalTest\.jpg)$@',
            400,
            300,
        ];
        yield 'file reference record' => [
            '<f:uri.image image="{fileReference}" cropVariant="square" />',
            '@^(fileadmin/_processed_/c/f/csm_ImageViewHelperFalTest_.*\.jpg)$@',
            300,
            300,
        ];
        yield 'file reference id' => [
            '<f:uri.image src="1" treatIdAsReference="1" cropVariant="square" />',
            '@^(fileadmin/_processed_/c/f/csm_ImageViewHelperFalTest_.*\.jpg)$@',
            300,
            300,
        ];
    }

    #[DataProvider('basicScalingCroppingDataProvider')]
    #[Test]
    public function basicScalingCropping(string $template, string $expected, int $expectedWidth, int $expectedHeight): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getVariableProvider()->add('fileReference', $this->get(ResourceFactory::class)->getFileReferenceObject(1));
        $context->getTemplatePaths()->setTemplateSource($template);
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
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" crop="false" />',
            '@^(fileadmin/ImageViewHelperTest\.jpg)$@',
            400,
            300,
        ];
        yield 'crop null' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" crop="null" />',
            '@^(fileadmin/ImageViewHelperTest\.jpg)$@',
            400,
            300,
        ];
        yield 'crop as array' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" crop="{default: {cropArea: {x: 0.2, y: 0.2, width: 0.5, height: 0.5}}}" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            200,
            150,
        ];
        yield 'default crop variant' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" crop="{crop}" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            200,
            225,
        ];
        yield 'square crop variant' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" crop="{crop}" cropVariant="square" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
            300,
            300,
        ];
        yield 'wide crop variant' => [
            '<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" crop="{crop}" cropVariant="wide" />',
            '@^(fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.jpg)$@',
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

    #[Test]
    public function fileExtensionArgument(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" fileExtension="png" />');
        self::assertMatchesRegularExpression(
            '@^fileadmin/_processed_/5/3/csm_ImageViewHelperTest_.*\.png$@',
            (new TemplateView($context))->render(),
        );
    }

    #[Test]
    public function absoluteArgument(): void
    {
        GeneralUtility::setIndpEnv('TYPO3_REQUEST_DIR', 'https://typo3-testing.local/');

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" absolute="1" />');
        self::assertEquals(
            'https://typo3-testing.local/fileadmin/ImageViewHelperTest.jpg',
            (new TemplateView($context))->render(),
        );
    }

    #[Test]
    public function base64Argument(): void
    {
        GeneralUtility::setIndpEnv('TYPO3_REQUEST_DIR', 'https://typo3-testing.local/');

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:uri.image src="fileadmin/ImageViewHelperTest.jpg" base64="1" width="5" height="5" />');
        self::assertEquals(
            'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQIAEgASAAD/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAAFAAUDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAdEAEAAgICAwAAAAAAAAAAAAABAgMAEQQhEiIl/8QAFQEBAQAAAAAAAAAAAAAAAAAAAQL/xAAXEQADAQAAAAAAAAAAAAAAAAABAhEA/9oADAMBAAIRAxEAPwCBv53C5IfMiW1vjKyVqsgjEDRo0JJOt+3a6xjGU4jQZYC7/9k=',
            (new TemplateView($context))->render(),
        );
    }
}
