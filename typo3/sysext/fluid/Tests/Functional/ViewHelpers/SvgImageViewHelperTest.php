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
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class SvgImageViewHelperTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['filemetadata'];
    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/svg_image_test',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/svg_image_test/Resources/Public/Images/ImageViewHelperTest1.svg' => 'fileadmin/user_upload/FALImageViewHelperTest1.svg',
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/svg_image_test/Resources/Public/Images/ImageViewHelperTest2.svg' => 'fileadmin/user_upload/FALImageViewHelperTest2.svg',
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/svg_image_test/Resources/Public/Images/ImageViewHelperTest3.svg' => 'fileadmin/user_upload/FALImageViewHelperTest3.svg',
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/svg_image_test/Resources/Public/Images/ImageViewHelperTest4.svg' => 'fileadmin/user_upload/FALImageViewHelperTest4.svg',
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/svg_image_test/Resources/Public/Images/ImageViewHelperTest5.svg' => 'fileadmin/user_upload/FALImageViewHelperTest5.svg',
    ];

    protected array $additionalFoldersToCreate = [
        '/fileadmin/user_upload',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/crops.csv');
        $this->setUpBackendUser(1);
    }

    public static function renderReturnsExpectedMarkupDataProvider(): array
    {
        /** Used files:
         * ===========
         *
         * ImageViewHelperTest1.svg: with viewBox, no width, no height, 0x0 origin
         * ImageViewHelperTest2.svg: with viewBox, no width, no height, shifted origin
         * ImageViewHelperTest3.svg: with viewBox, with width and height
         * ImageViewHelperTest4.svg: no viewBox, with height and width
        **/

        // width, height, [scalingFactorBasedOnWidth (60px/80%)], [scalingFactorBasedOnOffset (15px/10%)], [pixelBasedOnOffset]
        $dimensionMap = [
            'ImageViewHelperTest1.svg' => [
                'input'                 => [1680, 1050],
                'fixedCrop60px'         => [60, 38, 0.03571428572, 0.008928571429, 15, 9],
                'heightAtMaxWidth60px'  => 62,
                'relativeCrop80Percent' => [1344, 840, 0.8, 0.1, 168, 105],
                'falUidCropped'         => 1,
                'falUidUncropped'       => 6,
                'falCropString'         => '77 581 231 238',
                'falCropDim'            => [231, 238],
            ],
            'ImageViewHelperTest2.svg' => [
                'input'                 => [283.5, 283.5],
                'fixedCrop60px'         => [60, 60, 0.2116402117, 0.05291005291, 14, 14],
                'heightAtMaxWidth60px'  => 15,
                'relativeCrop80Percent' => [226, 226, 0.8, 0.1, 28, 28],
                'falUidCropped'         => 2,
                'falUidUncropped'       => 7,
                'falCropString'         => '18 62 241 60',
                'falCropDim'            => [241, 60],
            ],
            'ImageViewHelperTest3.svg' => [
                'input'                 => [940.7, 724],
                'fixedCrop60px'         => [60, 46, 0.06378228979, 0.01594557245, 15, 11],
                'heightAtMaxWidth60px'  => 109,
                'relativeCrop80Percent' => [753, 579, 0.8, 0.1, 94, 72],
                'falUidCropped'         => 3,
                'falUidUncropped'       => 8,
                'falCropString'         => '235 303 176 320',
                'falCropDim'            => [176, 320],
            ],
            'ImageViewHelperTest4.svg' => [
                'input'                 => [1680, 1050],
                'fixedCrop60px'         => [60, 38, 0.03571428572, 0.008928571429, 15, 9],
                'heightAtMaxWidth60px'  => 69,
                'relativeCrop80Percent' => [1344, 840, 0.8, 0.1, 168, 105],
                'falUidCropped'         => 4,
                'falUidUncropped'       => 9,
                'falCropString'         => '59 345 114 131',
                'falCropDim'            => [113, 131],
            ],
        ];

        $expected = [];

        $maximum = count($dimensionMap);

        $storageDirOriginal = 'typo3conf/ext/svg_image_test/Resources/Public/Images';
        $storageDirTemp     = 'typo3temp/assets/_processed_/[0-9a-f]/[0-9a-f]';
        $storageDirFal      = 'fileadmin/user_upload';
        $storageDirFalTemp  = 'fileadmin/_processed_/[0-9a-f]/[0-9a-f]';

        // To prevent excess copy and paste labor, this is done programmatically:
        for ($i = 1; $i <= $maximum; $i++) {
            $fn = 'ImageViewHelperTest' . $i . '.svg';
            $fUid = $dimensionMap[$fn]['falUidCropped'];
            $fUidUncropped = $dimensionMap[$fn]['falUidUncropped'];

            $width  = round($dimensionMap[$fn]['input'][0]);
            $height = round($dimensionMap[$fn]['input'][1]);

            // Note: Uncropped SVGs are returned from their original location. No conversion/tampering is done.

            //# SECTION 1: Referenced via EXT: ###
            $expected[sprintf('no crop (%s)', $fn)] = [
                sprintf(
                    '<f:image src="EXT:svg_image_test/Resources/Public/Images/%s" width="%d" height="%d" />',
                    $fn,
                    $width,
                    $height,
                ),
                sprintf(
                    '@^<img src="(%s/%s)" width="%d" height="%d" alt="" />$@',
                    $storageDirOriginal,
                    $fn,
                    $width,
                    $height,
                ),
                null,
                false,
            ];

            $expected[sprintf('empty crop (%s)', $fn)] = [
                sprintf(
                    '<f:image src="EXT:svg_image_test/Resources/Public/Images/%s" width="%d" height="%d" crop="null" />',
                    $fn,
                    $width,
                    $height,
                ),
                sprintf(
                    '@^<img src="(%s/%s)" width="%d" height="%d" alt="" />$@',
                    $storageDirOriginal,
                    $fn,
                    $width,
                    $height,
                ),
                null,
                false,
            ];

            $expected[sprintf('crop as array - forced 60px (%s)', $fn)] = [
                sprintf(
                    '<f:image src="EXT:svg_image_test/Resources/Public/Images/%1$s" width="%2$d" height="%3$d" crop="{\'default\':{\'cropArea\':{\'width\':%4$s,\'height\':%4$s,\'x\':%5$s,\'y\':%5$s},\'selectedRatio\':\'1:1\',\'focusArea\':null}}" />',
                    $fn,
                    $dimensionMap[$fn]['fixedCrop60px'][0], // width
                    $dimensionMap[$fn]['fixedCrop60px'][1], // height
                    $dimensionMap[$fn]['fixedCrop60px'][2], // crop-string width/height
                    $dimensionMap[$fn]['fixedCrop60px'][3] // crop-string offset left/top
                ),
                sprintf(
                    '@^<img src="(%s/csm_ImageViewHelperTest%d_.*\.svg)" width="%d" height="%d" alt="" />$@',
                    $storageDirTemp,
                    $i,
                    $dimensionMap[$fn]['fixedCrop60px'][0],
                    $dimensionMap[$fn]['fixedCrop60px'][1],
                ),
                $dimensionMap[$fn]['fixedCrop60px'][4] . ' ' . $dimensionMap[$fn]['fixedCrop60px'][5] . ' ' . $dimensionMap[$fn]['fixedCrop60px'][0] . ' ' . $dimensionMap[$fn]['fixedCrop60px'][1],
            ];

            $expected[sprintf('crop as array - no width/height (%s)', $fn)] = [
                sprintf(
                    '<f:image src="EXT:svg_image_test/Resources/Public/Images/%1$s" crop="{\'default\':{\'cropArea\':{\'width\':%2$s,\'height\':%2$s,\'x\':%3$s,\'y\':%3$s},\'selectedRatio\':\'1:1\',\'focusArea\':null}}" />',
                    $fn,
                    $dimensionMap[$fn]['relativeCrop80Percent'][2], // crop-string width/height
                    $dimensionMap[$fn]['relativeCrop80Percent'][3] // crop-string offset left/top
                ),
                sprintf(
                    '@^<img src="(%s/csm_ImageViewHelperTest%d_.*\.svg)" width="%d" height="%d" alt="" />$@',
                    $storageDirTemp,
                    $i,
                    $dimensionMap[$fn]['relativeCrop80Percent'][0],
                    $dimensionMap[$fn]['relativeCrop80Percent'][1],
                ),
                $dimensionMap[$fn]['relativeCrop80Percent'][4] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][5] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][0] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][1],
            ];

            $expected[sprintf('force pixel-conversion, no crop (%s)', $fn)] = [
                sprintf(
                    '<f:image src="EXT:svg_image_test/Resources/Public/Images/%s" width="%d" height="%d" fileExtension="png" />',
                    $fn,
                    $width,
                    $height,
                ),
                sprintf(
                    '@^<img src="(%s/csm_ImageViewHelperTest%d_.*\.png)" width="%d" height="%d" alt="" />$@',
                    $storageDirTemp,
                    $i,
                    $width,
                    $height,
                ),
                null,
            ];

            $expected[sprintf('force pixel-conversion, with crop (%s)', $fn)] = [
                sprintf(
                    '<f:image src="EXT:svg_image_test/Resources/Public/Images/%1$s" fileExtension="png" width="%2$d" height="%3$d" crop="{\'default\':{\'cropArea\':{\'width\':%4$s,\'height\':%4$s,\'x\':%5$s,\'y\':%5$s},\'selectedRatio\':\'1:1\',\'focusArea\':null}}" />',
                    $fn,
                    $dimensionMap[$fn]['relativeCrop80Percent'][0], // width
                    $dimensionMap[$fn]['relativeCrop80Percent'][1], // height
                    $dimensionMap[$fn]['relativeCrop80Percent'][2], // crop-string width/height
                    $dimensionMap[$fn]['relativeCrop80Percent'][3] // crop-string offset left/top
                ),
                sprintf(
                    '@^<img src="(%s/csm_ImageViewHelperTest%d_.*\.png)" width="%d" height="%d" alt="" />$@',
                    $storageDirTemp,
                    $i,
                    $dimensionMap[$fn]['relativeCrop80Percent'][0],
                    $dimensionMap[$fn]['relativeCrop80Percent'][1],
                ),
                $dimensionMap[$fn]['relativeCrop80Percent'][4] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][5] . $dimensionMap[$fn]['relativeCrop80Percent'][0] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][1],
            ];
            //############################################################################

            //# SECTION 2: Referenced via UID, cropped via sys_file_reference (with overrides) ###
            // width/height is using the original dimensions, contained crop will be rendered within
            $expected[sprintf('using sys_file_reference crop (UID %d)', $fUid)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" width="%2$d" height="%3$d" />',
                    $fUid,
                    $width,
                    $height,
                ),
                sprintf(
                    '@^<img src="(%s/csm_FALImageViewHelperTest%d_.*\.svg)" width="%d" height="%d" alt="" />$@',
                    $storageDirFalTemp,
                    $i,
                    $width,
                    $height,
                ),
                $dimensionMap[$fn]['falCropString'], // Stored in sys_file_reference
                true,
            ];

            $expected[sprintf('using sys_file_reference crop, using maxWidth (60px, UID %d)', $fUid)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" maxWidth="60" />',
                    $fUid,
                ),
                sprintf(
                    '@^<img src="(%s/csm_FALImageViewHelperTest%d_.*\.svg)" width="60" height="%d" alt="" />$@',
                    $storageDirFalTemp,
                    $i,
                    $dimensionMap[$fn]['heightAtMaxWidth60px']
                ),
                $dimensionMap[$fn]['falCropString'], // Stored in sys_file_reference
                true,
            ];

            $expected[sprintf('empty crop (UID %d)', $fUid)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" width="%2$d" height="%3$d" crop="null" />',
                    $fUid,
                    $width,
                    $height,
                ),
                sprintf(
                    '@^<img src="(%s/%s)" width="%d" height="%d" alt="" />$@',
                    $storageDirFal,
                    'FAL' . $fn,
                    $width,
                    $height,
                ),
                null,
                false,
            ];

            $expected[sprintf('crop as array - forced 60px (UID %d)', $fUid)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" width="%2$d" height="%3$d" crop="{\'default\':{\'cropArea\':{\'width\':%4$s,\'height\':%4$s,\'x\':%5$s,\'y\':%5$s},\'selectedRatio\':\'1:1\',\'focusArea\':null}}" />',
                    $fUid,
                    $dimensionMap[$fn]['fixedCrop60px'][0], // width
                    $dimensionMap[$fn]['fixedCrop60px'][1], // height
                    $dimensionMap[$fn]['fixedCrop60px'][2], // crop-string width/height
                    $dimensionMap[$fn]['fixedCrop60px'][3] // crop-string offset left/top
                ),
                sprintf(
                    '@^<img src="(%s/csm_FALImageViewHelperTest%d_.*\.svg)" width="%d" height="%d" alt="" />$@',
                    $storageDirFalTemp,
                    $i,
                    $dimensionMap[$fn]['fixedCrop60px'][0],
                    $dimensionMap[$fn]['fixedCrop60px'][1],
                ),
                $dimensionMap[$fn]['fixedCrop60px'][4] . ' ' . $dimensionMap[$fn]['fixedCrop60px'][5] . ' ' . $dimensionMap[$fn]['fixedCrop60px'][0] . ' ' . $dimensionMap[$fn]['fixedCrop60px'][1],
            ];

            $expected[sprintf('crop as array - no width/height (UID %d)', $fUid)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" crop="{\'default\':{\'cropArea\':{\'width\':%2$s,\'height\':%2$s,\'x\':%3$s,\'y\':%3$s},\'selectedRatio\':\'1:1\',\'focusArea\':null}}" />',
                    $fUid,
                    $dimensionMap[$fn]['relativeCrop80Percent'][2], // crop-string width/height
                    $dimensionMap[$fn]['relativeCrop80Percent'][3] // crop-string offset left/top
                ),
                sprintf(
                    '@^<img src="(%s/csm_FALImageViewHelperTest%d_.*\.svg)" width="%d" height="%d" alt="" />$@',
                    $storageDirFalTemp,
                    $i,
                    $dimensionMap[$fn]['relativeCrop80Percent'][0],
                    $dimensionMap[$fn]['relativeCrop80Percent'][1],
                ),
                $dimensionMap[$fn]['relativeCrop80Percent'][4] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][5] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][0] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][1],
            ];

            $expected[sprintf('force pixel-conversion, sys_file_reference crop (UID %d)', $fUid)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" width="%2$d" height="%3$d" fileExtension="png" />',
                    $fUid,
                    $width,
                    $height,
                ),
                sprintf(
                    '@^<img src="(%s/csm_FALImageViewHelperTest%d_.*\.png)" width="%d" height="%d" alt="" />$@',
                    $storageDirFalTemp,
                    $i,
                    $width,
                    $height,
                ),
                null,
            ];

            $expected[sprintf('force pixel-conversion, with crop (UID %d)', $fUid)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" fileExtension="png" width="%2$d" height="%3$d" crop="{\'default\':{\'cropArea\':{\'width\':%4$s,\'height\':%4$s,\'x\':%5$s,\'y\':%5$s},\'selectedRatio\':\'1:1\',\'focusArea\':null}}" />',
                    $fUid,
                    $dimensionMap[$fn]['relativeCrop80Percent'][0], // width
                    $dimensionMap[$fn]['relativeCrop80Percent'][1], // height
                    $dimensionMap[$fn]['relativeCrop80Percent'][2], // crop-string width/height
                    $dimensionMap[$fn]['relativeCrop80Percent'][3] // crop-string offset left/top
                ),
                sprintf(
                    '@^<img src="(%s/csm_FALImageViewHelperTest%d_.*\.png)" width="%d" height="%d" alt="" />$@',
                    $storageDirFalTemp,
                    $i,
                    $dimensionMap[$fn]['relativeCrop80Percent'][0],
                    $dimensionMap[$fn]['relativeCrop80Percent'][1],
                ),
                $dimensionMap[$fn]['relativeCrop80Percent'][4] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][5] . $dimensionMap[$fn]['relativeCrop80Percent'][0] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][1],
            ];
            //############################################################################

            //# SECTION 3: Referenced via UID, uncropped in sys_file_reference ###
            $expected[sprintf('no crop (uncrop-UID %d)', $fUidUncropped)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" width="%2$d" height="%3$d" />',
                    $fUidUncropped,
                    $width,
                    $height,
                ),
                sprintf(
                    '@^<img src="(%s/%s)" width="%d" height="%d" alt="" />$@',
                    $storageDirFal,
                    'FAL' . $fn,
                    $width,
                    $height,
                ),
                null,
                false,
            ];

            $expected[sprintf('empty crop (uncrop-UID %d)', $fUidUncropped)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" width="%2$d" height="%3$d" crop="null" />',
                    $fUidUncropped,
                    $width,
                    $height,
                ),
                sprintf(
                    '@^<img src="(%s/%s)" width="%d" height="%d" alt="" />$@',
                    $storageDirFal,
                    'FAL' . $fn,
                    $width,
                    $height,
                ),
                null,
                false,
            ];

            $expected[sprintf('crop as array - forced 60px (uncrop-UID %d)', $fUidUncropped)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" width="%2$d" height="%3$d" crop="{\'default\':{\'cropArea\':{\'width\':%4$s,\'height\':%4$s,\'x\':%5$s,\'y\':%5$s},\'selectedRatio\':\'1:1\',\'focusArea\':null}}" />',
                    $fUidUncropped,
                    $dimensionMap[$fn]['fixedCrop60px'][0], // width
                    $dimensionMap[$fn]['fixedCrop60px'][1], // height
                    $dimensionMap[$fn]['fixedCrop60px'][2], // crop-string width/height
                    $dimensionMap[$fn]['fixedCrop60px'][3] // crop-string offset left/top
                ),
                sprintf(
                    '@^<img src="(%s/csm_FALImageViewHelperTest%d_.*\.svg)" width="%d" height="%d" alt="" />$@',
                    $storageDirFalTemp,
                    $i,
                    $dimensionMap[$fn]['fixedCrop60px'][0],
                    $dimensionMap[$fn]['fixedCrop60px'][1],
                ),
                $dimensionMap[$fn]['fixedCrop60px'][4] . ' ' . $dimensionMap[$fn]['fixedCrop60px'][5] . ' ' . $dimensionMap[$fn]['fixedCrop60px'][0] . ' ' . $dimensionMap[$fn]['fixedCrop60px'][1],
            ];

            $expected[sprintf('crop as array - no width/height (uncrop-UID %d)', $fUidUncropped)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" crop="{\'default\':{\'cropArea\':{\'width\':%2$s,\'height\':%2$s,\'x\':%3$s,\'y\':%3$s},\'selectedRatio\':\'1:1\',\'focusArea\':null}}" />',
                    $fUidUncropped,
                    $dimensionMap[$fn]['relativeCrop80Percent'][2], // crop-string width/height
                    $dimensionMap[$fn]['relativeCrop80Percent'][3] // crop-string offset left/top
                ),
                sprintf(
                    '@^<img src="(%s/csm_FALImageViewHelperTest%d_.*\.svg)" width="%d" height="%d" alt="" />$@',
                    $storageDirFalTemp,
                    $i,
                    $dimensionMap[$fn]['relativeCrop80Percent'][0],
                    $dimensionMap[$fn]['relativeCrop80Percent'][1],
                ),
                $dimensionMap[$fn]['relativeCrop80Percent'][4] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][5] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][0] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][1],
            ];

            $expected[sprintf('force pixel-conversion, no crop (uncrop-UID %d)', $fUidUncropped)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" width="%2$d" height="%3$d" fileExtension="png" />',
                    $fUidUncropped,
                    $width,
                    $height,
                ),
                sprintf(
                    '@^<img src="(%s/csm_FALImageViewHelperTest%d_.*\.png)" width="%d" height="%d" alt="" />$@',
                    $storageDirFalTemp,
                    $i,
                    $width,
                    $height,
                ),
                null,
            ];

            $expected[sprintf('force pixel-conversion, with crop (uncrop-UID %d)', $fUidUncropped)] = [
                sprintf(
                    '<f:image src="%1$d" treatIdAsReference="true" fileExtension="png" width="%2$d" height="%3$d" crop="{\'default\':{\'cropArea\':{\'width\':%4$s,\'height\':%4$s,\'x\':%5$s,\'y\':%5$s},\'selectedRatio\':\'1:1\',\'focusArea\':null}}" />',
                    $fUidUncropped,
                    $dimensionMap[$fn]['relativeCrop80Percent'][0], // width
                    $dimensionMap[$fn]['relativeCrop80Percent'][1], // height
                    $dimensionMap[$fn]['relativeCrop80Percent'][2], // crop-string width/height
                    $dimensionMap[$fn]['relativeCrop80Percent'][3] // crop-string offset left/top
                ),
                sprintf(
                    '@^<img src="(%s/csm_FALImageViewHelperTest%d_.*\.png)" width="%d" height="%d" alt="" />$@',
                    $storageDirFalTemp,
                    $i,
                    $dimensionMap[$fn]['relativeCrop80Percent'][0],
                    $dimensionMap[$fn]['relativeCrop80Percent'][1],
                ),
                $dimensionMap[$fn]['relativeCrop80Percent'][4] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][5] . $dimensionMap[$fn]['relativeCrop80Percent'][0] . ' ' . $dimensionMap[$fn]['relativeCrop80Percent'][1],
            ];
            //############################################################################
        }

        // Iterate the whole array, utilize and test f:uri.image with the same inputs.
        // This is done if in the future the two viewHelpers may diverge from each other,
        // to still perform all tests properly.
        $uriImageCopy = $expected;
        foreach ($uriImageCopy as $expectedKey => $imageViewHelperGreatExpectations) {
            // Switch and bait execution string
            $imageViewHelperGreatExpectations[0] = str_replace('<f:image', '<f:uri.image', $imageViewHelperGreatExpectations[0]);
            // ... and expectation string
            $imageViewHelperGreatExpectations[1] = '@^' . preg_replace('@^.+src="(.+)".+$@imsU', '\1', $imageViewHelperGreatExpectations[1]) . '$@';

            // ... and append to the main data provider
            $expected[$expectedKey . ' (f:uri.image)'] = $imageViewHelperGreatExpectations;
        }

        return $expected;
    }

    #[DataProvider('renderReturnsExpectedMarkupDataProvider')]
    #[Test]
    public function renderReturnsExpectedMarkup(string $template, string $expected, ?string $cropResult, bool $expectProcessedFile = true): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $actual = (new TemplateView($context))->render();
        self::assertMatchesRegularExpression($expected, $actual);

        $dumpTables = [
            'sys_file_processedfile' => 1,
        ];

        foreach ($dumpTables as $dumpTable => $expectedRecords) {
            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($dumpTable);
            $rows =
                $queryBuilder
                    ->select('*')
                    ->from($dumpTable)
                    ->executeQuery()
                    ->fetchAllAssociative();

            self::assertEquals(count($rows), $expectedRecords, sprintf('Expected post-conversion database records in %s do not match.', $dumpTable));

            if ($dumpTable === 'sys_file_processedfile' && $expectProcessedFile) {
                // Only SVGs count
                if (str_ends_with($rows[0]['identifier'], '.svg')) {
                    $this->verifySvg($rows[0], $cropResult);
                }
            }
        }
    }

    protected function verifySvg(array $file, ?string $cropResult)
    {
        if ($file['storage'] == 1) {
            $dir = Environment::getPublicPath() . '/fileadmin';
        } else {
            $dir = Environment::getPublicPath();
        }

        $svg = new \DOMDocument();
        $svg->load($dir . $file['identifier']);

        self::assertEquals($file['width'], $svg->documentElement->getAttribute('width'), 'SVG "width" mismatch.');
        self::assertEquals($file['height'], $svg->documentElement->getAttribute('height'), 'SVG "height" mismatch.');
        self::assertEquals($cropResult, $svg->documentElement->getAttribute('viewBox'), 'SVG "viewBox" (crop) mismatch.');
        unlink($dir . $file['identifier']);
    }

}
