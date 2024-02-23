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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\ImageService;
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
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
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

    public static function renderReturnsExpectedMarkupDataProvider(): array
    {
        return [
            'crop false' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="false" />',
                '@^<img src="typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@',
            ],
            'crop null' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="null" />',
                '@^<img src="typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@',
            ],
            'crop as array' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="{\'x\': 200, \'y\': 200, \'width\': 200, \'height\': 200}" />',
                '@^<img src="typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@',
            ],
            'jpg file extension' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="null" fileExtension="jpg" />',
                '@^<img src="typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@',
            ],
        ];
    }

    #[DataProvider('renderReturnsExpectedMarkupDataProvider')]
    #[Test]
    public function renderReturnsExpectedMarkup(string $template, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertMatchesRegularExpression($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function renderReturnsCorrectAltAttribute(): void
    {
        $imageServiceMock = $this->createMock(ImageService::class);
        $imageServiceMock->method('getImage')->willReturn(new File([], $this->get(ResourceFactory::class)->getDefaultStorage()));

        $metaDataRepository = $this->createMock(MetaDataRepository::class);
        $metaDataRepository->method('findByFileUid')->willReturn(['alternative' => 'alt text']);

        GeneralUtility::setSingletonInstance(ImageService::class, $imageServiceMock);
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $metaDataRepository);

        $context = $this->get(RenderingContextFactory::class)->create();

        // No alt attribute given - use metadata
        $context->getTemplatePaths()->setTemplateSource(
            '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" />'
        );
        self::assertStringContainsString(
            'alt="alt text"',
            (new TemplateView($context))->render()
        );

        // Enforce empty alt attribute - omit metadata fallback
        $context->getTemplatePaths()->setTemplateSource(
            '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" alt="" />'
        );
        self::assertStringContainsString(
            'alt=""',
            (new TemplateView($context))->render()
        );

    }
}
