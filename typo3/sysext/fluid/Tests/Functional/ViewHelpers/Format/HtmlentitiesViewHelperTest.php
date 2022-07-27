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

class HtmlentitiesViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function renderUsesValueAsSourceIfSpecified(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentities value="Some string" />');
        self::assertEquals('Some string', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderUsesChildnodesAsSourceIfSpecified(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentities>Some string</f:format.htmlentities>');
        self::assertEquals('Some string', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters(): void
    {
        $source = 'This is a sample text without special characters.';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentities value="' . $source . '" />');
        self::assertEquals($source, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderEncodesSimpleString(): void
    {
        $source = 'Some special characters: &©"\'';
        $expectedResult = 'Some special characters: &amp;&copy;&quot;&#039;';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentities>' . $source . '</f:format.htmlentities>');
        self::assertEquals($expectedResult, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderRespectsKeepQuoteArgument(): void
    {
        $source = 'Some special characters: &©"\'';
        $expectedResult = 'Some special characters: &amp;&copy;"\'';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentities keepQuotes="true">' . $source . '</f:format.htmlentities>');
        self::assertEquals($expectedResult, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderRespectsEncodingArgument(): void
    {
        $source = mb_convert_encoding('Some special characters: &©"\'', 'ISO-8859-1', 'UTF-8');
        $expectedResult = 'Some special characters: &amp;&copy;&quot;&#039;';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentities encoding="ISO-8859-1">' . $source . '</f:format.htmlentities>');
        self::assertEquals($expectedResult, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderConvertsAlreadyConvertedEntitiesByDefault(): void
    {
        $source = 'already &quot;encoded&quot;';
        $expectedResult = 'already &amp;quot;encoded&amp;quot;';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentities>' . $source . '</f:format.htmlentities>');
        self::assertEquals($expectedResult, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderDoesNotConvertAlreadyConvertedEntitiesIfDoubleQuoteIsFalse(): void
    {
        $source = 'already &quot;encoded&quot;';
        $expectedResult = 'already &quot;encoded&quot;';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentities doubleEncode="false">' . $source . '</f:format.htmlentities>');
        self::assertEquals($expectedResult, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderEscapesObjectIfPossible(): void
    {
        $toStringClass = new class() {
            public function __toString(): string
            {
                return '<script>alert(\'"&xss"\')</script>';
            }
        };
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentities value="{source}" />');
        $view = new TemplateView($context);
        $view->assign('source', $toStringClass);
        self::assertEquals('&lt;script&gt;alert(&#039;&quot;&amp;xss&quot;&#039;)&lt;/script&gt;', $view->render());
    }
}
