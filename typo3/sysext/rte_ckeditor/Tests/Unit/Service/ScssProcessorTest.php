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

namespace TYPO3\CMS\RteCKEditor\Tests\Unit\Service;

use ScssPhp\ScssPhp\Compiler;
use TYPO3\CMS\RteCKEditor\Service\ScssProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ScssProcessorTest extends UnitTestCase
{
    public static function cssIsPrefixedForScssDataProvider(): \Generator
    {
        yield 'from fixture files' => [
            'foo',
            file_get_contents(__DIR__ . '/../Fixtures/origin.scss'),
            rtrim(file_get_contents(__DIR__ . '/../Fixtures/fixed.scss')),
        ];
        yield 'minified' => [
            'foo',
            'div{color:#abc;}body{color:#abc;}',
            "foo {\ndiv{color:#abc;;}&{color:#abc;;}\n}",
        ];
        yield 'chained' => [
            'foo',
            'body,html{color:#abc}html,body{color:#abc}',
            "foo {\n&,&{color:#abc;}&,&{color:#abc;}\n}",
        ];
        yield 'empty #html' => [
            'foo',
            '#html {}',
            "foo {\n#html {;}\n}",
        ];
        yield 'empty .html' => [
            'foo',
            '.html {}',
            "foo {\n.html {;}\n}",
        ];
        yield 'embedded ---html---' => [
            'foo',
            'my---html---div { color: #abc; }',
            "foo {\nmy---html---div { color: #abc; ;}\n}",
        ];
        yield 'embedded ___html___' => [
            'foo',
            'my___html___div { color: #abc; }',
            "foo {\nmy___html___div { color: #abc; ;}\n}",
        ];
    }

    /**
     * @test
     * @dataProvider cssIsPrefixedForScssDataProvider
     */
    public function cssIsPrefixedForScss(string $prefix, string $source, string $expectation): void
    {
        $subject = new ScssProcessor(new Compiler());
        $processedSource = $subject->prefixCssForScss($prefix, $source);
        self::assertSame($expectation, $processedSource);
    }

    /**
     * @test
     */
    public function prefixedScssIsCompiledToCss(): void
    {
        $source = file_get_contents(__DIR__ . '/../Fixtures/origin.scss');
        $expected = file_get_contents(__DIR__ . '/../Fixtures/compiled.css');

        $subject = new ScssProcessor(new Compiler());
        $processedSource = $subject->prefixCssForScss('foo', $source);
        $processedSource = $subject->compileToCss($processedSource);
        self::assertSame($expected, $processedSource);
    }
}
