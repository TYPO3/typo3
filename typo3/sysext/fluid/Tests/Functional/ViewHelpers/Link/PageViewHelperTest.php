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

use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
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

    public function tearDown(): void
    {
        FormProtectionFactory::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionWithoutRequest(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1639819269);
        $view = new StandaloneView();
        $view->setRequest();
        $view->setTemplateSource('<f:link.page>foo</f:link.page>');
        $view->render();
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextCreatesNoLinkWithoutRoute(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:link.page>foo</f:link.page>');
        $result = $view->render();
        self::assertSame('foo', $result);
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextCreatesLinkWithRouteFromQueryString(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withQueryParams(['route' => 'web_layout']);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:link.page addQueryString="1" pageUid="42">foo</f:link.page>');
        $result = $view->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextCreatesLinkWithRouteFromAdditionalParams(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:link.page additionalParams="{\'route\': \'web_layout\'}" pageUid="42">foo</f:link.page>');
        $result = $view->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextCreatesLinkWithRouteFromRequest(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('dummy', ['_identifier' => 'web_layout']));
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:link.page pageUid="42">foo</f:link.page>');
        $result = $view->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextAddsSection(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('dummy', ['_identifier' => 'web_layout']));
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:link.page pageUid="42" section="mySection">foo</f:link.page>');
        $result = $view->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42#mySection">foo</a>', $result);
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextCreatesAbsoluteLink(): void
    {
        $request = new ServerRequest(null, null, 'php://input', [], ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => 'typo3/index.php']);
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('dummy', ['_identifier' => 'web_layout']));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:link.page pageUid="42" absolute="1">foo</f:link.page>');
        $result = $view->render();
        self::assertSame('<a href="http://localhost/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    /**
     * @test
     */
    public function renderInBackendExtbaseContextCreatesLinkWithId(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('module/web/layout', ['_identifier' => 'web_layout']));
        $request = $request->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($request);
        $GLOBALS['_GET']['id'] = '42';
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:link.page>foo</f:link.page>');
        $result = $view->render();
        self::assertSame('<a href="/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    /**
     * @test
     */
    public function renderInBackendExtbaseContextCreatesAbsoluteLinkWithId(): void
    {
        $request = new ServerRequest(null, null, 'php://input', [], ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => 'typo3/index.php']);
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('route', new Route('module/web/layout', ['_identifier' => 'web_layout']));
        $request = $request->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($request);
        $GLOBALS['_GET']['id'] = '42';
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:link.page absolute="1">foo</f:link.page>');
        $result = $view->render();
        self::assertSame('<a href="http://localhost/typo3/module/web/layout?token=dummyToken&amp;id=42">foo</a>', $result);
    }

    public function renderDataProvider(): array
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
            'link to page sub page' => [
                '<f:link.page pageUid="3">linkMe</f:link.page>',
                '<a href="/dummy-1-2/dummy-1-2-3">linkMe</a>',
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
