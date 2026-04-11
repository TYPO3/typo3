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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Image;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class SrcsetViewHelperTest extends FunctionalTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/Image/SrcsetViewHelper/fileadmin/' => 'fileadmin/',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ViewHelpers/Image/SrcsetViewHelper/fal_image.csv');
    }

    public static function renderReturnsExpectedMarkupDataProvider(): array
    {
        return [
            'srcset with width descriptors' => [
                "{f:image.srcset(image: image, srcset: '100w, 200w')}",
                '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 100w, (fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 200w$@',
                [
                    [100, 75],
                    [200, 150],
                ],
            ],
            'srcset with density descriptors' => [
                "{f:image.srcset(image: image, referenceWidth: 80, srcset: '1x, 2x')}",
                '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 1x, (fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 2x$@',
                [
                    [80, 60],
                    [160, 120],
                ],
            ],
            'image as child node' => [
                "{image -> f:image.srcset(srcset: '100w, 200w')}",
                '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 100w, (fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 200w$@',
                [
                    [100, 75],
                    [200, 150],
                ],
            ],
            'file as image' => [
                "{f:image.srcset(image: image.originalFile, srcset: '100w, 200w')}",
                '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 100w, (fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 200w$@',
                [
                    [100, 75],
                    [200, 150],
                ],
            ],
            'absolute' => [
                "{f:image.srcset(image: image, srcset: '100w', absolute: 1)}",
                '@^https?://.*(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 100w$@',
                [
                    [100, 75],
                ],
            ],
            'with fileExtension' => [
                "{f:image.srcset(image: image, srcset: '100w', fileExtension: 'png')}",
                '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.png) 100w$@',
                [
                    [100, 75],
                ],
            ],
            'with cropVariant' => [
                "{f:image.srcset(image: image, srcset: '100w', cropVariant: 'square')}",
                '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 100w$@',
                [
                    [100, 100],
                ],
            ],
            'with crop as array' => [
                "{f:image.srcset(image: image, srcset: '100w', crop: {default:{cropArea:{x:0,y:0,width:0.75,height:1}}})}",
                '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 100w$@',
                [
                    [100, 100],
                ],
            ],
            'with crop as json' => [
                "{f:image.srcset(image: image, srcset: '100w', crop: '" . json_encode(['default' => ['cropArea' => ['x' => 0, 'y' => 0, 'width' => 0.75, 'height' => 1]]]) . "')}",
                '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 100w$@',
                [
                    [100, 100],
                ],
            ],
            'with disabled crop' => [
                "{f:image.srcset(image: image, srcset: '100w', cropVariant: 'square', crop: false)}",
                '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 100w$@',
                [
                    [100, 75],
                ],
            ],
            'bigger width than original' => [
                "{f:image.srcset(image: image, srcset: '800w')}",
                '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 800w$@',
                [
                    [800, 600],
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderReturnsExpectedMarkupDataProvider')]
    public function renderReturnsExpectedMarkup(string $template, string $expected, array $expectedSizes): void
    {
        $normalizedParams = NormalizedParams::createFromServerParams(['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/index.php']);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->assign('image', $this->get(ResourceFactory::class)->getFileReferenceObject(1));
        $result = $view->render();
        self::assertMatchesRegularExpression($expected, $result);

        preg_match($expected, $result, $matches);
        array_shift($matches);
        $matches = array_values($matches);

        foreach ($expectedSizes as $i => $expectedSize) {
            $this->assertImageSize($expectedSize[0], $expectedSize[1], $matches[$i]);
        }
    }

    #[Test]
    public function rendersFallbackImageForDisabledUpscaling(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowUpscaling'] = false;

        $template = "{f:image.srcset(image: image, srcset: '400w, 999w', crop: {default:{cropArea:{x:0,y:0,width:0.75,height:1}}})}";
        $expected = '@^(fileadmin/_processed_/4/0/csm_SrcsetViewHelperTest_.*\.jpg) 300w$@';

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->assign('image', $this->get(ResourceFactory::class)->getFileReferenceObject(1));
        $result = $view->render();
        self::assertMatchesRegularExpression($expected, $result);

        preg_match($expected, $result, $matches);
        $this->assertImageSize(300, 300, $matches[1]);
    }

    #[Test]
    public function throwsExceptionForInvalidSrcset(): void
    {
        $this->expectExceptionCode(1774530722);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource("{f:image.srcset(image: image, srcset: '400, 600')}");
        $view = new TemplateView($context);
        $view->assign('image', $this->get(ResourceFactory::class)->getFileReferenceObject(1));
        $view->render();
    }

    #[Test]
    public function throwsExceptionForInvalidFileExtension(): void
    {
        $this->expectExceptionCode(1697797923);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource("{f:image.srcset(image: image, srcset: '400w', fileExtension: 'invalid')}");
        $view = new TemplateView($context);
        $view->assign('image', $this->get(ResourceFactory::class)->getFileReferenceObject(1));
        $view->render();
    }

    #[Test]
    public function throwsExceptionIfImageNotSpecified(): void
    {
        $this->expectExceptionCode(1697797783);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource("{f:image.srcset(srcset: '400w')}");
        (new TemplateView($context))->render();
    }

    #[Test]
    public function throwsExceptionIfImageNotValidObject(): void
    {
        $this->expectExceptionCode(1697797783);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource("{image -> f:image.srcset(srcset: '400w')}");
        $view = new TemplateView($context);
        $view->assign('image', 'no object');
        $view->render();
    }

    /**
     * Asserts that dimensions of generated image are correct
     */
    protected function assertImageSize(int $expectedWidth, int $expectedHeight, string $imageFile)
    {
        [$detectedWidth, $detectedHeight] = getimagesize(static::getInstancePath() . '/' . $imageFile);
        self::assertEquals($expectedWidth, $detectedWidth, "Image width $detectedWidth does not match expected width $expectedWidth: $imageFile");
        self::assertEquals($expectedHeight, $detectedHeight, "Image height $detectedHeight does not match expected height $expectedHeight: $imageFile");
    }
}
