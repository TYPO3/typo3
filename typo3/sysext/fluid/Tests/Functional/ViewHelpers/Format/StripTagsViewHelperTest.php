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

class StripTagsViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    public function renderDataProvider(): array
    {
        return [
            'renderUsesValueAsSourceIfSpecified' => [
                '<f:format.stripTags value="Some string" />',
                'Some string',
            ],
            'renderUsesChildnodesAsSourceIfSpecified' => [
                '<f:format.stripTags>Some string</f:format.stripTags>',
                'Some string',
            ],
            'no special chars' => [
                '<f:format.stripTags>This is a sample text without special characters.</f:format.stripTags>',
                'This is a sample text without special characters.',
            ],
            'some tags' => [
                '<f:format.stripTags>This is a sample text <b>with <i>some</i> tags</b>.</f:format.stripTags>',
                'This is a sample text with some tags.',
            ],
            'some umlauts' => [
                '<f:format.stripTags>This text contains some &quot;&Uuml;mlaut&quot;.</f:format.stripTags>',
                'This text contains some &quot;&Uuml;mlaut&quot;.',
            ],
            'allowed tags' => [
                '<f:format.stripTags allowedTags="<strong>">This text <i>contains</i> some <strong>allowed</strong> tags.</f:format.stripTags>',
                'This text contains some <strong>allowed</strong> tags.',
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
     * Ensures that objects are handled properly:
     * + class having __toString() method gets tags stripped off
     *
     * @test
     */
    public function renderEscapesObjectIfPossible(): void
    {
        $toStringClass = new class() {
            public function __toString(): string
            {
                return '<script>alert(\'"xss"\')</script>';
            }
        };
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.stripTags>{value}</f:format.stripTags>');
        $view = new TemplateView($context);
        $view->assign('value', $toStringClass);
        self::assertEquals('alert(\'"xss"\')', $view->render());
    }
}
