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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Be;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class PageRendererViewHelperTest extends FunctionalTestCase
{
    public static function renderDataProvider(): array
    {
        return [
            'renderSetsPageTitle' => [
                '<f:be.pageRenderer pageTitle="foo" />',
                '<title>foo</title>',
            ],
            'renderIncludesCssFile' => [
                '<f:be.pageRenderer includeCssFiles="{0: \'EXT:backend/Resources/Public/Css/backend.css\'}" />',
                'rel="stylesheet" href="typo3/sysext/backend/Resources/Public/Css/backend.css',
            ],
            'renderIncludesJsFile' => [
                '<f:be.pageRenderer includeJsFiles="{0: \'EXT:backend/Resources/Public/JavaScript/backend.js\'}" />',
                '<script src="typo3/sysext/backend/Resources/Public/JavaScript/backend.js',
            ],
            'renderIncludesInlineSettings' => [
                '<f:be.pageRenderer addInlineSettings="{\'foo\': \'bar\'}" />',
                '"TYPO3":{"settings":{"foo":"bar"',
            ],
            'renderResolvesLabelUsingExtSyntax' => [
                '<f:be.pageRenderer addJsInlineLabels="{\'login.header\': \'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:login.header\'}" />',
                '"lang":{"login.header":"Login"}',
            ],
        ];
    }

    #[DataProvider('renderDataProvider')]
    #[Test]
    public function render(string $template, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $view->render();
        $pageRenderer = $this->get(PageRenderer::class);
        // PageRenderer depends on request to determine FE vs. BE
        self::assertStringContainsString($expected, $pageRenderer->renderResponse()->getBody()->__toString());
    }

    #[Test]
    public function renderResolvesLabelWithExtbaseRequest(): void
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Backend');
        $serverRequest = (new ServerRequest('https://example.com/typo3/'))->withAttribute('extbase', $extbaseRequestParameters)->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:be.pageRenderer addJsInlineLabels="{0: \'login.header\'}" />');
        $view = new TemplateView($context);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $view->render();
        $pageRenderer = $this->get(PageRenderer::class);
        // PageRenderer depends on request to determine FE vs. BE
        self::assertStringContainsString('"lang":{"login.header":"Login"}', $pageRenderer->renderResponse()->getBody()->__toString());
    }
}
