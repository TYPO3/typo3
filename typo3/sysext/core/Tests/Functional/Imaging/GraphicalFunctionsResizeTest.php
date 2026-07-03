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

namespace TYPO3\CMS\Core\Tests\Functional\Imaging;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for GraphicalFunctions::resize(), focusing on crop handling
 * and dimension rounding when crop areas carry fractional pixel values.
 */
final class GraphicalFunctionsResizeTest extends FunctionalTestCase
{
    // Dimensions of the PNG fixture image used by all tests in this class.
    private const FIXTURE_IMAGE_WIDTH = 640;
    private const FIXTURE_IMAGE_HEIGHT = 480;

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Imaging/Fixtures/GraphicalFunctionsResize.png' => 'fileadmin/user_upload/GraphicalFunctionsResize.png',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/GraphicalFunctionsResizeTest.csv');
    }

    /**
     * @return array<string, array{fileReferenceUid: int, expectedWidth: int, expectedHeight: int}>
     */
    public static function resizeWithCropFromFileReferenceDataProvider(): array
    {
        return [
            // Relative crop 0.5 × 0.5 on a 640×480 image yields an exact 320×240 crop area,
            // which must be returned as clean integer output dimensions.
            'clean integer crop dimensions produce exact pixel output' => [
                'fileReferenceUid' => 1,
                'expectedWidth' => 320,
                'expectedHeight' => 240,
            ],
            // Relative crop 0.5086612656 × 0.0029166667 on a 640×480 image yields an absolute
            // crop area of approximately 325.54321 × 1.4 pixels — a scenario analogous to the
            // extreme floating-point values (e.g. 325.54321 × 1.14e-13) that can arise when
            // relative crop coordinates are converted to absolute pixel dimensions.
            // The fractional width rounds up to 326 and the sub-pixel height must be rounded
            // to at least 1, yielding a 326×1 output.
            'fractional crop dimensions are rounded to nearest integer with minimum of 1px' => [
                'fileReferenceUid' => 2,
                'expectedWidth' => 326,
                'expectedHeight' => 1,
            ],
            // Relative x-offset 1.78125e-16 on a 640-pixel-wide image yields the absolute offset
            // 640 × 1.78125e-16 = 1.14e-13, which PHP serialises to the string "1.14E-13" (scientific
            // notation) when it is interpolated into the ImageMagick crop geometry string.
            // This exercises the code path where at least one dimension in the -crop argument
            // carries an E-notation exponent, and verifies that the output dimensions are still
            // computed correctly (326×240).
            'sub-pixel x-offset serialised to scientific notation does not corrupt output dimensions' => [
                'fileReferenceUid' => 3,
                'expectedWidth' => 326,
                'expectedHeight' => 240,
            ],
        ];
    }

    #[DataProvider('resizeWithCropFromFileReferenceDataProvider')]
    #[Test]
    public function resizeWithCropFromFileReferenceReturnsExpectedDimensions(
        int $fileReferenceUid,
        int $expectedWidth,
        int $expectedHeight
    ): void {
        $graphicalFunctions = $this->get(GraphicalFunctions::class);
        if (!$graphicalFunctions->isProcessingEnabled()) {
            self::markTestSkipped('Image processor (ImageMagick/GraphicsMagick) is not enabled.');
        }

        $sourceFile = Environment::getPublicPath() . '/fileadmin/user_upload/GraphicalFunctionsResize.png';

        $row = $this->getConnectionPool()
            ->getConnectionForTable('sys_file_reference')
            ->select(['crop'], 'sys_file_reference', ['uid' => $fileReferenceUid])
            ->fetchAssociative();

        // Parse the sys_file_reference crop string and convert the relative crop area
        // (stored as 0–1 fractions) to absolute pixel coordinates for the fixture image.
        $relativeArea = CropVariantCollection::create((string)($row['crop'] ?? ''))->getCropArea('default');
        $absoluteArea = new Area(
            $relativeArea->getOffsetLeft() * self::FIXTURE_IMAGE_WIDTH,
            $relativeArea->getOffsetTop() * self::FIXTURE_IMAGE_HEIGHT,
            $relativeArea->getWidth() * self::FIXTURE_IMAGE_WIDTH,
            $relativeArea->getHeight() * self::FIXTURE_IMAGE_HEIGHT,
        );

        $result = $graphicalFunctions->resize($sourceFile, 'png', 0, 0, '', ['crop' => $absoluteArea]);

        self::assertNotNull($result);
        self::assertSame($expectedWidth, $result->getWidth());
        self::assertSame($expectedHeight, $result->getHeight());
    }
}
