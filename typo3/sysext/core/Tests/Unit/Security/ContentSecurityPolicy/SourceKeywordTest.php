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

namespace TYPO3\CMS\Core\Tests\Unit\Security\ContentSecurityPolicy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SourceKeywordTest extends UnitTestCase
{
    public static function isApplicableDataProvider(): \Generator
    {
        yield 'self in default-src' => [SourceKeyword::self, Directive::ImgSrc, true];
        yield 'self in img-src' => [SourceKeyword::self, Directive::ImgSrc, true];

        yield 'reportSample in default-src' => [SourceKeyword::reportSample, Directive::DefaultSrc, false];
        yield 'reportSample in script-src' => [SourceKeyword::reportSample, Directive::ScriptSrc, true];
        yield 'reportSample in script-src-attr' => [SourceKeyword::reportSample, Directive::ScriptSrcAttr, true];
        yield 'reportSample in script-src-elem' => [SourceKeyword::reportSample, Directive::ScriptSrcElem, true];
        yield 'reportSample in img-src' => [SourceKeyword::reportSample, Directive::ImgSrc, false];

        yield 'strictDynamic in default-src' => [SourceKeyword::strictDynamic, Directive::DefaultSrc, false];
        yield 'strictDynamic in script-src' => [SourceKeyword::strictDynamic, Directive::ScriptSrc, true];
        yield 'strictDynamic in script-src-attr' => [SourceKeyword::strictDynamic, Directive::ScriptSrcAttr, true];
        yield 'strictDynamic in script-src-elem' => [SourceKeyword::strictDynamic, Directive::ScriptSrcElem, true];
        yield 'strictDynamic in style-src' => [SourceKeyword::strictDynamic, Directive::StyleSrc, false];
        yield 'strictDynamic in style-src-attr' => [SourceKeyword::strictDynamic, Directive::StyleSrcAttr, false];
        yield 'strictDynamic in style-src-elem' => [SourceKeyword::strictDynamic, Directive::StyleSrcElem, false];
        yield 'strictDynamic in img-src' => [SourceKeyword::strictDynamic, Directive::ImgSrc, false];

        yield 'unsafeHashes in default-src' => [SourceKeyword::unsafeHashes, Directive::DefaultSrc, true];
        yield 'unsafeHashes in script-src' => [SourceKeyword::unsafeHashes, Directive::ScriptSrc, true];
        yield 'unsafeHashes in script-src-attr' => [SourceKeyword::unsafeHashes, Directive::ScriptSrcAttr, true];
        yield 'unsafeHashes in script-src-elem' => [SourceKeyword::unsafeHashes, Directive::ScriptSrcElem, true];
        yield 'unsafeHashes in style-src' => [SourceKeyword::unsafeHashes, Directive::ScriptSrc, true];
        yield 'unsafeHashes in style-src-attr' => [SourceKeyword::unsafeHashes, Directive::StyleSrcAttr, true];
        yield 'unsafeHashes in style-src-elem' => [SourceKeyword::unsafeHashes, Directive::StyleSrcElem, true];
        yield 'unsafeHashes in img-src' => [SourceKeyword::unsafeHashes, Directive::ImgSrc, false];

        yield 'unsafeInline in default-src' => [SourceKeyword::unsafeInline, Directive::DefaultSrc, true];
        yield 'unsafeInline in script-src' => [SourceKeyword::unsafeInline, Directive::ScriptSrc, true];
        yield 'unsafeInline in script-src-attr' => [SourceKeyword::unsafeInline, Directive::ScriptSrcAttr, true];
        yield 'unsafeInline in script-src-elem' => [SourceKeyword::unsafeInline, Directive::ScriptSrcElem, true];
        yield 'unsafeInline in style-src' => [SourceKeyword::unsafeInline, Directive::ScriptSrc, true];
        yield 'unsafeInline in style-src-attr' => [SourceKeyword::unsafeInline, Directive::StyleSrcAttr, true];
        yield 'unsafeInline in style-src-elem' => [SourceKeyword::unsafeInline, Directive::StyleSrcElem, true];
        yield 'unsafeInline in img-src' => [SourceKeyword::unsafeInline, Directive::ImgSrc, false];

        yield 'nonceProxy in default-src' => [SourceKeyword::nonceProxy, Directive::DefaultSrc, true];
        yield 'nonceProxy in script-src' => [SourceKeyword::nonceProxy, Directive::ScriptSrc, true];
        yield 'nonceProxy in script-src-attr' => [SourceKeyword::nonceProxy, Directive::ScriptSrcAttr, false];
        yield 'nonceProxy in script-src-elem' => [SourceKeyword::nonceProxy, Directive::ScriptSrcElem, true];
        yield 'nonceProxy in style-src' => [SourceKeyword::nonceProxy, Directive::ScriptSrc, true];
        yield 'nonceProxy in style-src-attr' => [SourceKeyword::nonceProxy, Directive::StyleSrcAttr, false];
        yield 'nonceProxy in style-src-elem' => [SourceKeyword::nonceProxy, Directive::StyleSrcElem, true];
        yield 'nonceProxy in img-src' => [SourceKeyword::nonceProxy, Directive::ImgSrc, false];
    }

    #[Test]
    #[DataProvider('isApplicableDataProvider')]
    public function isApplicable(SourceKeyword $sourceKeyword, Directive $directive, bool $expectation): void
    {
        self::assertSame($expectation, $sourceKeyword->isApplicable($directive));
    }
}
