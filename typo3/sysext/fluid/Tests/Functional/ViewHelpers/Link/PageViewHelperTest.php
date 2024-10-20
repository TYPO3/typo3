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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Link;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class PageViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'excludedParameters' => [
                    'untrusted',
                ],
            ],
        ],
    ];

    #[Test]
    public function renderThrowsExceptionWithoutRequest(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1639819269);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:link.page>foo</f:link.page>');
        (new TemplateView($context))->render();
    }

    #[Test]
    public function renderInBackendCoreContextCreatesNoLinkWithoutRoute(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:link.page>foo</f:link.page>');
        $result = (new TemplateView($context))->render();
        self::assertSame('foo', $result);
    }

    #[Test]
    public function renderInBackendCoreContextAllowsIntegerBasedTagContent(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:for each="{4711:\'4712\'}" as="i" iteration="iterator" key="k"><f:link.page>{k}</f:link.page></f:for>');
        $result = (new TemplateView($context))->render();
        self::assertSame('4711', $result);
    }

    #[Test]
    public function renderInBackendCoreContextCreatesLinkWithRouteFromQueryString(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withQueryParams(['route' => 'web_layout']);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:link.page addQueryString="1" pageUid="42">foo</f:link.page>');
        $result = (new TemplateView($context))->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    #[Test]
    public function renderInBackendCoreContextCreatesLinkWithRouteFromAdditionalParams(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:link.page additionalParams="{\'route\': \'web_layout\'}" pageUid="42">foo</f:link.page>');
        $result = (new TemplateView($context))->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    #[Test]
    public function renderInBackendCoreContextCreatesLinkWithRouteFromRequest(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('dummy', ['_identifier' => 'web_layout']));
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:link.page pageUid="42">foo</f:link.page>');
        $result = (new TemplateView($context))->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    #[Test]
    public function renderInBackendCoreContextAddsSection(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('dummy', ['_identifier' => 'web_layout']));
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:link.page pageUid="42" section="mySection">foo</f:link.page>');
        $result = (new TemplateView($context))->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42#mySection">foo</a>', $result);
    }

    #[Test]
    public function renderInBackendCoreContextCreatesAbsoluteLink(): void
    {
        $request = new ServerRequest('http://localhost/typo3', null, 'php://input', [], ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/index.php']);
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('dummy', ['_identifier' => 'web_layout']));
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:link.page pageUid="42" absolute="1">foo</f:link.page>');
        $result = (new TemplateView($context))->render();
        self::assertSame('<a href="http://localhost/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    #[Test]
    public function renderInBackendExtbaseContextCreatesLinkWithId(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('module/web/layout', ['_identifier' => 'web_layout']));
        $request = $request->withQueryParams(['id' => 42]);
        $request = $request->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($request);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:link.page>foo</f:link.page>');
        $result = (new TemplateView($context))->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    #[Test]
    public function renderInBackendExtbaseContextAllowsIntegerBasedTagContent(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('module/web/layout', ['_identifier' => 'web_layout']));
        $request = $request->withQueryParams(['id' => 42]);
        $request = $request->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($request);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:for each="{4711:\'4712\'}" as="i" iteration="iterator" key="k"><f:link.page>{k}</f:link.page></f:for>');
        $result = (new TemplateView($context))->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42">4711</a>', $result);
    }

    #[Test]
    public function renderInBackendExtbaseContextCreatesAbsoluteLinkWithId(): void
    {
        $request = new ServerRequest('http://localhost/typo3/', null, 'php://input', [], ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => 'typo3/index.php']);
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('module/web/layout', ['_identifier' => 'web_layout']));
        $request = $request->withQueryParams(['id' => 42]);
        $request = $request->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($request);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:link.page absolute="1">foo</f:link.page>');
        $result = (new TemplateView($context))->render();
        self::assertSame('<a href="http://localhost/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    public static function renderDataProvider(): array
    {
        return [
            'renderProvidesATagForValidLinkTarget' => [
                '<f:link.page>index.php</f:link.page>',
                '<a href="/">index.php</a>',
            ],
            'renderWillProvideEmptyATagForNonValidLinkTarget' => [
                '<f:link.page></f:link.page>',
                '<a href="/"></a>',
            ],
            'link to root page' => [
                '<f:link.page pageUid="1">linkMe</f:link.page>',
                '<a href="/">linkMe</a>',
            ],
            'link to root page with section' => [
                '<f:link.page pageUid="1" section="c13">linkMe</f:link.page>',
                '<a href="/#c13">linkMe</a>',
            ],
            'link to root page with page type' => [
                '<f:link.page pageUid="1" pageType="1234">linkMe</f:link.page>',
                '<a href="/?type=1234">linkMe</a>',
            ],
            'link to root page with untrusted query arguments' => [
                '<f:link.page addQueryString="untrusted"></f:link.page>',
                '<a href="/?untrusted=123"></a>',
            ],
            'link to page sub page' => [
                '<f:link.page pageUid="3">linkMe</f:link.page>',
                '<a href="/dummy-1-2/dummy-1-2-3">linkMe</a>',
            ],
            'additional parameters one level' => [
                '<f:link.page pageUid="3" additionalParams="{tx_examples_haiku: \'foo\'}">haiku title</f:link.page>',
                '<a href="/dummy-1-2/dummy-1-2-3?tx_examples_haiku=foo&amp;cHash=3ed8716f46e97ba37335fa4b28ce2d8a">haiku title</a>',
            ],
            'additional parameters two levels' => [
                '<f:link.page pageUid="3" additionalParams="{tx_examples_haiku: {action: \'show\', haiku: 42}}">haiku title</f:link.page>',
                '<a href="/dummy-1-2/dummy-1-2-3?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bhaiku%5D=42&amp;cHash=1e0eb1e54d6bacf0138a50107c6ae29a">haiku title</a>',
            ],
            // see: https://forge.typo3.org/issues/101432
            'link with target renders the correct target attribute if intTarget is configured' => [
                '<f:link.page pageUid="3" target="home">link me</f:link.page>',
                '<a target="home" href="/dummy-1-2/dummy-1-2-3">link me</a>',
                [
                    'config.' => [
                        'intTarget' => '_self',
                    ],
                ],
            ],
            // see: https://forge.typo3.org/issues/101432
            'link skips configured intTarget if no target viewhelper attribute is provided' => [
                '<f:link.page pageUid="3">link me</f:link.page>',
                '<a href="/dummy-1-2/dummy-1-2-3">link me</a>',
                [
                    'config' => [
                        'intTarget' => '_self',
                    ],
                ],
            ],
            // see: https://forge.typo3.org/issues/105367
            'link allows integer based tag content' => [
                '<f:for each="{4711:\'4712\'}" as="i" iteration="iterator" key="k"><f:link.page pageUid="3">{k}</f:link.page></f:for>',
                '<a href="/dummy-1-2/dummy-1-2-3">4711</a>',
                [
                    'config' => [
                        'intTarget' => '_self',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('renderDataProvider')]
    #[Test]
    public function renderInFrontendWithCoreContext(string $template, string $expected, array $frontendTypoScriptConfigArray = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setConfigArray($frontendTypoScriptConfigArray);
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('routing', new PageArguments(1, '0', ['untrusted' => 123]));
        $request = $request->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource($template);
        $result = (new TemplateView($context))->render();
        self::assertSame($expected, $result);
    }

    #[DataProvider('renderDataProvider')]
    #[Test]
    public function renderInFrontendWithExtbaseContext(string $template, string $expected, array $frontendTypoScriptSetupArray = [], array $tsfeConfigArray = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray($frontendTypoScriptSetupArray);
        $frontendTypoScript->setConfigArray([]);
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('routing', new PageArguments(1, '0', ['untrusted' => 123]));
        $request = $request->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = $request->withAttribute('currentContentObject', $contentObjectRenderer);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $contentObjectRenderer->setRequest($request);
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $request = new Request($request);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource($template);
        $result = (new TemplateView($context))->render();
        self::assertSame($expected, $result);
    }
}
