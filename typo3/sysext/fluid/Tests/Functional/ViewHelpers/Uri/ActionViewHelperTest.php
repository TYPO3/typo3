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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ActionViewHelperTest extends FunctionalTestCase
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
    public function renderThrowsExceptionWithoutARequest(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1690360598);
        $view = new StandaloneView();
        $view->setRequest();
        $view->setTemplateSource('<f:uri.action />');
        $view->render();
    }

    #[Test]
    public function renderInFrontendCoreContextThrowsExceptionWithIncompleteArguments(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('routing', new PageArguments(1, '0', []));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1639819692);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:uri.action />');
        $view->render();
    }

    #[Test]
    public function renderInBackendCoreContextThrowsExceptionWithIncompleteArguments(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withQueryParams(['route' => 'web_layout']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1690360598);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:uri.action />');
        $view->render();
    }

    public static function renderInFrontendWithCoreContextAndAllNecessaryExtbaseArgumentsDataProvider(): \Generator
    {
        yield 'link to root page with plugin' => [
            '<f:uri.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show" />',
            '/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c',
        ];

        yield 'link to root page with plugin and section' => [
            '<f:uri.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show" section="c13" />',
            '/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c#c13',
        ];

        yield 'link to root page with page type' => [
            '<f:uri.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show" pageType="1234" />',
            '/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;type=1234&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c',
        ];
    }

    #[DataProvider('renderInFrontendWithCoreContextAndAllNecessaryExtbaseArgumentsDataProvider')]
    #[Test]
    public function renderInFrontendWithCoreContextAndAllNecessaryExtbaseArguments(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('routing', new PageArguments(1, '0', ['untrusted' => 123]));
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource($template);
        $result = $view->render();
        self::assertSame($expected, $result);
    }

    public static function renderInFrontendWithExtbaseContextDataProvider(): \Generator
    {
        // with all extbase arguments provided
        yield 'link to root page with plugin' => [
            '<f:uri.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show" />',
            '/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c',
        ];

        yield 'link to root page with plugin and section' => [
            '<f:uri.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show" section="c13" />',
            '/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c#c13',
        ];

        yield 'link to root page with page type' => [
            '<f:uri.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show" pageType="1234" />',
            '/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;type=1234&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c',
        ];
        // without all extbase arguments provided
        yield 'renderWillProvideEmptyATagForNonValidLinkTarget' => [
            '<f:uri.action />',
            '/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3',
        ];
        yield 'link to root page in extbase context' => [
            '<f:uri.action pageUid="1" />',
            '/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3',
        ];
        yield 'link to root page with section' => [
            '<f:uri.action pageUid="1" section="c13" />',
            '/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3#c13',
        ];
        yield 'link to root page with page type in extbase context' => [
            '<f:uri.action pageUid="1" pageType="1234" />',
            '/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;type=1234&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3',
        ];
        yield 'link to root page with untrusted query arguments' => [
            '<f:uri.action addQueryString="untrusted" />',
            '/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;untrusted=123&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3',
        ];
        yield 'link to page sub page' => [
            '<f:uri.action pageUid="3" />',
            '/dummy-1-2/dummy-1-2-3?tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=d9289022f99f8cbc8080832f61e46509',
        ];
        yield 'arguments one level' => [
            '<f:uri.action pageUid="3" arguments="{foo: \'bar\'}" />',
            '/dummy-1-2/dummy-1-2-3?tx_examples_haiku%5Bcontroller%5D=Detail&amp;tx_examples_haiku%5Bfoo%5D=bar&amp;cHash=74dd4635cee85b19b67cd9b497ec99e9',
        ];
        yield 'additional parameters two levels' => [
            '<f:uri.action pageUid="3" additionalParams="{tx_examples_haiku: {action: \'show\', haiku: 42}}" />',
            '/dummy-1-2/dummy-1-2-3?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;tx_examples_haiku%5Bhaiku%5D=42&amp;cHash=aefc37bc2323ebd8c8e39c222adb7413',
        ];
    }

    #[DataProvider('renderInFrontendWithExtbaseContextDataProvider')]
    #[Test]
    public function renderInFrontendWithExtbaseContext(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setConfigArray([]);
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Examples');
        $extbaseRequestParameters->setControllerName('Detail');
        $extbaseRequestParameters->setControllerActionName('show');
        $extbaseRequestParameters->setPluginName('Haiku');
        $contentObject = $this->get(ContentObjectRenderer::class);
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('routing', new PageArguments(1, '0', ['untrusted' => 123]));
        $request = $request->withAttribute('extbase', $extbaseRequestParameters);
        $request = $request->withAttribute('currentContentObject', $contentObject);
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $contentObject->setRequest($request);
        $configurationManager = $this->get(ConfigurationManagerInterface::class);
        $configurationManager->setRequest($request);
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $request = new Request($request);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource($template);
        $result = $view->render();
        self::assertSame($expected, $result);
    }
}
