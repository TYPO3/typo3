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

class HtmlentitiesDecodeViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function renderUsesValueAsSourceIfSpecified(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentitiesDecode value="Some string" />');
        self::assertEquals('Some string', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderUsesChildnodesAsSourceIfSpecified(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentitiesDecode>Some string</f:format.htmlentitiesDecode>');
        self::assertEquals('Some string', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters(): void
    {
        $source = 'This is a sample text without special characters. <> &©"\'';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentitiesDecode>' . $source . '</f:format.htmlentitiesDecode>');
        self::assertEquals($source, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderDecodesSimpleString(): void
    {
        $source = 'Some special characters: &amp; &quot; \' &lt; &gt; *';
        $expectedResult = 'Some special characters: & " \' < > *';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentitiesDecode>' . $source . '</f:format.htmlentitiesDecode>');
        self::assertEquals($expectedResult, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderRespectsKeepQuoteArgument(): void
    {
        $source = 'Some special characters: &amp; &quot; \' &lt; &gt; *';
        $expectedResult = 'Some special characters: & &quot; \' < > *';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentitiesDecode keepQuotes="true">' . $source . '</f:format.htmlentitiesDecode>');
        self::assertEquals($expectedResult, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderRespectsEncodingArgument(): void
    {
        $source = 'Some special characters: &amp; &quot; \' &lt; &gt; *';
        $expectedResult = 'Some special characters: & " \' < > *';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:format.htmlentitiesDecode encoding="ISO-8859-1">' . $source . '</f:format.htmlentitiesDecode>');
        self::assertEquals($expectedResult, (new TemplateView($context))->render());
    }
}
