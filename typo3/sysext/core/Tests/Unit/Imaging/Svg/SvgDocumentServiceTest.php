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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging\Svg;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocument;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocumentFactory;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocumentService;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SvgDocumentServiceTest extends UnitTestCase
{
    public static function resolvesDimensionsDataProvider(): array
    {
        return [
            'viewBox only' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 436 177"/>',
                436,
                177,
            ],
            'viewBox with comma separators' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0,0,320,240"/>',
                320,
                240,
            ],
            'viewBox with fractional values is truncated' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 168.4 940.7 724.2"/>',
                940,
                724,
            ],
            'numeric width/height override viewBox' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="200" height="300"/>',
                200,
                300,
            ],
            'percentage width/height yield to viewBox' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200" width="100%" height="100%"/>',
                400,
                200,
            ],
            'unit-suffixed width/height yield to viewBox' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 200" width="10mm" height="20mm"/>',
                100,
                200,
            ],
            'single non-numeric width yields to viewBox for that axis only' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 200" width="10mm"/>',
                100,
                200,
            ],
            'unit-suffixed width/height are unit-stripped when no viewBox' => [
                '<svg xmlns="http://www.w3.org/2000/svg" width="640px" height="480px"/>',
                640,
                480,
            ],
            'percentage width/height without viewBox fall back to stripped int' => [
                '<svg xmlns="http://www.w3.org/2000/svg" width="50%" height="75%"/>',
                50,
                75,
            ],
            'plain numeric width/height without viewBox' => [
                '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="64"/>',
                128,
                64,
            ],
            'no attributes falls back to 64x64 default' => [
                '<svg xmlns="http://www.w3.org/2000/svg"/>',
                64,
                64,
            ],
            'viewBox height missing still yields defaults for missing side' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50"/>',
                50,
                64,
            ],
            'svg with DOCTYPE and entities (regression for ImageInfoTest case)' => [
                '<?xml version="1.0" encoding="utf-8"?>
                <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
                <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 436 177"/>',
                436,
                177,
            ],
        ];
    }

    #[DataProvider('resolvesDimensionsDataProvider')]
    #[Test]
    public function resolvesDimensions(string $svg, int $expectedWidth, int $expectedHeight): void
    {
        $dimensions = (new SvgDocumentService())->getDimensions($this->loadString($svg));

        self::assertSame($expectedWidth, $dimensions->getWidth());
        self::assertSame($expectedHeight, $dimensions->getHeight());
    }

    #[Test]
    public function cropScaleWrapsSourceSvgWithViewBoxAndTargetDimensions(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 941 724" width="941" height="724"><path d="M0 0"/></svg>';
        $processed = (new SvgDocumentService())->cropScale(
            $this->loadString($svg),
            new Area(50.0, 50.0, 640.0, 480.0),
            new ImageDimension(320, 240),
        );

        $root = $processed->documentElement;
        self::assertSame('svg', $root->localName);
        self::assertSame('50 50 640 480', $root->getAttribute('viewBox'));
        self::assertSame('320', $root->getAttribute('width'));
        self::assertSame('240', $root->getAttribute('height'));

        // Inner <svg> is preserved.
        $inner = $root->firstElementChild;
        self::assertNotNull($inner);
        self::assertSame('svg', $inner->localName);
        self::assertSame('941', $inner->getAttribute('width'));
        self::assertSame('724', $inner->getAttribute('height'));
    }

    #[Test]
    public function cropScaleInjectsIntrinsicDimensionsWhenSourceHasOnlyViewBox(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200"><path d="M0 0"/></svg>';
        $processed = (new SvgDocumentService())->cropScale(
            $this->loadString($svg),
            new Area(0.0, 0.0, 100.0, 100.0),
            new ImageDimension(50, 50),
        );

        $inner = $processed->documentElement->firstElementChild;
        self::assertNotNull($inner);
        self::assertSame('400', $inner->getAttribute('width'));
        self::assertSame('200', $inner->getAttribute('height'));
        self::assertSame('true', $inner->getAttribute('data-manipulated-width'));
        self::assertSame('true', $inner->getAttribute('data-manipulated-height'));
    }

    #[Test]
    public function cropScalePropagatesPreserveAspectRatio(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" preserveAspectRatio="xMinYMax meet"/>';
        $processed = (new SvgDocumentService())->cropScale(
            $this->loadString($svg),
            new Area(0.0, 0.0, 50.0, 50.0),
            new ImageDimension(25, 25),
        );

        self::assertSame('xMinYMax meet', $processed->documentElement->getAttribute('preserveAspectRatio'));
    }

    #[Test]
    public function cropScaleReturnsDocumentWithTargetDimensions(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"/>';
        $document = (new SvgDocumentService())->cropScale(
            $this->loadString($svg),
            new Area(0.0, 0.0, 10.0, 10.0),
            new ImageDimension(5, 5),
        );

        self::assertSame('5', $document->documentElement->getAttribute('width'));
        self::assertSame('5', $document->documentElement->getAttribute('height'));
    }

    #[Test]
    public function toXmlReturnsSerializedDocumentWithoutXmlProlog(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"/>';

        self::assertSame($svg, (new SvgDocumentService())->toXml($this->loadString($svg)));
    }

    #[Test]
    public function toInlineMarkupStripsXmlPrologNamespaceAndVersion(): void
    {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 10 10"/>';

        self::assertSame(
            '<svg viewBox="0 0 10 10"/>',
            (new SvgDocumentService())->toInlineMarkup($this->loadString($svg)),
        );
    }

    #[Test]
    public function toInlineMarkupSynthesizesViewBoxFromWidthHeight(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="64"/>';

        self::assertSame(
            '<svg width="128" height="64" viewBox="0 0 128 64"/>',
            (new SvgDocumentService())->toInlineMarkup($this->loadString($svg)),
        );
    }

    /**
     * Inline SVG embedded in an HTML5 document drops the redundant root
     * xmlns and the legacy version attribute, and synthesizes a viewBox
     * from width/height so the markup scales with CSS.
     */
    #[Test]
    public function inlineMarkupIsReadyForHtml5EmbeddingWhenOnlyWidthHeightArePresent(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="128" height="64">'
            . '<rect width="128" height="64"/></svg>';

        self::assertSame(
            '<svg width="128" height="64" viewBox="0 0 128 64"><rect width="128" height="64"/></svg>',
            (new SvgDocumentService())->toInlineMarkup($this->loadString($svg)),
        );
    }

    #[Test]
    public function toInlineMarkupDoesNotMutateOriginalDocument(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 10 10"/>';
        $service = new SvgDocumentService();
        $document = $this->loadString($svg);
        $service->toInlineMarkup($document);

        self::assertSame($svg, $service->toXml($document));
    }

    private function loadString(string $svg): SvgDocument
    {
        return (new SvgDocumentFactory(new SvgSanitizer()))->fromString($svg);
    }
}
