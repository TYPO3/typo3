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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\GraphicsCanvas;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GraphicsCanvasTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!GraphicsCanvas::isAvailable()) {
            self::markTestSkipped('GD extension is not available.');
        }
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function rgb(GraphicsCanvas $canvas, int $x, int $y): array
    {
        $color = $canvas->getPixel($x, $y);
        return [
            ($color >> 16) & 0xff,
            ($color >> 8) & 0xff,
            $color & 0xff,
        ];
    }

    /**
     * Names a pixel by which channels are switched on, so output that went
     * through dithering can be pinned without depending on exact values.
     */
    private static function colourName(GraphicsCanvas $canvas, int $x, int $y): string
    {
        [$r, $g, $b] = array_map(static fn(int $channel): bool => $channel > 127, self::rgb($canvas, $x, $y));
        return match (true) {
            $r && $g && $b => 'white',
            $r && $g => 'yellow',
            $r && $b => 'magenta',
            $g && $b => 'cyan',
            $r => 'red',
            $g => 'green',
            $b => 'blue',
            default => 'black',
        };
    }

    /**
     * 8x8 canvas of mid grey with a brighter square in the middle. Convolution
     * filters need midtones and an edge to show any effect: on the saturated
     * quadrant canvas every kernel clamps straight back to the input value.
     */
    private static function createSoftEdgeCanvas(): GraphicsCanvas
    {
        $canvas = GraphicsCanvas::create(8, 8, 100, 100, 100);
        $canvas->fillRect(2, 2, 4, 4, $canvas->allocateColor(160, 160, 160));
        return $canvas;
    }

    /**
     * All pixels of a canvas as one comparable value.
     */
    private static function fingerprint(GraphicsCanvas $canvas): string
    {
        $pixels = [];
        for ($y = 0; $y < $canvas->height(); $y++) {
            for ($x = 0; $x < $canvas->width(); $x++) {
                $pixels[] = $canvas->getPixel($x, $y);
            }
        }
        return implode(',', $pixels);
    }

    /**
     * 8x8 canvas with four solid quadrants: red (top-left), green
     * (top-right), blue (bottom-left), white (bottom-right). Used to pin
     * geometric operations (flip, flop, resize) against known pixels.
     */
    private static function createQuadrantCanvas(): GraphicsCanvas
    {
        $canvas = GraphicsCanvas::create(8, 8, 0, 0, 0);
        $canvas->fillRect(0, 0, 4, 4, $canvas->allocateColor(255, 0, 0));
        $canvas->fillRect(4, 0, 4, 4, $canvas->allocateColor(0, 255, 0));
        $canvas->fillRect(0, 4, 4, 4, $canvas->allocateColor(0, 0, 255));
        $canvas->fillRect(4, 4, 4, 4, $canvas->allocateColor(255, 255, 255));
        return $canvas;
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function formatSupportDataProvider(): array
    {
        return [
            'gif' => ['gif', IMG_GIF],
            'jpg' => ['jpg', IMG_JPEG],
            'jpeg' => ['jpeg', IMG_JPEG],
            'png' => ['png', IMG_PNG],
            'webp' => ['webp', IMG_WEBP],
            'avif' => ['avif', IMG_AVIF],
        ];
    }

    #[DataProvider('formatSupportDataProvider')]
    #[Test]
    public function formatSupportFollowsTheImageTypesOfTheGdBuild(string $extension, int $imageType): void
    {
        $supported = (bool)(imagetypes() & $imageType);

        self::assertSame($supported, GraphicsCanvas::canRead($extension));
        self::assertSame($supported, GraphicsCanvas::canWrite($extension));
    }

    #[Test]
    public function formatSupportIgnoresTheCaseOfTheExtension(): void
    {
        self::assertSame(GraphicsCanvas::canRead('png'), GraphicsCanvas::canRead('PNG'));
        self::assertSame(GraphicsCanvas::canWrite('jpeg'), GraphicsCanvas::canWrite('JPEG'));
    }

    /**
     * A GD build can report IMG_BMP while the canvas has no decoder or
     * encoder for it, so the format list is the canvas' own and not
     * everything imagetypes() answers.
     */
    #[Test]
    public function formatSupportRejectsExtensionsTheCanvasCannotHandle(): void
    {
        self::assertFalse(GraphicsCanvas::canRead('bmp'));
        self::assertFalse(GraphicsCanvas::canWrite('bmp'));
        self::assertFalse(GraphicsCanvas::canRead('tiff'));
        self::assertFalse(GraphicsCanvas::canWrite(''));
    }

    #[Test]
    public function createProducesCanvasWithRequestedDimensionsAndFillColour(): void
    {
        $canvas = GraphicsCanvas::create(32, 16, 10, 20, 30);

        self::assertSame(32, $canvas->width());
        self::assertSame(16, $canvas->height());
        self::assertSame([10, 20, 30], self::rgb($canvas, 0, 0));
        self::assertSame([10, 20, 30], self::rgb($canvas, 31, 15));
    }

    #[Test]
    public function fillRectPaintsRequestedRegionAndLeavesOutsideUntouched(): void
    {
        $canvas = GraphicsCanvas::create(10, 10, 0, 0, 0);
        $red = $canvas->allocateColor(255, 0, 0);
        $canvas->fillRect(2, 2, 4, 4, $red);

        self::assertSame([255, 0, 0], self::rgb($canvas, 3, 3));
        self::assertSame([255, 0, 0], self::rgb($canvas, 5, 5));
        self::assertSame([0, 0, 0], self::rgb($canvas, 1, 1));
        self::assertSame([0, 0, 0], self::rgb($canvas, 6, 6));
    }

    #[Test]
    public function setPixelAndGetPixelRoundTripPreservesChannels(): void
    {
        $canvas = GraphicsCanvas::create(4, 4, 0, 0, 0);
        $canvas->setPixel(1, 2, $canvas->allocateColor(123, 45, 67));

        self::assertSame([123, 45, 67], self::rgb($canvas, 1, 2));
    }

    #[Test]
    public function grayscaleCollapsesChannelsToEqualValues(): void
    {
        $canvas = GraphicsCanvas::create(2, 2, 200, 50, 10);
        $canvas->grayscale();

        [$r, $g, $b] = self::rgb($canvas, 0, 0);
        self::assertSame($r, $g);
        self::assertSame($g, $b);
    }

    #[Test]
    public function cropProducesRequestedRectangle(): void
    {
        $canvas = GraphicsCanvas::create(10, 10, 0, 0, 0);
        $canvas->crop(2, 2, 4, 3);

        self::assertSame(4, $canvas->width());
        self::assertSame(3, $canvas->height());
    }

    #[Test]
    public function solarizeWithZeroPercentInvertsEveryNonBlackPixel(): void
    {
        // Threshold 0 means "invert any channel > 0".
        $canvas = GraphicsCanvas::create(2, 2, 100, 100, 100);
        $canvas->solarize(0);

        self::assertSame([155, 155, 155], self::rgb($canvas, 0, 0));
    }

    #[Test]
    public function solarizeWithOneHundredPercentIsClampedAndLeavesPixelsUntouched(): void
    {
        // Range is clamped to 0..99; at the top end no channel exceeds the
        // threshold, so the image must come back unchanged.
        $canvas = GraphicsCanvas::create(2, 2, 100, 150, 200);
        $canvas->solarize(100);

        self::assertSame([100, 150, 200], self::rgb($canvas, 0, 0));
    }

    #[Test]
    public function solarizeAtFiftyPercentTreatsValueAsPercentageNotQuantum(): void
    {
        // 50% corresponds to a threshold of 127 on an 8-bit channel:
        // pixels at 100 stay, pixels at 200 get inverted to 55.
        // This is the behaviour the old "-solarize N" IM shell-out did
        // not guarantee because it read N as a quantum-scale threshold.
        $canvas = GraphicsCanvas::create(2, 1, 100, 200, 100);
        $canvas->solarize(50);

        self::assertSame([100, 55, 100], self::rgb($canvas, 0, 0));
    }

    #[Test]
    public function shearAtNinetyDegreesIsClampedAndStillProducesPositiveGeometry(): void
    {
        // The old IM path emitted `-shear 90` which diverges through
        // tan(90°) and produced no usable image. The canvas must clamp
        // to ±85 so the output keeps finite, positive dimensions.
        $canvas = GraphicsCanvas::create(10, 10, 0, 0, 0);
        $canvas->shear(90);

        self::assertGreaterThan(0, $canvas->width());
        self::assertSame(10, $canvas->height());
    }

    #[Test]
    public function saveToFileAndLoadFileRoundTripPreservesDimensionsAndColour(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'gc') . '.png';
        try {
            $canvas = GraphicsCanvas::create(12, 7, 200, 100, 50);
            self::assertTrue($canvas->saveToFile($path, 'png'));

            $loaded = GraphicsCanvas::loadFile($path);
            self::assertNotNull($loaded);
            self::assertSame(12, $loaded->width());
            self::assertSame(7, $loaded->height());
            self::assertSame([200, 100, 50], self::rgb($loaded, 0, 0));
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function loadFileReturnsNullForUnsupportedExtension(): void
    {
        self::assertNull(GraphicsCanvas::loadFile('/tmp/whatever.xyz'));
    }

    #[Test]
    public function gammaBrightensAboveOneAndDarkensBelowOne(): void
    {
        $brightened = GraphicsCanvas::create(1, 1, 100, 100, 100);
        $brightened->gamma(1.0, 2.0);
        $darkened = GraphicsCanvas::create(1, 1, 100, 100, 100);
        $darkened->gamma(1.0, 0.5);

        self::assertGreaterThan(100, self::rgb($brightened, 0, 0)[0]);
        self::assertLessThan(100, self::rgb($darkened, 0, 0)[0]);
    }

    #[Test]
    public function colorsKeepsPixelsReadableForFollowingOperations(): void
    {
        $canvas = self::createQuadrantCanvas();
        $canvas->colors(4);

        // Quantising must not leave a palette image behind: getPixel() would then
        // return palette indices (0-3 for four colours), and every following pixel
        // operation — a chained EFFECT keyword, ADJUST, a mask composite — would
        // read those indices as colours.
        self::assertSame('red', self::colourName($canvas, 1, 1));
        self::assertSame('green', self::colourName($canvas, 6, 1));
        self::assertSame('blue', self::colourName($canvas, 1, 6));
        self::assertSame('white', self::colourName($canvas, 6, 6));
    }

    /**
     * @return array<string, array{0: \Closure}>
     */
    public static function geometryPreservingFilterDataProvider(): array
    {
        return [
            'blur' => [static fn(GraphicsCanvas $canvas): GraphicsCanvas => $canvas->blur(2)],
            'sharpen' => [static fn(GraphicsCanvas $canvas): GraphicsCanvas => $canvas->sharpen(50)],
            'emboss' => [static fn(GraphicsCanvas $canvas): GraphicsCanvas => $canvas->emboss()],
        ];
    }

    #[DataProvider('geometryPreservingFilterDataProvider')]
    #[Test]
    public function filterKeepsTheCanvasSizeAndChangesPixels(\Closure $filter): void
    {
        $canvas = self::createSoftEdgeCanvas();
        $before = self::fingerprint($canvas);
        $filter($canvas);

        self::assertSame(8, $canvas->width());
        self::assertSame(8, $canvas->height());
        // Individual pixels may come back unchanged, so the whole canvas is
        // compared rather than a hand-picked probe pixel.
        self::assertNotSame($before, self::fingerprint($canvas));
    }

    #[Test]
    public function rotateByNinetyDegreesSwapsWidthAndHeight(): void
    {
        $canvas = GraphicsCanvas::create(10, 5, 0, 0, 0);
        $canvas->rotate(90);

        self::assertSame(5, $canvas->width());
        self::assertSame(10, $canvas->height());
    }

    #[Test]
    public function waveGrowsCanvasHeightByTwiceTheAmplitude(): void
    {
        $canvas = GraphicsCanvas::create(8, 8, 255, 255, 255);
        $canvas->wave(3, 30);

        self::assertSame(8, $canvas->width());
        self::assertSame(14, $canvas->height());
    }

    #[Test]
    public function invertOnQuadrantCanvasNegatesEveryChannelPerQuadrant(): void
    {
        $canvas = self::createQuadrantCanvas();
        $canvas->invert();

        self::assertSame([0, 255, 255], self::rgb($canvas, 0, 0));      // red  -> cyan
        self::assertSame([255, 0, 255], self::rgb($canvas, 7, 0));      // green-> magenta
        self::assertSame([255, 255, 0], self::rgb($canvas, 0, 7));      // blue -> yellow
        self::assertSame([0, 0, 0], self::rgb($canvas, 7, 7));          // white-> black
    }

    #[Test]
    public function flipOnQuadrantCanvasSwapsTopAndBottomRows(): void
    {
        $canvas = self::createQuadrantCanvas();
        $canvas->flip();

        self::assertSame([0, 0, 255], self::rgb($canvas, 0, 0));        // blue now top-left
        self::assertSame([255, 255, 255], self::rgb($canvas, 7, 0));    // white now top-right
        self::assertSame([255, 0, 0], self::rgb($canvas, 0, 7));        // red now bottom-left
        self::assertSame([0, 255, 0], self::rgb($canvas, 7, 7));        // green now bottom-right
    }

    #[Test]
    public function flopOnQuadrantCanvasSwapsLeftAndRightColumns(): void
    {
        $canvas = self::createQuadrantCanvas();
        $canvas->flop();

        self::assertSame([0, 255, 0], self::rgb($canvas, 0, 0));        // green now top-left
        self::assertSame([255, 0, 0], self::rgb($canvas, 7, 0));        // red now top-right
        self::assertSame([255, 255, 255], self::rgb($canvas, 0, 7));    // white now bottom-left
        self::assertSame([0, 0, 255], self::rgb($canvas, 7, 7));        // blue now bottom-right
    }

    #[Test]
    public function resizeToOnQuadrantCanvasPreservesQuadrantLayoutAtSmallerSize(): void
    {
        $canvas = self::createQuadrantCanvas();
        $canvas->resizeTo(4, 4);

        self::assertSame(4, $canvas->width());
        self::assertSame(4, $canvas->height());
        self::assertSame([255, 0, 0], self::rgb($canvas, 0, 0));
        self::assertSame([0, 255, 0], self::rgb($canvas, 3, 0));
        self::assertSame([0, 0, 255], self::rgb($canvas, 0, 3));
        self::assertSame([255, 255, 255], self::rgb($canvas, 3, 3));
    }
}
