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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Format;

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class PaddingViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    public function renderDataProvider(): array
    {
        return [
            'stringsArePaddedWithBlanksByDefault' => [
                '<f:format.padding padLength="10">foo</f:format.padding>',
                'foo       ',
            ],
            'paddingStringCanBeSpecified' => [
                '<f:format.padding padLength="10" padString="-=">foo</f:format.padding>',
                'foo-=-=-=-',
            ],
            'stringIsNotTruncatedIfPadLengthIsBelowStringLength' => [
                '<f:format.padding padLength="5">some long string</f:format.padding>',
                'some long string',
            ],
            'valueParameterIsRespected' => [
                '<f:format.padding value="foo" padLength="5" padString="0" />',
                'foo00',
            ],
            'valueParameterIsOmitted' => [
                '<f:format.padding padLength="5" padString="0" />',
                '00000',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function integersAreRespectedAsValue(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.padding padLength="5" padString="0">{value}</f:format.padding>');
        $view = new TemplateView($context);
        $view->assign('value', 123);
        self::assertEquals('12300', $view->render());
    }
}
