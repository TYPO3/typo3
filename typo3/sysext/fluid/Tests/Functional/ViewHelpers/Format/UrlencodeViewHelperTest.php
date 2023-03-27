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

class UrlencodeViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function renderDataProvider(): array
    {
        return [
            'renderUsesValueAsSourceIfSpecified' => [
                '<f:format.urlencode value="Source" />',
                'Source',
            ],
            'renderUsesChildnodesAsSourceIfSpecified' => [
                '<f:format.urlencode>Source</f:format.urlencode>',
                'Source',
            ],
            'renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters' => [
                '<f:format.urlencode>StringWithoutSpecialCharacters</f:format.urlencode>',
                'StringWithoutSpecialCharacters',
            ],
            'renderEncodesString' => [
                '<f:format.urlencode>Foo @+%/ "</f:format.urlencode>',
                'Foo%20%40%2B%25%2F%20%22',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    /**
     * Ensures that objects are handled properly:
     * + class having __toString() method gets tags stripped off
     *
     * @test
     */
    public function renderEscapesObjectIfPossible(): void
    {
        $toStringClass = new class () {
            public function __toString(): string
            {
                return '<script>alert(\'"xss"\')</script>';
            }
        };
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.urlencode>{value}</f:format.urlencode>');
        $view = new TemplateView($context);
        $view->assign('value', $toStringClass);
        self::assertEquals('%3Cscript%3Ealert%28%27%22xss%22%27%29%3C%2Fscript%3E', $view->render());
    }
}
