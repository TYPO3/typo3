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

namespace TYPO3\CMS\Fluid\Tests\FunctionalDeprecated\ViewHelpers\Be\Labels;

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class CshViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!isset($GLOBALS['LANG'])) {
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        }
    }

    public function isRenderedDataProvider(): array
    {
        return [
            '#1' => [
                '<f:be.labels.csh table="table" field="field" label="{label}">{variable}</f:be.labels.csh>',
                [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack',
                    'variable' => 'variable<>&"\'',
                ],
                '<label>Go back</label>',
            ],
            '#2' => [
                '<f:be.labels.csh table="table" field="field" label="{label}">{variable}</f:be.labels.csh>',
                [
                    'label' => 'label<>&"\'',
                    'variable' => 'variable<>&"\'',
                ],
                '<label>label&lt;&gt;&amp;&quot;&#039;</label>',
            ],
            '#3' => [
                '{f:be.labels.csh(table:\'table\', field:\'field\', label:label)}',
                [
                    'label' => 'label<>&"\'',
                    'variable' => 'variable<>&"\'',
                ],
                '<label>label&lt;&gt;&amp;&quot;&#039;</label>',
            ],
            '#4' => [
                '{f:be.labels.csh(table:\'table\', field:\'field\', label:\'{label}\')}',
                [
                    'label' => 'label<>&"\'',
                    'variable' => 'variable<>&"\'',
                ],
                '<label>label&lt;&gt;&amp;&quot;&#039;</label>',
            ],
        ];
    }

    /**
     * @param string $source
     * @param array $variables
     * @param string $expectation
     *
     * @test
     * @dataProvider isRenderedDataProvider
     */
    public function isRendered(string $source, array $variables, string $expectation): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($source);
        $view = new TemplateView($context);
        $view->getRenderingContext()->getCache()->flush();
        $view->assignMultiple($variables);
        self::assertSame($expectation, $view->render());
    }
}
