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

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NumberViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    public function renderDataProvider(): array
    {
        return [
            'formatNumberDefaultsToEnglishNotationWithTwoDecimals' => [
                '<f:format.number>3.1415926535898</f:format.number>',
                '3.14',
            ],
            'formatNumberWithDecimalPoint' => [
                '<f:format.number decimalSeparator=",">3.1415926535898</f:format.number>',
                '3,14',
            ],
            'formatNumberWithDecimals' => [
                '<f:format.number decimals="4">3.1415926535898</f:format.number>',
                '3.1416',
            ],
            'formatNumberWithThousandsSeparator' => [
                '<f:format.number thousandsSeparator=",">3141.5926535898</f:format.number>',
                '3,141.59',
            ],
            'formatNumberWithEmptyInput' => [
                '<f:format.number></f:format.number>',
                '0.00',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource($template);
        self::assertEquals($expected, $view->render());
    }
}
