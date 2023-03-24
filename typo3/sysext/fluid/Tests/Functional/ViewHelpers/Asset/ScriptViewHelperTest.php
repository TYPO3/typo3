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
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ScriptViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public function sourceDataProvider(): array
    {
        return [
            'fileadmin reference' => ['fileadmin/JavaScript/foo.js'],
            'EXT: reference' => ['EXT:core/Resources/Public/JavaScript/foo.js'],
            'external reference' => ['https://typo3.com/foo.js'],
            'external reference with 1 parameter' => ['https://typo3.com/foo.js?foo=bar'],
            'external reference with 2 parameters' => ['https://typo3.com/foo.js?foo=bar&bar=baz'],
        ];
    }

    /**
     * @test
     * @dataProvider sourceDataProvider
     */
    public function sourceStringIsNotHtmlEncodedBeforePassedToAssetCollector(string $src): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:asset.script identifier="test" src="' . $src . '" priority="0"/>');

        (new TemplateView($context))->render();

        $collectedJavaScripts = $this->get(AssetCollector::class)->getJavaScripts();
        self::assertSame($src, $collectedJavaScripts['test']['source']);
        self::assertSame([], $collectedJavaScripts['test']['attributes']);
    }

    /**
     * @test
     */
    public function booleanAttributesAreProperlyConverted(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:asset.script identifier="test" src="my.js" async="1" defer="1" nomodule="1" priority="0"/>');

        (new TemplateView($context))->render();

        $collectedJavaScripts = $this->get(AssetCollector::class)->getJavaScripts();
        self::assertSame($collectedJavaScripts['test']['source'], 'my.js');
        self::assertSame($collectedJavaScripts['test']['attributes'], ['async' => 'async', 'defer' => 'defer', 'nomodule' => 'nomodule']);
    }
}
