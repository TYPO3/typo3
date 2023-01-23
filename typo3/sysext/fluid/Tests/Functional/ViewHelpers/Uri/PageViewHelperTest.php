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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Uri;

use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PageViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [];

    /**
     * @test
     */
    public function renderThrowsExceptionWithoutRequest(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1639820200);
        $view = new StandaloneView();
        $view->setRequest();
        $view->setTemplateSource('<f:uri.page />');
        $view->render();
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextCreatesNoUriWithoutRoute(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:uri.page>foo</f:uri.page>');
        $result = $view->render();
        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextCreatesUriWithRouteFromQueryString(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withQueryParams(['route' => 'web_layout']);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:uri.page addQueryString="1" pageUid="42">foo</f:uri.page>');
        $result = $view->render();
        self::assertSame('/typo3/module/web/layout?token=dummyToken&amp;id=42', $result);
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextCreatesUriWithRouteFromAdditionalParams(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:uri.page additionalParams="{\'route\': \'web_layout\'}" pageUid="42">foo</f:uri.page>');
        $result = $view->render();
        self::assertSame('/typo3/module/web/layout?token=dummyToken&amp;id=42', $result);
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextCreatesUriWithRouteFromRequest(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('dummy', ['_identifier' => 'web_layout']));
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:uri.page pageUid="42">foo</f:uri.page>');
        $result = $view->render();
        self::assertSame('/typo3/module/web/layout?token=dummyToken&amp;id=42', $result);
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextAddsSection(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('dummy', ['_identifier' => 'web_layout']));
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:uri.page pageUid="42" section="mySection">foo</f:uri.page>');
        $result = $view->render();
        self::assertSame('/typo3/module/web/layout?token=dummyToken&amp;id=42#mySection', $result);
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextCreatesAbsoluteUri(): void
    {
        $request = new ServerRequest('http://localhost/typo3/', null, 'php://input', [], ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => 'typo3/index.php']);
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('dummy', ['_identifier' => 'web_layout']));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:uri.page pageUid="42" absolute="1">foo</f:uri.page>');
        $result = $view->render();
        self::assertSame('http://localhost/typo3/module/web/layout?token=dummyToken&amp;id=42', $result);
    }

    /**
     * @test
     */
    public function renderInBackendExtbaseContextCreatesUriWithId(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('module/web/layout', ['_identifier' => 'web_layout']));
        $request = $request->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = $request->withQueryParams(['id' => 42]);
        $request = new Request($request);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:uri.page>foo</f:uri.page>');
        $result = $view->render();
        self::assertSame('/typo3/module/web/layout?token=dummyToken&amp;id=42', $result);
    }

    /**
     * @test
     */
    public function renderInBackendExtbaseContextCreatesAbsoluteUriWithId(): void
    {
        $request = new ServerRequest('http://localhost/typo3/', null, 'php://input', [], ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => 'typo3/index.php']);
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('module/web/layout', ['_identifier' => 'web_layout']));
        $request = $request->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = $request->withQueryParams(['id' => 42]);
        $request = new Request($request);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:uri.page absolute="1">foo</f:uri.page>');
        $result = $view->render();
        self::assertSame('http://localhost/typo3/module/web/layout?token=dummyToken&amp;id=42', $result);
    }

    public function renderDataProvider(): array
    {
        return [
            'renderProvidesATagForValidLinkTarget' => [
                '<f:uri.page>index.php</f:uri.page>',
                '/',
            ],
            'renderWillProvideEmptyATagForNonValidLinkTarget' => [
                '<f:uri.page></f:uri.page>',
                '/',
            ],
            'link to root page' => [
                '<f:uri.page pageUid="1" />',
                '/',
            ],
            'link to root page with section' => [
                '<f:uri.page pageUid="1" section="c13" />',
                '/#c13',
            ],
            'link to page sub page' => [
                '<f:uri.page pageUid="3" />',
                '/dummy-1-2/dummy-1-2-3',
            ],
            'additional parameters one level' => [
                '<f:uri.page pageUid="3" additionalParams="{tx_examples_haiku: \'foo\'}">haiku title</f:uri.page>',
                '/dummy-1-2/dummy-1-2-3?tx_examples_haiku=foo&amp;cHash=3ed8716f46e97ba37335fa4b28ce2d8a',
            ],
            'additional parameters two levels' => [
                '<f:uri.page pageUid="3" additionalParams="{tx_examples_haiku: {action: \'show\', haiku: 42}}">haiku title</f:uri.page>',
                '/dummy-1-2/dummy-1-2-3?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bhaiku%5D=42&amp;cHash=1e0eb1e54d6bacf0138a50107c6ae29a',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function renderInFrontendWithCoreContext(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $GLOBALS['TSFE'] = $this->createMock(TypoScriptFrontendController::class);
        $GLOBALS['TSFE']->id = 1;
        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource($template);
        $result = $view->render();
        self::assertSame($expected, $result);
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function renderInFrontendWithExtbaseContext(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($request);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $GLOBALS['TSFE'] = $this->createMock(TypoScriptFrontendController::class);
        $GLOBALS['TSFE']->id = 1;
        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource($template);
        $result = $view->render();
        self::assertSame($expected, $result);
    }
}
