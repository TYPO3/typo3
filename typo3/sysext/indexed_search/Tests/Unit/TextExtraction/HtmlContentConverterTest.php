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

namespace TYPO3\CMS\IndexedSearch\Tests\Unit\TextExtraction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
use TYPO3\CMS\IndexedSearch\TextExtraction\HtmlContentConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class HtmlContentConverterTest extends UnitTestCase
{
    #[Test]
    public function charsetIsReadFromContentTypeMetaTag(): void
    {
        $subject = new HtmlContentConverter();
        self::assertSame('windows-1252', $subject->getCharset('<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">'));
        self::assertSame('', $subject->getCharset('<meta charset="utf-8">'));
    }

    #[Test]
    public function contentIsConvertedToUtf8AndEntitiesAreDecoded(): void
    {
        $subject = new HtmlContentConverter();
        $latin1 = mb_convert_encoding('<meta http-equiv="content-type" content="text/html;charset=ISO-8859-1">Käse &amp; Brot', 'ISO-8859-1', 'UTF-8');
        self::assertStringEndsWith('Käse & Brot', $subject->toUtf8($latin1));
        self::assertSame('Käse', $subject->toUtf8(mb_convert_encoding('Käse', 'ISO-8859-1', 'UTF-8'), 'ISO-8859-1'));
    }

    #[Test]
    public function unknownCharsetKeepsContent(): void
    {
        self::assertSame('abc', new HtmlContentConverter()->toUtf8('abc', 'no-such-charset'));
    }

    #[Test]
    public function splitReadsTitleMetaTagsAndBody(): void
    {
        $html = '<html><head><title>My page</title>'
            . '<meta name="Keywords" content="one,two , three">'
            . '<meta name="description" content="A &amp; B">'
            . '<meta name="robots" content="noindex"></head>'
            . '<body><h1>Head</h1><p>Text</p><script type="text/javascript">alert(1);</script><style>p{color:red}</style>tail</body></html>';
        $result = new HtmlContentConverter()->split($html);
        self::assertEquals(new ExtractedText('Head  Text tail', 'My page', 'A & B', 'one, two, three'), $result);
    }

    #[Test]
    public function splitDropsLabelPrefixOfTitle(): void
    {
        $result = new HtmlContentConverter()->split('<head><title>Site: The Title</title></head><body>x</body>');
        self::assertSame('The Title', $result->title);
    }

    #[Test]
    public function splitConcatenatesMultipleMetaTags(): void
    {
        $result = new HtmlContentConverter()->split('<head><meta name="keywords" content="a"><meta name="keywords" content="b"></head><body>x</body>');
        self::assertSame('a,b', $result->keywords);
    }

    #[Test]
    public function splitWithoutBodyTagUsesContentWithoutHead(): void
    {
        $result = new HtmlContentConverter()->split('<head><title>T</title></head><p>Only paragraph</p>');
        self::assertSame('T', $result->title);
        self::assertSame('Only paragraph', $result->text);
    }

    #[Test]
    public function splitOnlyKeepsBodyBetweenSearchMarkers(): void
    {
        $html = '<head><title>T</title></head><body>Navigation'
            . '<!--TYPO3SEARCH_begin-->First<!--TYPO3SEARCH_end-->Sidebar'
            . '<!-- TYPO3SEARCH_begin -->Second<!-- TYPO3SEARCH_end -->Footer</body>';
        $result = new HtmlContentConverter()->split($html);
        self::assertSame('First Second', $result->text);
    }

    #[Test]
    public function splitKeepsBodyBeforeEndMarkerWithoutBeginMarker(): void
    {
        $result = new HtmlContentConverter()->split('<body>Content<!--TYPO3SEARCH_end-->Footer</body>');
        self::assertSame('Content', $result->text);
    }

    #[Test]
    public function splitOfPlainTextKeepsText(): void
    {
        $result = new HtmlContentConverter()->split('just text');
        self::assertSame('just text', $result->text);
        self::assertSame('', $result->title);
    }
}
