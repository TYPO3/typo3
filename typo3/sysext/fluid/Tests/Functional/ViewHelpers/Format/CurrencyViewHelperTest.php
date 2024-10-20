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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class CurrencyViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function renderConvertsAValueDataProvider(): array
    {
        return [
            'rounds float correctly' => [
                '<f:format.currency>123.456</f:format.currency>',
                '123,46',
            ],
            'currency sign' => [
                '<f:format.currency currencySign="foo">123</f:format.currency>',
                '123,00 foo',
            ],
            'prepended currency sign' => [
                '<f:format.currency currencySign="foo" prependCurrency="true" decimalSeparator="," thousandsSeparator=".">123</f:format.currency>',
                'foo 123,00',
            ],
            'respects currency separator' => [
                '<f:format.currency currencySign="foo" separateCurrency="false" prependCurrency="true" decimalSeparator="," thousandsSeparator=".">123</f:format.currency>',
                'foo123,00',
            ],
            'respects decimal separator' => [
                '<f:format.currency currencySign="" decimalSeparator="|">12345</f:format.currency>',
                '12.345|00',
            ],
            'respects thousands separator' => [
                '<f:format.currency currencySign="" decimalSeparator=","thousandsSeparator="|">12345</f:format.currency>',
                '12|345,00',
            ],
            'empty value' => [
                '<f:format.currency></f:format.currency>',
                '0,00',
            ],
            'zero values' => [
                '<f:format.currency>0</f:format.currency>',
                '0,00',
            ],
            'negative amounts' => [
                '<f:format.currency>-123.456</f:format.currency>',
                '-123,46',
            ],
            'strings to zero value float' => [
                '<f:format.currency>TYPO3</f:format.currency>',
                '0,00',
            ],
            'comma values to value before comma' => [
                '<f:format.currency>12,34.00</f:format.currency>',
                '12,00',
            ],
            'without decimals' => [
                '<f:format.currency decimals="0">54321</f:format.currency>',
                '54.321',
            ],
            'three decimals' => [
                '<f:format.currency decimals="3">54321</f:format.currency>',
                '54.321,000',
            ],
            'with dash' => [
                '<f:format.currency useDash="true">54321.00</f:format.currency>',
                '54.321,—',
            ],
            'without dash' => [
                '<f:format.currency useDash="true">54321.45</f:format.currency>',
                '54.321,45',
            ],
            'without integer tag content' => [
                '<f:for each="{4711:\'4712\'}" as="i" iteration="iterator" key="k"><f:format.currency useDash="true">{k}</f:format.currency></f:for>',
                '4.711,—',
            ],
        ];
    }

    #[DataProvider('renderConvertsAValueDataProvider')]
    #[Test]
    public function renderConvertsAValue(string $src, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($src);
        self::assertSame($expected, (new TemplateView($context))->render());
    }
}
