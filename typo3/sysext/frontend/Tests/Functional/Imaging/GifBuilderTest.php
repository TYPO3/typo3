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

namespace TYPO3\CMS\Frontend\Tests\Functional\Imaging;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\ImageResource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class GifBuilderTest extends FunctionalTestCase
{
    private function setupFullTestEnvironment(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/fileadmin');
    }

    /**
     * Copies the 401x600 portrait fixture into fileadmin under its own name and
     * returns it as a File, so tests that need a real image do not repeat the
     * copy-and-look-up dance.
     */
    private function importFixtureImage(string $targetName): File
    {
        $this->setupFullTestEnvironment();
        copy(
            __DIR__ . '/../Fixtures/Images/kasper-skarhoj1.jpg',
            Environment::getPublicPath() . '/fileadmin/' . $targetName
        );
        $file = $this->get(StorageRepository::class)->findByUid(1)->getFile($targetName);
        self::assertInstanceOf(File::class, $file);
        return $file;
    }

    private function buildImage(array $conf): ImageResource
    {
        $gifBuilder = new GifBuilder();
        $gifBuilder->start($conf, []);
        $imageResource = $gifBuilder->gifBuild();
        self::assertNotNull($imageResource);
        return $imageResource;
    }

    /**
     * Renders a GIFBUILDER configuration and returns the written PNG, for tests
     * that assert on actual pixels rather than on dimensions.
     */
    private function renderPng(array $conf): \GdImage
    {
        $image = imagecreatefrompng(Environment::getPublicPath() . '/' . $this->buildImage($conf)->getPublicUrl());
        self::assertInstanceOf(\GdImage::class, $image);
        return $image;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function rgb(\GdImage $image, int $x, int $y): array
    {
        $color = imagecolorat($image, $x, $y);
        return [($color >> 16) & 0xff, ($color >> 8) & 0xff, $color & 0xff];
    }

    /**
     * Sets up Environment to simulate Composer mode and a cli request
     */
    private function simulateCliRequestInComposerMode(): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            true,
            Environment::getProjectPath(),
            Environment::getPublicPath() . '/public',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
    }

    public static function fileExtensionDataProvider(): array
    {
        return [
            'jpg' => ['jpg'],
            'png' => ['png'],
            'gif' => ['gif'],
            'webp' => ['webp'],
        ];
    }

    #[DataProvider('fileExtensionDataProvider')]
    #[Test]
    public function buildSimpleGifBuilderImageInComposerMode(string $fileExtension): void
    {
        $this->simulateCliRequestInComposerMode();

        $conf = [
            'XY' => '10,10',
            'format' => $fileExtension,
        ];

        $gifBuilder = new GifBuilder();
        $gifBuilder->start($conf, []);
        $imageResource = $gifBuilder->gifBuild();

        self::assertFileDoesNotExist(Environment::getProjectPath() . '/' . $imageResource->getPublicUrl());
        self::assertFileExists(Environment::getPublicPath() . '/' . $imageResource->getPublicUrl());
        self::assertEquals($fileExtension, $imageResource->getExtension());
    }

