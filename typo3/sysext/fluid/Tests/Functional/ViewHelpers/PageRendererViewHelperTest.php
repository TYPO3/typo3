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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class PageRendererViewHelperTest extends FunctionalTestCase
{
    public function renderDataProvider(): array
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
            'renderIncludesRequireJsModules' => [
                '<f:be.pageRenderer includeRequireJsModules="{0: \'EXT:backend/Resources/Public/JavaScript/iDoNotExist.js\'}" />',
                '"name":"EXT:backend\/Resources\/Public\/JavaScript\/iDoNotExist.js"',
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

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        $view->render();
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        // PageRenderer depends on request to determine FE vs. BE
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        self::assertStringContainsString($expected, $pageRenderer->render());
    }

    /**
     * @test
     */
    public function renderResolvesLabelWithExtbaseRequest(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:be.pageRenderer addJsInlineLabels="{0: \'login.header\'}" />');
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Backend');
        $context->setRequest((new Request())->withAttribute('extbase', $extbaseRequestParameters));
        $view = new TemplateView($context);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        $view->render();
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        // PageRenderer depends on request to determine FE vs. BE
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        self::assertStringContainsString('"lang":{"login.header":"Login"}', $pageRenderer->render());
    }
}
