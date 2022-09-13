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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Asset\CssViewHelper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CssViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @return array
     */
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
     * @param string $href
     * @test
     * @dataProvider sourceDataProvider
     */
    public function sourceStringIsNotHtmlEncodedBeforePassedToAssetCollector(string $href): void
    {
        $assetCollector = new AssetCollector();
        $viewHelper = new CssViewHelper();
        $viewHelper->injectAssetCollector($assetCollector);
        $viewHelper->setArguments([
            'identifier' => 'test',
            'href' => $href,
            'priority' => false,
        ]);
        $viewHelper->initializeArgumentsAndRender();
        $collectedJavaScripts = $assetCollector->getStyleSheets();
        self::assertSame($collectedJavaScripts['test']['source'], $href);
        self::assertSame($collectedJavaScripts['test']['attributes'], []);
    }

    /**
     * @test
     */
    public function booleanAttributesAreProperlyConverted(): void
    {
        $assetCollector = new AssetCollector();
        $viewHelper = new CssViewHelper();
        $viewHelper->injectAssetCollector($assetCollector);
        $viewHelper->setArguments([
            'identifier' => 'test',
            'href' => 'my.css',
            'disabled' => true,
            'priority' => false,
        ]);
        $viewHelper->initializeArgumentsAndRender();
        $collectedJavaScripts = $assetCollector->getStyleSheets();
        self::assertSame($collectedJavaScripts['test']['source'], 'my.css');
        self::assertSame($collectedJavaScripts['test']['attributes'], ['disabled' => 'disabled']);
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
        $assetCollector = new AssetCollector();
        GeneralUtility::setSingletonInstance(AssetCollector::class, $assetCollector);

        $view = new StandaloneView();
        $view->setTemplateSource(sprintf('<f:asset.css identifier="test">%s</f:asset.css>', $source));
        $view->assign('color', $value);
        $view->render();
        GeneralUtility::removeSingletonInstance(AssetCollector::class, $assetCollector);

        self::assertSame($expectation, $assetCollector->getInlineStyleSheets()['test']['source']);
    }
}