    #[Test]
    public function buildImageInCommandLineInterfaceAndComposerMode(): void
    {
        $this->simulateCliRequestInComposerMode();
        $this->setupFullTestEnvironment();

        copy(
            __DIR__ . '/../Fixtures/Images/kasper-skarhoj1.jpg',
            Environment::getPublicPath() . '/fileadmin/kasper-skarhoj-gifbuilder.jpg'
        );

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigArray([]);
        $request = new ServerRequest('https://www.example.com/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);

        $result = $contentObjectRenderer->cObjGetSingle(
            'IMAGE',
            [
                'file' => 'GIFBUILDER',
                'file.' => [
                    'XY' => '[10.w],[10.h]',
                    'format' => 'jpg',

                    '10' => 'IMAGE',
                    '10.' => [
                        'file' => 'fileadmin/kasper-skarhoj-gifbuilder.jpg',
                    ],
                ],
            ]
        );
        self::assertStringStartsWith('<img src="typo3temp/assets/images/csm_kasper-skarhoj-gifbuilder_', $result);
    }

    #[Test]
    public function getImageResourceInCommandLineInterfaceAndComposerMode(): void
    {
        $this->simulateCliRequestInComposerMode();
        $this->setupFullTestEnvironment();

        copy(
            __DIR__ . '/../Fixtures/Images/kasper-skarhoj1.jpg',
            Environment::getPublicPath() . '/fileadmin/kasper-skarhoj-gifbuilder-imageresource.jpg'
        );

        $request = new ServerRequest('https://www.example.com/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);

        $result = $contentObjectRenderer->cObjGetSingle(
            'IMG_RESOURCE',
            [
                'file' => 'GIFBUILDER',
                'file.' => [
                    'XY' => '[10.w],[10.h]',
                    'format' => 'jpg',

                    '10' => 'IMAGE',
                    '10.' => [
                        'file' => 'fileadmin/kasper-skarhoj-gifbuilder-imageresource.jpg',
                    ],
                ],
            ]
        );
        self::assertStringStartsWith('typo3temp/assets/images/csm_kasper-skarhoj-gifbuilder-imageresource_', $result);
    }

    #[Test]
    public function buildImageWithMaskInCommandLineInterfaceAndComposerMode(): void
    {
        $this->simulateCliRequestInComposerMode();
        $this->setupFullTestEnvironment();

        copy(
            __DIR__ . '/../Fixtures/Images/kasper-skarhoj1.jpg',
            Environment::getPublicPath() . '/fileadmin/kasper-skarhoj-gifbuilder.jpg'
        );

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigArray([]);
        $request = new ServerRequest('https://www.example.com/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);

        $result = $contentObjectRenderer->cObjGetSingle(
            'IMAGE',
            [
                'file' => 'GIFBUILDER',
                'file.' => [
                    'XY' => '[10.w],[10.h]',
                    'format' => 'jpg',

                    '10' => 'IMAGE',
                    '10.' => [
                        'file' => 'fileadmin/kasper-skarhoj-gifbuilder.jpg',
                    ],
                    '20' => 'IMAGE',
                    '20.' => [
                        'offset' => '0,500',
                        'XY' => '[mask.w],40',

                        'file' => 'GIFBUILDER',
                        'file.' => [
                            'XY' => '400,60',
                            'backColor' => '#cccccc',
                        ],

                        'mask' => 'GIFBUILDER',
                        'mask.' => [
                            'XY' => '[10.w]+55,60',
                            'backColor' => '#cccccc',

                            '10' => 'TEXT',
                            '10.' => [
                                'text' => 'Kasper Skårhøj',
                                'fontColor' => '#111111',
                                'fontSize' => '20',
                                'offset' => '20,40',
                            ],
                        ],
                    ],
                ],
            ]
        );
        self::assertStringStartsWith('<img src="typo3temp/', $result);
    }

    /**
     * Check hashes of Images overlayed with other images are idempotent
     */
    #[Test]
    public function overlayImagesHasStableHash(): void
    {
        $this->setupFullTestEnvironment();

        copy(
            __DIR__ . '/../Fixtures/Images/kasper-skarhoj1.jpg',
            Environment::getPublicPath() . '/fileadmin/kasper-skarhoj1.jpg'
        );

        $storageRepository = $this->get(StorageRepository::class)->findByUid(1);
        $file = $storageRepository->getFile('kasper-skarhoj1.jpg');

        self::assertInstanceOf(File::class, $file);
        self::assertFalse($file->isMissing());

        $conf = [
            'XY' => '[10.w],[10.h]',
            'format' => 'jpg',
            'quality' => 88,
            '10' => 'IMAGE',
            '10.' => [
                'file' => $file,
                'file.' => [
                    'width' => 300,
                ],
            ],
            '30' => 'IMAGE',
            '30.' => [
                'file' => $file,
                'file.' => [
                    'align' => 'l,t',
                    'width' => 100,
                ],
            ],
        ];

        $gifBuilder = new GifBuilder();
        $gifBuilder->start($conf, []);
        $setup1 = $gifBuilder->setup;
        $imageResource1 = $gifBuilder->gifBuild();

        // Recreate a fresh GifBuilder instance, to catch inconsistencies in hashing for different instances
        $gifBuilder = new GifBuilder();
        $gifBuilder->start($conf, []);
        $setup2 = $gifBuilder->setup;
        $imageResource2 = $gifBuilder->gifBuild();

        self::assertSame($setup1, $setup2, 'The Setup resulting from two equal configurations must be equal');
        self::assertSame($imageResource1->getPublicUrl(), $imageResource2->getPublicUrl());
    }

    /**
     * Every EFFECT keyword, with the canvas size it has to come back with.
     * Source is a 30x20 canvas. shear and wave compute their new geometry in
     * PHP and are therefore pinned exactly; rotate hands the bounding box to
     * libgd, which rounds differently between builds, and is covered by
     * rotateGrowsTheCanvasToFitTheRotatedImage() instead.
     *
     * @return array<string, array{0: non-empty-string, 1: int, 2: int}>
     */
    public static function effectKeywordDataProvider(): array
    {
        return [
            'blur' => ['blur = 80', 30, 20],
            'charcoal' => ['charcoal = 1', 30, 20],
            'colors' => ['colors = 4', 30, 20],
            'edge' => ['edge', 30, 20],
            'emboss' => ['emboss', 30, 20],
            'sharpen' => ['sharpen = 80', 30, 20],
            'gray' => ['gray', 30, 20],
            'invert' => ['invert', 30, 20],
            'gamma' => ['gamma = 2.0', 30, 20],
            'solarize' => ['solarize = 50', 30, 20],
            'swirl' => ['swirl = 360', 30, 20],
            'flip' => ['flip', 30, 20],
            'flop' => ['flop', 30, 20],
            'shear grows the width by height * tan(angle)' => ['shear = 20', 38, 20],
            'wave grows the height by twice the amplitude' => ['wave = 5,30', 30, 30],
        ];
    }

    #[DataProvider('effectKeywordDataProvider')]
    #[Test]
    public function effectKeywordRendersAnImageOfTheExpectedSize(string $effect, int $expectedWidth, int $expectedHeight): void
    {
        $conf = [
            'XY' => '30,20',
            'backColor' => '#3050c0',
            'format' => 'png',
            '10' => 'BOX',
            '10.' => ['color' => '#e0a020', 'dimensions' => '5,5,20,10'],
            '20' => 'EFFECT',
            '20.' => ['value' => $effect],
        ];

        $imageResource = $this->buildImage($conf);

        self::assertFileExists(Environment::getPublicPath() . '/' . $imageResource->getPublicUrl());
        self::assertSame($expectedWidth, $imageResource->getWidth());
        self::assertSame($expectedHeight, $imageResource->getHeight());
    }

    #[Test]
    public function rotateGrowsTheCanvasToFitTheRotatedImage(): void
    {
        $conf = [
            'XY' => '30,20',
            'backColor' => '#3050c0',
            'format' => 'png',
            '10' => 'EFFECT',
            // Chained with a pixel loop, so a size change followed by a per-pixel
            // keyword is covered in one go.
            '10.' => ['value' => 'rotate = 15 | solarize = 50'],
        ];

        $imageResource = $this->buildImage($conf);

        self::assertGreaterThan(30, $imageResource->getWidth());
        self::assertGreaterThan(20, $imageResource->getHeight());
    }

    #[Test]
    public function shearIsClampedSoAnExtremeAngleStillProducesAFiniteImage(): void
    {
        // The old IM path emitted "-shear 90", whose tan diverges, and came back
        // with nothing usable. 90 is clamped to 85°, so the 401x600 source grows
        // to 401 + ceil(600 * tan(85°)) = 7260 pixels wide.
        $conf = [
            'XY' => '[10.w],[10.h]',
            'format' => 'jpg',
            '10' => 'IMAGE',
            '10.' => ['file' => $this->importFixtureImage('kasper-skarhoj-shear.jpg')],
            '20' => 'EFFECT',
            '20.' => ['value' => 'shear = 90'],
        ];

        $imageResource = $this->buildImage($conf);

        self::assertSame(7260, $imageResource->getWidth());
        self::assertSame(600, $imageResource->getHeight());
    }

    /**
     * The source fixture is 401x600, so a width-only SCALE has to derive
     * round(600 * 200 / 401) = 299 as the height.
     *
     * @return array<string, array{0: array<string, string>, 1: int, 2: int}>
     */
    public static function scaleDataProvider(): array
    {
        return [
            'width and height' => [['width' => '200', 'height' => '150'], 200, 150],
            'width only keeps the aspect ratio' => [['width' => '200'], 200, 299],
        ];
    }

    #[DataProvider('scaleDataProvider')]
    #[Test]
    public function buildImageWithScaleObjectProducesTheExpectedDimensions(array $scale, int $expectedWidth, int $expectedHeight): void
    {
        $conf = [
            'XY' => '[10.w],[10.h]',
            'format' => 'jpg',
            '10' => 'IMAGE',
            '10.' => ['file' => $this->importFixtureImage('kasper-skarhoj-scale-' . $expectedHeight . '.jpg')],
            '20' => 'SCALE',
            '20.' => $scale,
        ];

        $imageResource = $this->buildImage($conf);

        self::assertSame($expectedWidth, $imageResource->getWidth());
        self::assertSame($expectedHeight, $imageResource->getHeight());
    }

    #[Test]
    public function chainedEffectKeywordsOperateOnColoursNotOnPaletteIndices(): void
    {
        // "colors" quantises the canvas. If that leaves a palette image behind, the
        // pixel loop of the following keyword reads palette indices instead of
        // colours: solarize then allocates on a full palette, gets false back and
        // kills the render with a TypeError.
        $image = $this->renderPng([
            'XY' => '20,20',
            'backColor' => '#ff0000',
            'format' => 'png',
            '10' => 'EFFECT',
            '10.' => ['value' => 'colors = 4 | solarize = 50'],
        ]);

        // Quantised red is (252, 2, 4); solarize inverts the red channel above the
        // 127 threshold and leaves the two low channels alone.
        self::assertSame([3, 2, 4], self::rgb($image, 10, 10));
    }

    #[Test]
    public function niceTextLeavesTheTransparentBackgroundTransparent(): void
    {
        $image = $this->renderPng([
            'XY' => '60,30',
            'backColor' => 'transparent',
            'format' => 'png',
            '10' => 'TEXT',
            '10.' => [
                'text' => 'Hi',
                'fontColor' => '#ffffff',
                'fontSize' => '18',
                'fontFile' => 'EXT:core/Resources/Private/Font/nimbus.ttf',
                'offset' => '2,22',
                'niceText' => '1',
            ],
        ]);

        // The corner is far away from the glyphs and has to stay fully transparent.
        self::assertSame(127, (imagecolorat($image, 59, 0) >> 24) & 0x7f);
    }

    #[Test]
    public function adjustRemapsInputLevelsOnATruecolourCanvas(): void
    {
        $image = $this->renderPng([
            'XY' => '20,20',
            'backColor' => '#808080',
            'format' => 'png',
            '10' => 'ADJUST',
            '10.' => ['value' => 'inputLevels = 100,200'],
        ]);

        // 128 stretched from the 100-200 input range to 0-255 lands on 71.
        self::assertSame([71, 71, 71], self::rgb($image, 10, 10));
    }

    /**
     * @return array<string, array{0: non-empty-string}>
     */
    public static function singleIntegerDataProvider(): array
    {
        return [
            'positive integer' => ['1'],
            'negative integer' => ['-1'],
            'zero' => ['0'],
        ];
    }

    #[DataProvider('singleIntegerDataProvider')]
    #[Test]
    public function calcOffsetWithSingleIntegerReturnsTheGivenIntegerAsString(string $number): void
    {
        $gifBuilder = new GifBuilder();
        $result = $gifBuilder->calcOffset($number);

        self::assertSame($number, $result);
    }

    #[Test]
    public function calcOffsetWithMultipleIntegersReturnsTheGivenIntegerCommaSeparated(): void
    {
        $gifBuilder = new GifBuilder();
        $numbers = '1,2,3';
        $result = $gifBuilder->calcOffset($numbers);

        self::assertSame($numbers, $result);
    }

    #[Test]
    public function calcOffsetTrimsWhitespaceAroundProvidedNumbers(): void
    {
        $gifBuilder = new GifBuilder();
        $result = $gifBuilder->calcOffset(' 1, 2, 3 ');

        self::assertSame('1,2,3', $result);
    }

    /**
     * @return array<string, array{0: non-empty-string, 1: non-empty-string}>
     */
    public static function roundingDataProvider(): array
    {
        return [
            'rounding down' => ['1.1', '1'],
            'rounding up' => ['1.9', '2'],
        ];
    }

    #[DataProvider('roundingDataProvider')]
    #[Test]
    public function calcOffsetRoundsNumbersToNearestInteger(string $input, string $expectedResult): void
    {
        $gifBuilder = new GifBuilder();
        $result = $gifBuilder->calcOffset($input);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{0: non-empty-string, 1: non-empty-string}>
     */
    public static function calculationDataProvider(): array
    {
        return [
            'addition of positive numbers' => ['1+1', '2'],
            'addition of negative numbers' => ['-1+-1', '-2'],
            'subtraction' => ['5-2', '3'],
            'multiplication' => ['2*5', '10'],
            'division with whole-number result' => ['10/5', '2'],
            'division with rounding up' => ['19/5', '4'],
            'division with rounding down' => ['21/5', '4'],
            'modulo' => ['21%5', '1'],
        ];
    }

    #[DataProvider('calculationDataProvider')]
    #[Test]
    public function calcOffsetDoesTheProvidedCalculation(string $input, string $expectedResult): void
    {
        $gifBuilder = new GifBuilder();
        $result = $gifBuilder->calcOffset($input);

        self::assertSame($expectedResult, $result);
    }
}
