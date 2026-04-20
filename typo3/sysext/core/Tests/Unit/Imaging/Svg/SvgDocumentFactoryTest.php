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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\Exception\InvalidSvgException;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocument;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocumentFactory;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SvgDocumentFactoryTest extends UnitTestCase
{
    #[Test]
    public function fromStringLoadsValidSvg(): void
    {
        $document = (new SvgDocumentFactory(new SvgSanitizer()))
            ->fromString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"/>');
        self::assertSame('svg', $document->documentElement->localName);
    }

    #[Test]
    public function fromStringThrowsOnEmptyInput(): void
    {
        $this->expectException(InvalidSvgException::class);
        (new SvgDocumentFactory(new SvgSanitizer()))->fromString('   ');
    }

    #[Test]
    public function fromStringThrowsOnInvalidXml(): void
    {
        $this->expectException(InvalidSvgException::class);
        (new SvgDocumentFactory(new SvgSanitizer()))->fromString('this is not xml');
    }

    #[Test]
    public function fromFileThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidSvgException::class);
        (new SvgDocumentFactory(new SvgSanitizer()))
            ->fromFile('/nonexistent/path/' . uniqid('svg_', true) . '.svg');
    }

    #[Test]
    public function sanitizedRemovesScriptElements(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
            . '<script>alert(1)</script><path d="M0 0"/></svg>';

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path d="M0 0"/></svg>',
            $this->serialize((new SvgDocumentFactory(new SvgSanitizer()))->fromStringAndSanitize($svg)),
        );
    }

    #[Test]
    public function sanitizedKeepsLinksByDefault(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
            . '<a href="https://example.com"><rect width="10" height="10"/></a></svg>';

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><a href="https://example.com"><rect width="10" height="10"/></a></svg>',
            $this->serialize((new SvgDocumentFactory(new SvgSanitizer()))->fromStringAndSanitize($svg)),
        );
    }

    #[Test]
    public function sanitizedDropsAnchorSubtreeWhenLinkRemovalRequested(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
            . '<a href="https://example.com"><rect width="10" height="10"/></a></svg>';

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"/>',
            $this->serialize((new SvgDocumentFactory(new SvgSanitizer()))->fromStringAndSanitize($svg, true)),
        );
    }

    #[Test]
    public function sanitizedRemovesEventHandlerAttributes(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
            . '<rect onclick="alert(1)" width="10" height="10"/></svg>';

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10"/></svg>',
            $this->serialize((new SvgDocumentFactory(new SvgSanitizer()))->fromStringAndSanitize($svg)),
        );
    }

    /**
     * Remote references (href / xlink:href pointing at external URLs)
     * are a known SVG exfiltration vector and must be dropped.
     */
    #[Test]
    public function sanitizedStripsRemoteUseReferences(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 10 10">'
            . '<use href="http://example.com/evil.svg#x"/>'
            . '<use xlink:href="https://example.com/evil.svg#y"/>'
            . '<rect width="10" height="10"/></svg>';

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 10 10"><rect width="10" height="10"/></svg>',
            $this->serialize((new SvgDocumentFactory(new SvgSanitizer()))->fromStringAndSanitize($svg)),
        );
    }

    private function serialize(SvgDocument $document): string
    {
        return (string)$document->saveXML($document->documentElement);
    }
}
