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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Asset;

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class CssViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public function sourceDataProvider(): array
    {
        return [
            'fileadmin reference' => ['fileadmin/StyleSheets/foo.css'],
            'EXT: reference' => ['EXT:core/Resources/Public/StyleSheets/foo.css'],
            'external reference' => ['https://typo3.com/foo.css'],
            'external reference with 1 parameter' => ['https://typo3.com/foo.css?foo=bar'],
            'external reference with 2 parameters' => ['https://typo3.com/foo.css?foo=bar&bar=baz'],
        ];
    }

    /**
     * @test
     * @dataProvider sourceDataProvider
     */
    public function sourceStringIsNotHtmlEncodedBeforePassedToAssetCollector(string $href): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:asset.css identifier="test" href="' . $href . '" priority="0"/>');

        (new TemplateView($context))->render();

        $collectedStyleSheets = $this->get(AssetCollector::class)->getStyleSheets();
        self::assertSame($href, $collectedStyleSheets['test']['source']);
        self::assertSame([], $collectedStyleSheets['test']['attributes']);
    }

    /**
     * @test
     */
    public function booleanAttributesAreProperlyConverted(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:asset.css identifier="test" href="my.css" disabled="1" priority="0"/>');

        (new TemplateView($context))->render();

        $collectedStyleSheets = $this->get(AssetCollector::class)->getStyleSheets();
        self::assertSame('my.css', $collectedStyleSheets['test']['source']);
        self::assertSame(['disabled' => 'disabled'], $collectedStyleSheets['test']['attributes']);
    }

    public static function childNodeRenderingIsCorrectDataProvider(): array
    {
        return [
            // Double quotes
            'variable with double quotes is encoded' => [
                '</style>/* " ', // variable value
                'body { color: #{color}; }', // inner template source
                'body { color: #&lt;/style&gt;/* &quot; ; }', // expectation
            ],
            'variable with double quotes is encoded in single quotes' => [
                '</style>/* " ', // variable value
                'body { color: \'#{color}\'; }', // inner template source
                'body { color: \'#&lt;/style&gt;/* &quot; \'; }', // expectation
            ],
            'variable with double quotes is encoded in double quotes' => [
                '</style>/* " ', // variable value
                'body { color: "#{color}"; }', // inner template source
                'body { color: "#&lt;/style&gt;/* &quot; "; }', // expectation
            ],
            // Single quotes
            'variable with single quotes is encoded' => [
                '</style>/* \' ', // variable value
                'body { color: #{color}; }', // inner template source
                'body { color: #&lt;/style&gt;/* &#039; ; }', // expectation
            ],
            'variable with single quotes is encoded in single quotes' => [
                '</style>/* \' ', // variable value
                'body { color: \'#{color}\'; }', // inner template source
                'body { color: \'#&lt;/style&gt;/* &#039; \'; }', // expectation
            ],
            'variable with single quotes is encoded in double quotes' => [
                '</style>/* \' ', // variable value
                'body { color: "#{color}"; }', // inner template source
                'body { color: "#&lt;/style&gt;/* &#039; "; }', // expectation
            ],
            // Raw instruction
            'raw instruction is passed' => [
                '</style>/* " ',
                'body { color: #{color -> f:format.raw()}; }',
                'body { color: #</style>/* " ; }',
            ],
            'raw instruction is passed in sigle quotes' => [
                '</style>/* " ',
                'body { color: \'#{color -> f:format.raw()}\'; }',
                'body { color: \'#</style>/* " \'; }',
            ],
            'raw instruction is passed in double quotes' => [
                '</style>/* " ',
                'body { color: "#{color -> f:format.raw()}"; }',
                'body { color: "#</style>/* " "; }',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider childNodeRenderingIsCorrectDataProvider
     */
    public function childNodeRenderingIsCorrect(string $value, string $source, string $expectation): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:asset.css identifier="test">' . $source . '</f:asset.css>');
        $context->getVariableProvider()->add('color', $value);

        (new TemplateView($context))->render();

        $collectedInlineStyleSheets = $this->get(AssetCollector::class)->getInlineStyleSheets();
        self::assertSame($expectation, $collectedInlineStyleSheets['test']['source']);
    }
}
