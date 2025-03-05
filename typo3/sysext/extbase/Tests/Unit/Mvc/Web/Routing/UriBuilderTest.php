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

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Web\Routing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\FormProtection\DisabledFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Routing\RequestContextFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Tests\Unit\Mvc\Web\Routing\Fixtures\EntityFixture;
use TYPO3\CMS\Extbase\Tests\Unit\Mvc\Web\Routing\Fixtures\ValueObjectFixture;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UriBuilderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    private ContentObjectRenderer&MockObject $mockContentObject;
    private Request&MockObject $mockRequest;
    private ExtensionService&MockObject $mockExtensionService;
    private UriBuilder&MockObject&AccessibleObjectInterface $subject;

    /**
     * @throws \InvalidArgumentException
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TSFE'] = $this->createMock(TypoScriptFrontendController::class);
        $this->mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $this->mockRequest = $this->createMock(Request::class);
        $this->mockExtensionService = $this->createMock(ExtensionService::class);
        $this->subject = $this->getAccessibleMock(UriBuilder::class, ['build']);
        $this->subject->setRequest($this->mockRequest);
        $this->subject->injectConfigurationManager($this->createMock(ConfigurationManagerInterface::class));
        $this->subject->injectExtensionService($this->mockExtensionService);
        $this->subject->_set('contentObject', $this->mockContentObject);
        $requestContextFactory = new RequestContextFactory(new BackendEntryPointResolver());
        $router = new Router($requestContextFactory);
        $router->addRoute('module_key', new Route('/test/Path', []));
        $router->addRoute('module_key.controller_action', new Route('/test/Path/Controller/action', []));
        $router->addRoute('module_key.controller2_action2', new Route('/test/Path/Controller2/action2', []));
        $router->addRoute('module_key2', new Route('/test/Path2', []));
        $router->addRoute('', new Route('', []));
        $formProtectionFactory = $this->createMock(FormProtectionFactory::class);
        $formProtectionFactory->method('createForType')->willReturn(new DisabledFormProtection());
        GeneralUtility::setSingletonInstance(BackendUriBuilder::class, new BackendUriBuilder($router, $formProtectionFactory, $requestContextFactory));
    }

    private function getRequestWithRouteAttribute(string $routeIdentifier = 'module_key', string $baseUri = ''): ServerRequestInterface
    {
        return (new ServerRequest(new Uri($baseUri)))->withAttribute('route', new Route('/test/Path', ['_identifier' => $routeIdentifier]));
    }

    #[Test]
    public function settersAndGettersWorkAsExpected(): void
    {
        $this->subject
            ->reset()
            ->setArguments(['test' => 'arguments'])
            ->setSection('testSection')
            ->setFormat('testFormat')
            ->setCreateAbsoluteUri(true)
            ->setAbsoluteUriScheme('https')
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(['test' => 'addQueryStringExcludeArguments'])
            ->setArgumentPrefix('testArgumentPrefix')
            ->setLinkAccessRestrictedPages(true)
            ->setTargetPageUid(123)
            ->setTargetPageType(321)
            ->setNoCache(true);
        self::assertEquals(['test' => 'arguments'], $this->subject->getArguments());
        self::assertEquals('testSection', $this->subject->getSection());
        self::assertEquals('testFormat', $this->subject->getFormat());
        self::assertTrue($this->subject->getCreateAbsoluteUri());
        self::assertEquals('https', $this->subject->getAbsoluteUriScheme());
        self::assertTrue($this->subject->getAddQueryString());
        self::assertEquals(['test' => 'addQueryStringExcludeArguments'], $this->subject->getArgumentsToBeExcludedFromQueryString());
        self::assertEquals('testArgumentPrefix', $this->subject->getArgumentPrefix());
        self::assertTrue($this->subject->getLinkAccessRestrictedPages());
        self::assertEquals(123, $this->subject->getTargetPageUid());
        self::assertEquals(321, $this->subject->getTargetPageType());
        self::assertTrue($this->subject->getNoCache());
    }

    #[Test]
    public function uriForPrefixesArgumentsWithExtensionAndPluginNameAndSetsControllerArgument(): void
    {
        $expectedArguments = ['foo' => 'bar', 'baz' => ['extbase' => 'fluid'], 'controller' => 'SomeController', 'route' => 'SomePlugin'];
        $GLOBALS['TSFE'] = null;
        $this->mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->subject->uriFor(null, ['foo' => 'bar', 'baz' => ['extbase' => 'fluid']], 'SomeController', 'SomeExtension', 'SomePlugin');
        self::assertEquals($expectedArguments, $this->subject->getArguments());
    }

    #[Test]
    public function uriForRecursivelyMergesAndOverrulesControllerArgumentsWithArguments(): void
    {
        $arguments = ['foo' => 'bar', 'additionalParam' => 'additionalValue'];
        $controllerArguments = ['foo' => 'overruled', 'baz' => ['extbase' => 'fluid']];
        $expectedArguments = ['foo' => 'overruled', 'baz' => ['extbase' => 'fluid'], 'controller' => 'SomeController', 'additionalParam' => 'additionalValue', 'route' => 'SomePlugin'];
        $this->mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->subject->setArguments($arguments);
        $this->subject->uriFor(null, $controllerArguments, 'SomeController', 'SomeExtension', 'SomePlugin');
        self::assertEquals($expectedArguments, $this->subject->getArguments());
    }

    #[Test]
    public function uriForOnlySetsActionArgumentIfSpecified(): void
    {
        $expectedArguments = ['controller' => 'SomeController', 'route' => 'SomePlugin'];
        $this->mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->subject->uriFor(null, [], 'SomeController', 'SomeExtension', 'SomePlugin');
        self::assertEquals($expectedArguments, $this->subject->getArguments());
    }

    #[Test]
    public function uriForSetsControllerFromRequestIfControllerIsNotSet(): void
    {
        $this->mockRequest->expects(self::once())->method('getControllerName')->willReturn('SomeControllerFromRequest');
        $expectedArguments = ['controller' => 'SomeControllerFromRequest', 'route' => 'SomePlugin'];
        $this->mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->subject->uriFor(null, [], null, 'SomeExtension', 'SomePlugin');
        self::assertEquals($expectedArguments, $this->subject->getArguments());
    }

    #[Test]
    public function uriForSetsExtensionNameFromRequestIfExtensionNameIsNotSet(): void
    {
        $this->mockRequest->expects(self::once())->method('getControllerExtensionName')->willReturn('SomeExtensionNameFromRequest');
        $expectedArguments = ['controller' => 'SomeController', 'route' => 'SomePlugin'];
        $this->mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->subject->uriFor(null, [], 'SomeController', null, 'SomePlugin');
        self::assertEquals($expectedArguments, $this->subject->getArguments());
    }

    #[Test]
    public function uriForSetsPluginNameFromRequestIfPluginNameIsNotSetInFrontend(): void
    {
        $this->mockExtensionService->expects(self::once())->method('getPluginNamespace')->willReturn('tx_someextension_somepluginnamefromrequest');
        $this->mockRequest->expects(self::once())->method('getPluginName')->willReturn('SomePluginNameFromRequest');
        $expectedArguments = ['tx_someextension_somepluginnamefromrequest' => ['controller' => 'SomeController']];
        $this->mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $this->subject->uriFor(null, [], 'SomeController', 'SomeExtension');
        self::assertEquals($expectedArguments, $this->subject->getArguments());
    }
    #[Test]
    public function uriForSetsPluginNameFromRequestIfPluginNameIsNotSet(): void
    {
        $this->mockRequest->expects(self::once())->method('getPluginName')->willReturn('SomePluginNameFromRequest');
        $expectedArguments = ['controller' => 'SomeController', 'route' => 'SomePluginNameFromRequest'];
        $this->mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->subject->uriFor(null, [], 'SomeController', 'SomeExtension');
        self::assertEquals($expectedArguments, $this->subject->getArguments());
    }

    #[Test]
    public function buildBackendUriKeepsQueryParametersIfAddQueryStringIsSet(): void
    {
        $extbaseParameters = new ExtbaseRequestParameters();
        $serverRequest = $this->getRequestWithRouteAttribute()->withQueryParams(['id' => 'pageId', 'foo' => 'bar'])
            ->withAttribute('extbase', $extbaseParameters);
        $request = new Request($serverRequest);
        $this->subject->setRequest($request);
        $this->subject->setAddQueryString(true);
        $expectedResult = '/typo3/test/Path?token=dummyToken&id=pageId&foo=bar';
        $actualResult = $this->subject->buildBackendUri();
        self::assertEquals($expectedResult, $actualResult);

        // Check "untrusted" setting, which in BE context is the same as setting the property to TRUE
        $this->subject->setAddQueryString('untrusted');
        $actualResult = $this->subject->buildBackendUri();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function buildBackendUriRouteAttributeOverrulesGetParameterIfAddQueryStringIsSet(): void
    {
        $extbaseParameters = new ExtbaseRequestParameters();
        $serverRequest = $this->getRequestWithRouteAttribute('module_key2')
            ->withQueryParams(['route' => 'module_key', 'id' => 'pageId', 'foo' => 'bar'])
            ->withAttribute('extbase', $extbaseParameters);
        $request = new Request($serverRequest);
        $this->subject->setRequest($request);
        $this->subject->setAddQueryString(true);
        $expectedResult = '/typo3/test/Path2?token=dummyToken&id=pageId&foo=bar';
        $actualResult = $this->subject->buildBackendUri();
        self::assertEquals($expectedResult, $actualResult);

        // Check "untrusted" setting, which in BE context is the same as setting the property to TRUE
        $this->subject->setAddQueryString('untrusted');
        $actualResult = $this->subject->buildBackendUri();
        self::assertEquals($expectedResult, $actualResult);
    }

    public static function buildBackendUriRemovesSpecifiedQueryParametersIfArgumentsToBeExcludedFromQueryStringIsSetDataProvider(): array
    {
        return [
            'Arguments to be excluded in the beginning' => [
                [
                    'id' => 'pageId',
                    'myparam' => 'pageId',
                    'route' => 'module_key',
                    'foo' => 'bar',
                ],
                [
                    'myparam',
                    'id',
                ],
                '/typo3/test/Path?token=dummyToken&foo=bar',
            ],
            'Arguments to be excluded in the end' => [
                [
                    'foo' => 'bar',
                    'route' => 'module_key',
                    'id' => 'pageId',
                    'myparam' => 'anyway',
                ],
                [
                    'id',
                    'myparam',
                ],
                '/typo3/test/Path?token=dummyToken&foo=bar',
            ],
            'Arguments in nested array to be excluded' => [
                [
                    'tx_foo' => [
                        'bar' => 'baz',
                    ],
                    'id' => 'pageId',
                    'route' => 'module_key',
                ],
                [
                    'id',
                    'tx_foo[bar]',
                ],
                '/typo3/test/Path?token=dummyToken',
            ],
            'Arguments in multidimensional array to be excluded' => [
                [
                    'tx_foo' => [
                        'bar' => [
                            'baz' => 'bay',
                        ],
                    ],
                    'id' => 'pageId',
                    'route' => 'module_key',
                ],
                [
                    'id',
                    'tx_foo[bar][baz]',
                ],
                '/typo3/test/Path?token=dummyToken',
            ],
        ];
    }

    #[DataProvider('buildBackendUriRemovesSpecifiedQueryParametersIfArgumentsToBeExcludedFromQueryStringIsSetDataProvider')]
    #[Test]
    public function buildBackendUriRemovesSpecifiedQueryParametersIfArgumentsToBeExcludedFromQueryStringIsSet(array $parameters, array $excluded, string $expected): void
    {
        $serverRequest = $this->getRequestWithRouteAttribute()
            ->withQueryParams($parameters)
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($serverRequest);
        $this->subject->setRequest($request);
        $this->subject->setAddQueryString(true);
        $this->subject->setArgumentsToBeExcludedFromQueryString($excluded);
        $actualResult = $this->subject->buildBackendUri();
        self::assertEquals($expected, $actualResult);

        // Check "untrusted" setting, which in BE context is the same as setting the property to TRUE
        $this->subject->setAddQueryString('untrusted');
        $actualResult = $this->subject->buildBackendUri();
        self::assertEquals($expected, $actualResult);
    }

    #[Test]
    public function buildBackendUriKeepsModuleQueryParametersIfAddQueryStringIsNotSet(): void
    {
        $serverRequest = $this->getRequestWithRouteAttribute()
            ->withQueryParams(['id' => 'pageId', 'foo' => 'bar'])
            ->withAttribute('extbase', new ExtbaseRequestParameters())
        ;
        $request = new Request($serverRequest);
        $this->subject->setRequest($request);
        $expectedResult = '/typo3/test/Path?token=dummyToken&id=pageId';
        $actualResult = $this->subject->buildBackendUri();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function buildBackendUriMergesAndOverrulesQueryParametersWithArguments(): void
    {
        $serverRequest = $this->getRequestWithRouteAttribute()
            ->withQueryParams(['id' => 'pageId', 'foo' => 'bar'])
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request =  new Request($serverRequest);
        $this->subject->setRequest($request);
        $this->subject->setArguments(['route' => 'module_key2', 'somePrefix' => ['bar' => 'baz']]);
        $expectedResult = '/typo3/test/Path2?token=dummyToken&id=pageId&somePrefix%5Bbar%5D=baz';
        $actualResult = $this->subject->buildBackendUri();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function buildBackendUriConvertsDomainObjectsAfterArgumentsHaveBeenMerged(): void
    {
        $serverRequest = $this->getRequestWithRouteAttribute()
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request =  new Request($serverRequest);
        $this->subject->setRequest($request);
        $mockDomainObject = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockDomainObject->_set('uid', '123');
        $this->subject->setArguments(['somePrefix' => ['someDomainObject' => $mockDomainObject]]);
        $expectedResult = '/typo3/test/Path?token=dummyToken&somePrefix%5BsomeDomainObject%5D=123';
        $actualResult = $this->subject->buildBackendUri();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function buildBackendUriRespectsSection(): void
    {
        $serverRequest = $this->getRequestWithRouteAttribute()
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request =  new Request($serverRequest);
        $this->subject->setRequest($request);
        $this->subject->setSection('someSection');
        $expectedResult = '/typo3/test/Path?token=dummyToken#someSection';
        $actualResult = $this->subject->buildBackendUri();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function buildBackendUriCreatesAbsoluteUrisIfSpecified(): void
    {
        $request = $this->getRequestWithRouteAttribute(baseUri: 'http://baseuri/typo3/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromServerParams($_SERVER))
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $mvcRequest = new Request($request);
        $this->subject->setRequest($mvcRequest);
        $this->subject->setCreateAbsoluteUri(true);
        $this->subject->setArguments(['route' => 'module_key']);
        $backendUriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);
        $backendUriBuilder->setRequestContext(new RequestContext(host: 'baseuri/typo3'));
        $expectedResult = 'http://baseuri/typo3/test/Path?token=dummyToken';
        $actualResult = $this->subject->buildBackendUri();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function buildBackendRespectsGivenControllerActionArguments(): void
    {
        $serverRequest = $this
            ->getRequestWithRouteAttribute()
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($serverRequest);
        $this->subject->setRequest($request);
        $this->subject->setArguments(['controller' => 'controller', 'action' => 'action']);
        $expectedResult = '/typo3/test/Path/Controller/action?token=dummyToken';
        $actualResult = $this->subject->buildBackendUri();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function buildBackendOverwritesSubRouteIdentifierControllerActionArguments(): void
    {
        $serverRequest = $this
            ->getRequestWithRouteAttribute('module_key.controller_action')
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($serverRequest);
        $this->subject->setRequest($request);
        $this->subject->setArguments(['controller' => 'controller2', 'action' => 'action2']);
        $expectedResult = '/typo3/test/Path/Controller2/action2?token=dummyToken';
        $actualResult = $this->subject->buildBackendUri();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function buildFrontendUriCreatesTypoLink(): void
    {
        $uriBuilder = $this->getAccessibleMock(UriBuilder::class, ['buildTypolinkConfiguration']);
        $uriBuilder->_set('contentObject', $this->mockContentObject);
        $uriBuilder->expects(self::once())->method('buildTypolinkConfiguration')->willReturn(['someTypoLinkConfiguration']);
        $this->mockContentObject->expects(self::once())->method('createUrl')->with(['someTypoLinkConfiguration']);
        $uriBuilder->buildFrontendUri();
    }

    #[Test]
    public function buildFrontendUriCreatesRelativeUrisByDefault(): void
    {
        $this->mockContentObject->expects(self::once())->method('createUrl')->willReturn('relative/uri');
        $expectedResult = 'relative/uri';
        $actualResult = $this->subject->buildFrontendUri();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function buildFrontendUriDoesNotStripLeadingSlashesFromRelativeUris(): void
    {
        $this->mockContentObject->expects(self::once())->method('createUrl')->willReturn('/relative/uri');
        $expectedResult = '/relative/uri';
        $actualResult = $this->subject->buildFrontendUri();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function buildFrontendUriCreatesAbsoluteUrisIfSpecified(): void
    {
        $uriBuilder = $this->getAccessibleMock(UriBuilder::class, ['buildTypolinkConfiguration']);
        $uriBuilder->_set('contentObject', $this->mockContentObject);
        $uriBuilder->expects(self::once())->method('buildTypolinkConfiguration')->willReturn(['foo' => 'bar']);
        $this->mockContentObject->expects(self::once())->method('createUrl')->with(['foo' => 'bar', 'forceAbsoluteUrl' => true])->willReturn('http://baseuri/relative/uri');
        $uriBuilder->setCreateAbsoluteUri(true);
        $expectedResult = 'http://baseuri/relative/uri';
        $actualResult = $uriBuilder->buildFrontendUri();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function buildFrontendUriSetsAbsoluteUriSchemeIfSpecified(): void
    {
        $uriBuilder = $this->getAccessibleMock(UriBuilder::class, ['buildTypolinkConfiguration']);
        $uriBuilder->_set('contentObject', $this->mockContentObject);
        $uriBuilder->expects(self::once())->method('buildTypolinkConfiguration')->willReturn(['foo' => 'bar']);
        $this->mockContentObject->expects(self::once())->method('createUrl')->with(['foo' => 'bar', 'forceAbsoluteUrl' => true, 'forceAbsoluteUrl.' => ['scheme' => 'someScheme']])->willReturn('http://baseuri/relative/uri');
        $uriBuilder->setCreateAbsoluteUri(true);
        $uriBuilder->setAbsoluteUriScheme('someScheme');
        $expectedResult = 'http://baseuri/relative/uri';
        $actualResult = $uriBuilder->buildFrontendUri();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function buildFrontendUriDoesNotSetAbsoluteUriSchemeIfCreateAbsoluteUriIsFalse(): void
    {
        $uriBuilder = $this->getAccessibleMock(UriBuilder::class, ['buildTypolinkConfiguration']);
        $uriBuilder->_set('contentObject', $this->mockContentObject);
        $uriBuilder->expects(self::once())->method('buildTypolinkConfiguration')->willReturn(['foo' => 'bar']);
        $this->mockContentObject->expects(self::once())->method('createUrl')->with(['foo' => 'bar'])->willReturn('http://baseuri/relative/uri');
        $uriBuilder->setCreateAbsoluteUri(false);
        $uriBuilder->setAbsoluteUriScheme('someScheme');
        $expectedResult = 'http://baseuri/relative/uri';
        $actualResult = $uriBuilder->buildFrontendUri();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function resetSetsAllOptionsToTheirDefaultValue(): void
    {
        $this->subject
            ->setArguments(['test' => 'arguments'])
            ->setSection('testSection')
            ->setFormat('someFormat')
            ->setCreateAbsoluteUri(true)
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(['test' => 'addQueryStringExcludeArguments'])
            ->setLinkAccessRestrictedPages(true)
            ->setTargetPageUid(123)
            ->setTargetPageType(321)
            ->setNoCache(true)
            ->setArgumentPrefix('testArgumentPrefix')
            ->setAbsoluteUriScheme('test')
        ;

        $this->subject->reset();
        self::assertEquals([], $this->subject->getArguments());
        self::assertEquals('', $this->subject->getSection());
        self::assertEquals('', $this->subject->getFormat());
        self::assertFalse($this->subject->getCreateAbsoluteUri());
        self::assertFalse($this->subject->getAddQueryString());
        self::assertEquals([], $this->subject->getArgumentsToBeExcludedFromQueryString());
        self::assertEquals('', $this->subject->getArgumentPrefix());
        self::assertFalse($this->subject->getLinkAccessRestrictedPages());
        self::assertNull($this->subject->getTargetPageUid());
        self::assertEquals(0, $this->subject->getTargetPageType());
        self::assertFalse($this->subject->getNoCache());
        self::assertFalse($this->subject->getNoCache());
        self::assertNull($this->subject->getAbsoluteUriScheme());
    }

    #[Test]
    public function buildTypolinkConfigurationRespectsSpecifiedTargetPageUid(): void
    {
        $GLOBALS['TSFE']->id = 123;
        $this->subject->setTargetPageUid(321);
        $expectedConfiguration = ['parameter' => 321];
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');
        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    #[Test]
    public function buildTypolinkConfigurationUsesCurrentPageUidIfTargetPageUidIsNotSet(): void
    {
        $GLOBALS['TSFE']->id = 123;
        $expectedConfiguration = ['parameter' => 123];
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');
        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    #[Test]
    public function buildTypolinkConfigurationProperlySetsAdditionalArguments(): void
    {
        $this->subject->setTargetPageUid(123);
        $this->subject->setArguments(['foo' => 'bar', 'baz' => ['extbase' => 'fluid']]);
        $expectedConfiguration = ['parameter' => 123, 'additionalParams' => '&foo=bar&baz%5Bextbase%5D=fluid'];
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');
        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    public static function buildTypolinkConfigurationProperlySetsAddQueryStringDataProvider(): \Generator
    {
        yield 'AddQueryString not set' => [
            null,
            ['parameter' => 123],
        ];
        yield 'AddQueryString set to FALSE' => [
            false,
            ['parameter' => 123],
        ];
        yield 'AddQueryString set to "FALSE"' => [
            'false',
            ['parameter' => 123],
        ];
        yield 'AddQueryString set to 0' => [
            0,
            ['parameter' => 123],
        ];
        yield 'AddQueryString set to "0"' => [
            '0',
            ['parameter' => 123],
        ];
        yield 'AddQueryString set to TRUE' => [
            true,
            ['parameter' => 123, 'addQueryString' => 1],
        ];
        yield 'AddQueryString set to "TRUE"' => [
            'true',
            ['parameter' => 123, 'addQueryString' => 'true'],
        ];
        yield 'AddQueryString set to 1' => [
            1,
            ['parameter' => 123, 'addQueryString' => 1],
        ];
        yield 'AddQueryString set to "1"' => [
            '1',
            ['parameter' => 123, 'addQueryString' => 1],
        ];
        yield 'AddQueryString set to \'untrusted\'' => [
            'untrusted',
            ['parameter' => 123, 'addQueryString' => 'untrusted'],
        ];
    }

    #[DataProvider('buildTypolinkConfigurationProperlySetsAddQueryStringDataProvider')]
    #[Test]
    public function buildTypolinkConfigurationProperlySetsAddQueryString(
        bool|string|int|null $addQueryString,
        array $expectedConfiguration
    ): void {
        $this->subject->setTargetPageUid(123);
        if ($addQueryString !== null) {
            $this->subject->setAddQueryString($addQueryString);
        }
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');
        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    #[Test]
    public function buildTypolinkConfigurationConvertsDomainObjects(): void
    {
        $mockDomainObject1 = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockDomainObject1->_set('uid', '123');
        $mockDomainObject2 = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockDomainObject2->_set('uid', '321');
        $this->subject->setTargetPageUid(123);
        $this->subject->setArguments(['someDomainObject' => $mockDomainObject1, 'baz' => ['someOtherDomainObject' => $mockDomainObject2]]);
        $expectedConfiguration = ['parameter' => 123, 'additionalParams' => '&someDomainObject=123&baz%5BsomeOtherDomainObject%5D=321'];
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');
        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    #[Test]
    public function buildTypolinkConfigurationResolvesPageTypeFromFormat(): void
    {
        $this->subject->setTargetPageUid(123);
        $this->subject->setFormat('txt');
        $this->mockRequest->expects(self::once())->method('getControllerExtensionName')->willReturn('SomeExtensionNameFromRequest');

        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $mockConfigurationManager->method('getConfiguration')
            ->willReturn(['formatToPageTypeMapping' => ['txt' => 2]]);
        $this->subject->injectConfigurationManager($mockConfigurationManager);

        $this->mockExtensionService->method('getTargetPageTypeByFormat')
            ->with('SomeExtensionNameFromRequest', 'txt')
            ->willReturn(2);

        $expectedConfiguration = ['parameter' => '123,2'];
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');
        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    #[Test]
    public function buildTypolinkConfigurationResolvesDefaultPageTypeFromFormatIfNoMappingIsConfigured(): void
    {
        $this->subject->setTargetPageUid(123);
        $this->subject->setFormat('txt');

        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $mockConfigurationManager->method('getConfiguration')->willReturn([]);
        $this->subject->_set('configurationManager', $mockConfigurationManager);

        $this->mockExtensionService->method('getTargetPageTypeByFormat')
            ->with(null, 'txt')
            ->willReturn(0);

        $expectedConfiguration = ['parameter' => '123,0'];
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');

        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    #[Test]
    public function buildTypolinkConfigurationResolvesDefaultPageTypeFromFormatIfFormatIsNotMapped(): void
    {
        $this->subject->setTargetPageUid(123);
        $this->subject->setFormat('txt');

        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $mockConfigurationManager->method('getConfiguration')
            ->willReturn(['formatToPageTypeMapping' => ['pdf' => 2]]);
        $this->subject->_set('configurationManager', $mockConfigurationManager);

        $this->mockExtensionService->method('getTargetPageTypeByFormat')
            ->with(null, 'txt')
            ->willReturn(0);

        $expectedConfiguration = ['parameter' => '123,0'];
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');

        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    #[Test]
    public function buildTypolinkConfigurationDisablesCacheHashIfNoCacheIsSet(): void
    {
        $this->subject->setTargetPageUid(123);
        $this->subject->setNoCache(true);
        $expectedConfiguration = ['parameter' => 123, 'no_cache' => 1];
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');
        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    #[Test]
    public function buildTypolinkConfigurationConsidersSection(): void
    {
        $this->subject->setTargetPageUid(123);
        $this->subject->setSection('SomeSection');
        $expectedConfiguration = ['parameter' => 123, 'section' => 'SomeSection'];
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');
        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    #[Test]
    public function buildTypolinkConfigurationLinkAccessRestrictedPagesSetting(): void
    {
        $this->subject->setTargetPageUid(123);
        $this->subject->setLinkAccessRestrictedPages(true);
        $expectedConfiguration = ['parameter' => 123, 'linkAccessRestrictedPages' => 1];
        $actualConfiguration = $this->subject->_call('buildTypolinkConfiguration');
        self::assertEquals($expectedConfiguration, $actualConfiguration);
    }

    #[Test]
    public function convertDomainObjectsToIdentityArraysConvertsDomainObjects(): void
    {
        $mockDomainObject1 = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockDomainObject1->_set('uid', '123');
        $mockDomainObject2 = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockDomainObject2->_set('uid', '321');
        $expectedResult = ['foo' => ['bar' => 'baz'], 'domainObject1' => '123', 'second' => ['domainObject2' => '321']];
        $actualResult = $this->subject->_call('convertDomainObjectsToIdentityArrays', ['foo' => ['bar' => 'baz'], 'domainObject1' => $mockDomainObject1, 'second' => ['domainObject2' => $mockDomainObject2]]);
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function convertDomainObjectsToIdentityArraysConvertsObjectStoragesWithDomainObjects(): void
    {
        $objectStorage  = new ObjectStorage();
        $mockChildObject1 = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockChildObject1->_set('uid', '123');
        $objectStorage->attach($mockChildObject1);
        $expectedResult = ['foo' => ['bar' => 'baz'], 'objectStorage' => ['123']];
        $actualResult = $this->subject->_call('convertDomainObjectsToIdentityArrays', ['foo' => ['bar' => 'baz'], 'objectStorage' => $objectStorage]);
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function conversionOfTransientObjectsIsInvoked(): void
    {
        $mockValueObject = new ValueObjectFixture();
        $mockValueObject->name = 'foo';
        $mockUriBuilder = $this->getAccessibleMock(UriBuilder::class, ['convertTransientObjectToArray']);
        $mockUriBuilder->expects(self::once())->method('convertTransientObjectToArray')->willReturn(['foo' => 'bar']);
        $actualResult = $mockUriBuilder->_call('convertDomainObjectsToIdentityArrays', ['object' => $mockValueObject]);
        $expectedResult = ['object' => ['foo' => 'bar']];
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function conversionOfTransientObjectsThrowsExceptionForOtherThanValueObjects(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionCode(1260881688);
        $mockEntity = new EntityFixture();
        $mockEntity->name = 'foo';
        $mockUriBuilder = $this->getAccessibleMock(UriBuilder::class, null);
        $mockUriBuilder->_call('convertDomainObjectsToIdentityArrays', ['object' => $mockEntity]);
    }

    #[Test]
    public function transientObjectsAreConvertedToAnArrayOfProperties(): void
    {
        $mockValueObject = new ValueObjectFixture();
        $mockValueObject->name = 'foo';
        $uriBuilder = new UriBuilder();
        $actualResult = $uriBuilder->convertTransientObjectToArray($mockValueObject);
        $expectedResult = ['name' => 'foo', 'object' => null, 'uid' => null, 'pid' => null];
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function transientObjectsWithObjectStorageAreConvertedToAnArrayOfProperties(): void
    {
        $mockValueObject = new ValueObjectFixture();
        $objectStorage = new ObjectStorage();
        $mockValueObject->name = 'foo';
        $mockValueObject2 = new ValueObjectFixture();
        $mockValueObject2->name = 'bar';
        $objectStorage->attach($mockValueObject2);
        $mockValueObject->object = $objectStorage;
        $uriBuilder = new UriBuilder();
        $actualResult = $uriBuilder->convertTransientObjectToArray($mockValueObject);
        $expectedResult = [
            'name' => 'foo',
            'object' => [
                [
                    'name' => 'bar',
                    'uid' => null,
                    'pid' => null,
                    'object' => null,
                ],
            ],
            'uid' => null,
            'pid' => null,
        ];
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function transientObjectsAreRecursivelyConverted(): void
    {
        $mockInnerValueObject2 = new ValueObjectFixture();
        $mockInnerValueObject2->name = 'foo';
        $mockInnerValueObject2->uid = 99;
        $mockInnerValueObject1 = new ValueObjectFixture();
        $mockInnerValueObject1->object = $mockInnerValueObject2;
        $mockValueObject = new ValueObjectFixture();
        $mockValueObject->object = $mockInnerValueObject1;
        $uriBuilder = new UriBuilder();
        $actualResult = $uriBuilder->convertTransientObjectToArray($mockValueObject);
        $expectedResult = [
            'name' => null,
            'object' => [
                'name' => null,
                'object' => 99,
                'uid' => null,
                'pid' => null,
            ],
            'uid' => null,
            'pid' => null,
        ];
        self::assertEquals($expectedResult, $actualResult);
    }

    public static function convertIteratorToArrayConvertsIteratorsToArrayProvider(): array
    {
        return [
            'Extbase ObjectStorage' => [new ObjectStorage()],
            'SplObjectStorage' => [new \SplObjectStorage()],
            'ArrayIterator' => [new \ArrayIterator()],
        ];
    }

    #[DataProvider('convertIteratorToArrayConvertsIteratorsToArrayProvider')]
    #[Test]
    public function convertIteratorToArrayConvertsIteratorsToArray($iterator): void
    {
        $result = $this->subject->_call('convertIteratorToArray', $iterator);
        self::assertIsArray($result);
    }
}
