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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Tests\Fixture\StringBackedEnum;
use TYPO3\CMS\Extbase\Tests\Unit\Mvc\Web\Routing\Fixtures\EntityFixture;
use TYPO3\CMS\Extbase\Tests\Unit\Mvc\Web\Routing\Fixtures\ValueObjectFixture;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UriBuilderTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $backendEntryPointResolver = new BackendEntryPointResolver();
        $requestContextFactory = new RequestContextFactory($backendEntryPointResolver);
        $router = new Router($requestContextFactory, $backendEntryPointResolver);
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
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject
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
        self::assertEquals(['test' => 'arguments'], $subject->getArguments());
        self::assertEquals('testSection', $subject->getSection());
        self::assertEquals('testFormat', $subject->getFormat());
        self::assertTrue($subject->getCreateAbsoluteUri());
        self::assertEquals('https', $subject->getAbsoluteUriScheme());
        self::assertTrue($subject->getAddQueryString());
        self::assertEquals(['test' => 'addQueryStringExcludeArguments'], $subject->getArgumentsToBeExcludedFromQueryString());
        self::assertEquals('testArgumentPrefix', $subject->getArgumentPrefix());
        self::assertTrue($subject->getLinkAccessRestrictedPages());
        self::assertEquals(123, $subject->getTargetPageUid());
        self::assertEquals(321, $subject->getTargetPageType());
        self::assertTrue($subject->getNoCache());
    }

    #[Test]
    public function uriForPrefixesArgumentsWithExtensionAndPluginNameAndSetsControllerArgument(): void
    {
        $expectedArguments = ['foo' => 'bar', 'baz' => ['extbase' => 'fluid'], 'controller' => 'SomeController'];
        $GLOBALS['TSFE'] = null;
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $subject = $this->getAccessibleMock(UriBuilder::class, ['build'], [$mockExtensionService]);
        $subject->setRequest($mockRequest);
        $subject->uriFor(null, ['foo' => 'bar', 'baz' => ['extbase' => 'fluid']], 'SomeController', 'SomeExtension', 'SomePlugin');
        self::assertEquals($expectedArguments, $subject->getArguments());
    }

    #[Test]
    public function uriForRecursivelyMergesAndOverrulesControllerArgumentsWithArguments(): void
    {
        $arguments = ['foo' => 'bar', 'additionalParam' => 'additionalValue'];
        $controllerArguments = ['foo' => 'overruled', 'baz' => ['extbase' => 'fluid']];
        $expectedArguments = ['foo' => 'overruled', 'baz' => ['extbase' => 'fluid'], 'controller' => 'SomeController', 'additionalParam' => 'additionalValue'];
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $subject = $this->getAccessibleMock(UriBuilder::class, ['build'], [$mockExtensionService]);
        $subject->setRequest($mockRequest);
        $subject->setArguments($arguments);
        $subject->uriFor(null, $controllerArguments, 'SomeController', 'SomeExtension', 'SomePlugin');
        self::assertEquals($expectedArguments, $subject->getArguments());
    }

    #[Test]
    public function uriForOnlySetsActionArgumentIfSpecified(): void
    {
        $expectedArguments = ['controller' => 'SomeController'];
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $subject = $this->getAccessibleMock(UriBuilder::class, ['build'], [$mockExtensionService]);
        $subject->setRequest($mockRequest);
        $subject->uriFor(null, [], 'SomeController', 'SomeExtension', 'SomePlugin');
        self::assertEquals($expectedArguments, $subject->getArguments());
    }

    #[Test]
    public function uriForSetsControllerFromRequestIfControllerIsNotSet(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->expects(self::once())->method('getControllerName')->willReturn('SomeControllerFromRequest');
        $expectedArguments = ['controller' => 'SomeControllerFromRequest'];
        $mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $subject = $this->getAccessibleMock(UriBuilder::class, ['build'], [$mockExtensionService]);
        $subject->setRequest($mockRequest);
        $subject->uriFor(null, [], null, 'SomeExtension', 'SomePlugin');
        self::assertEquals($expectedArguments, $subject->getArguments());
    }

    #[Test]
    public function uriForSetsExtensionNameFromRequestIfExtensionNameIsNotSet(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->expects(self::once())->method('getControllerExtensionName')->willReturn('SomeExtensionNameFromRequest');
        $expectedArguments = ['controller' => 'SomeController'];
        $mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $subject = $this->getAccessibleMock(UriBuilder::class, ['build'], [$mockExtensionService]);
        $subject->setRequest($mockRequest);
        $subject->uriFor(null, [], 'SomeController', null, 'SomePlugin');
        self::assertEquals($expectedArguments, $subject->getArguments());
    }

    #[Test]
    public function uriForSetsPluginNameFromRequestIfPluginNameIsNotSetInFrontend(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $mockExtensionService->expects(self::once())->method('getPluginNamespace')->willReturn('tx_someextension_somepluginnamefromrequest');
        $mockRequest->expects(self::once())->method('getPluginName')->willReturn('SomePluginNameFromRequest');
        $expectedArguments = ['tx_someextension_somepluginnamefromrequest' => ['controller' => 'SomeController']];
        $mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $subject = $this->getAccessibleMock(UriBuilder::class, ['build'], [$mockExtensionService]);
        $subject->setRequest($mockRequest);
        $subject->uriFor(null, [], 'SomeController', 'SomeExtension');
        self::assertEquals($expectedArguments, $subject->getArguments());
    }
    #[Test]
    public function uriForSetsPluginNameFromRequestIfPluginNameIsNotSet(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->expects(self::once())->method('getPluginName')->willReturn('SomePluginNameFromRequest');
        $expectedArguments = ['controller' => 'SomeController'];
        $mockRequest->method('getAttribute')->with('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $subject = $this->getAccessibleMock(UriBuilder::class, ['build'], [$mockExtensionService]);
        $subject->setRequest($mockRequest);
        $subject->uriFor(null, [], 'SomeController', 'SomeExtension');
        self::assertEquals($expectedArguments, $subject->getArguments());
    }

    #[Test]
    public function buildBackendUriKeepsQueryParametersIfAddQueryStringIsSet(): void
    {
        $extbaseParameters = new ExtbaseRequestParameters();
        $serverRequest = $this->getRequestWithRouteAttribute()->withQueryParams(['id' => 'pageId', 'foo' => 'bar'])
            ->withAttribute('extbase', $extbaseParameters);
        $request = new Request($serverRequest);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $subject->setAddQueryString(true);
        $expectedResult = '/typo3/test/Path?token=dummyToken&id=pageId&foo=bar';
        self::assertEquals($expectedResult, $subject->buildBackendUri());
        // Check "untrusted" setting, which in BE context is the same as setting the property to TRUE
        $subject->setAddQueryString('untrusted');
        self::assertEquals($expectedResult, $subject->buildBackendUri());
    }

    #[Test]
    public function buildBackendUriRouteAttributeOverrulesGetParameterIfAddQueryStringIsSet(): void
    {
        $extbaseParameters = new ExtbaseRequestParameters();
        $serverRequest = $this->getRequestWithRouteAttribute('module_key2')
            ->withQueryParams(['route' => 'module_key', 'id' => 'pageId', 'foo' => 'bar'])
            ->withAttribute('extbase', $extbaseParameters);
        $request = new Request($serverRequest);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $subject->setAddQueryString(true);
        $expectedResult = '/typo3/test/Path2?token=dummyToken&id=pageId&foo=bar';
        self::assertEquals($expectedResult, $subject->buildBackendUri());
        // Check "untrusted" setting, which in BE context is the same as setting the property to TRUE
        $subject->setAddQueryString('untrusted');
        self::assertEquals($expectedResult, $subject->buildBackendUri());
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
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $subject->setAddQueryString(true);
        $subject->setArgumentsToBeExcludedFromQueryString($excluded);
        self::assertEquals($expected, $subject->buildBackendUri());
        // Check "untrusted" setting, which in BE context is the same as setting the property to TRUE
        $subject->setAddQueryString('untrusted');
        self::assertEquals($expected, $subject->buildBackendUri());
    }

    #[Test]
    public function buildBackendUriKeepsModuleQueryParametersIfAddQueryStringIsNotSet(): void
    {
        $serverRequest = $this->getRequestWithRouteAttribute()
            ->withQueryParams(['id' => 'pageId', 'foo' => 'bar'])
            ->withAttribute('extbase', new ExtbaseRequestParameters())
        ;
        $request = new Request($serverRequest);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $expectedResult = '/typo3/test/Path?token=dummyToken&id=pageId';
        $actualResult = $subject->buildBackendUri();
        self::assertEquals($expectedResult, $subject->buildBackendUri());
    }

    #[Test]
    public function buildBackendUriMergesAndOverrulesQueryParametersWithArguments(): void
    {
        $serverRequest = $this->getRequestWithRouteAttribute()
            ->withQueryParams(['id' => 'pageId', 'foo' => 'bar'])
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request =  new Request($serverRequest);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $subject->setArguments(['route' => 'module_key2', 'somePrefix' => ['bar' => 'baz']]);
        $expectedResult = '/typo3/test/Path2?token=dummyToken&id=pageId&somePrefix%5Bbar%5D=baz';
        self::assertEquals($expectedResult, $subject->buildBackendUri());
    }

    #[Test]
    public function buildBackendUriConvertsDomainObjectsAfterArgumentsHaveBeenMerged(): void
    {
        $serverRequest = $this->getRequestWithRouteAttribute()
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request =  new Request($serverRequest);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $mockDomainObject = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockDomainObject->_set('uid', 123);
        $subject->setArguments(['somePrefix' => ['someDomainObject' => $mockDomainObject]]);
        $expectedResult = '/typo3/test/Path?token=dummyToken&somePrefix%5BsomeDomainObject%5D=123';
        self::assertEquals($expectedResult, $subject->buildBackendUri());
    }

    #[Test]
    public function buildBackendUriConvertsEnumAfterArgumentsHaveBeenMerged(): void
    {
        $serverRequest = $this->getRequestWithRouteAttribute()
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request =  new Request($serverRequest);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $subject->setArguments(['somePrefix' => ['someDomainObject' => StringBackedEnum::FirstCase]]);
        $expectedResult = '/typo3/test/Path?token=dummyToken&somePrefix%5BsomeDomainObject%5D=' . StringBackedEnum::FirstCase->value;
        $actualResult = $subject->buildBackendUri();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function buildBackendUriRespectsSection(): void
    {
        $serverRequest = $this->getRequestWithRouteAttribute()->withAttribute('extbase', new ExtbaseRequestParameters());
        $request =  new Request($serverRequest);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $subject->setSection('someSection');
        $expectedResult = '/typo3/test/Path?token=dummyToken#someSection';
        self::assertEquals($expectedResult, $subject->buildBackendUri());
    }

    #[Test]
    public function buildBackendUriCreatesAbsoluteUrisIfSpecified(): void
    {
        $request = $this->getRequestWithRouteAttribute(baseUri: 'http://baseuri/typo3/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromServerParams($_SERVER))
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $mvcRequest = new Request($request);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($mvcRequest);
        $subject->setCreateAbsoluteUri(true);
        $subject->setArguments(['route' => 'module_key']);
        $backendUriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);
        $backendUriBuilder->setRequestContext(new RequestContext(host: 'baseuri/typo3'));
        $expectedResult = 'http://baseuri/typo3/test/Path?token=dummyToken';
        self::assertSame($expectedResult, $subject->buildBackendUri());
    }

    #[Test]
    public function buildBackendRespectsGivenControllerActionArguments(): void
    {
        $serverRequest = $this
            ->getRequestWithRouteAttribute()
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($serverRequest);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $subject->setArguments(['controller' => 'controller', 'action' => 'action']);
        $expectedResult = '/typo3/test/Path/Controller/action?token=dummyToken';
        self::assertSame($expectedResult, $subject->buildBackendUri());
    }

    #[Test]
    public function buildBackendOverwritesSubRouteIdentifierControllerActionArguments(): void
    {
        $serverRequest = $this
            ->getRequestWithRouteAttribute('module_key.controller_action')
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = new Request($serverRequest);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $subject->setArguments(['controller' => 'controller2', 'action' => 'action2']);
        $expectedResult = '/typo3/test/Path/Controller2/action2?token=dummyToken';
        self::assertSame($expectedResult, $subject->buildBackendUri());
    }

    #[Test]
    public function buildFrontendUriCreatesRelativeUrisByDefault(): void
    {
        $mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $mockContentObject->expects(self::once())->method('createUrl')->willReturn('relative/uri');
        $serverRequest = (new ServerRequest())
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('currentContentObject', $mockContentObject);
        $request = new Request($serverRequest);
        $expectedResult = 'relative/uri';
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        self::assertSame($expectedResult, $subject->buildFrontendUri());
    }

    #[Test]
    public function buildFrontendUriDoesNotStripLeadingSlashesFromRelativeUris(): void
    {
        $mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $mockContentObject->expects(self::once())->method('createUrl')->willReturn('/relative/uri');
        $serverRequest = (new ServerRequest())
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('currentContentObject', $mockContentObject);
        $request = new Request($serverRequest);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $expectedResult = '/relative/uri';
        self::assertSame($expectedResult, $subject->buildFrontendUri());
    }

    #[Test]
    public function buildFrontendUriCreatesAbsoluteUrisIfSpecified(): void
    {
        $mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $mockContentObject->expects(self::once())->method('createUrl')->with(['foo' => 'bar', 'forceAbsoluteUrl' => true])->willReturn('http://baseuri/relative/uri');
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')->with('currentContentObject')->willReturn($mockContentObject);
        $subject = $this->getAccessibleMock(UriBuilder::class, ['buildTypolinkConfiguration'], [], '', false);
        $subject->setRequest($mockRequest);
        $subject->expects(self::once())->method('buildTypolinkConfiguration')->willReturn(['foo' => 'bar']);
        $subject->setCreateAbsoluteUri(true);
        $expectedResult = 'http://baseuri/relative/uri';
        self::assertSame($expectedResult, $subject->buildFrontendUri());
    }

    #[Test]
    public function buildFrontendUriSetsAbsoluteUriSchemeIfSpecified(): void
    {
        $mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $mockContentObject->expects(self::once())
            ->method('createUrl')
            ->with(['foo' => 'bar', 'forceAbsoluteUrl' => true, 'forceAbsoluteUrl.' => ['scheme' => 'someScheme']])
            ->willReturn('http://baseuri/relative/uri');
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('getAttribute')->with('currentContentObject')->willReturn($mockContentObject);
        $subject = $this->getAccessibleMock(UriBuilder::class, ['buildTypolinkConfiguration'], [], '', false);
        $subject->setRequest($mockRequest);
        $subject->expects(self::once())->method('buildTypolinkConfiguration')->willReturn(['foo' => 'bar']);
        $subject->setCreateAbsoluteUri(true);
        $subject->setAbsoluteUriScheme('someScheme');
        $expectedResult = 'http://baseuri/relative/uri';
        self::assertSame($expectedResult, $subject->buildFrontendUri());
    }

    #[Test]
    public function buildFrontendUriDoesNotSetAbsoluteUriSchemeIfCreateAbsoluteUriIsFalse(): void
    {
        $uriBuilder = $this->getAccessibleMock(UriBuilder::class, ['buildTypolinkConfiguration'], [], '', false);
        $mockRequest = $this->createMock(Request::class);
        $mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $mockRequest->method('getAttribute')->with('currentContentObject')->willReturn($mockContentObject);
        $uriBuilder->setRequest($mockRequest);
        $uriBuilder->expects(self::once())->method('buildTypolinkConfiguration')->willReturn(['foo' => 'bar']);
        $mockContentObject->expects(self::once())->method('createUrl')->with(['foo' => 'bar'])->willReturn('http://baseuri/relative/uri');
        $uriBuilder->setCreateAbsoluteUri(false);
        $uriBuilder->setAbsoluteUriScheme('someScheme');
        $expectedResult = 'http://baseuri/relative/uri';
        self::assertSame($expectedResult, $uriBuilder->buildFrontendUri());
    }

    #[Test]
    public function buildFrontendUriConvertsEnumAfterArgumentsHaveBeenMerged(): void
    {
        $mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $serverRequest = $this->getRequestWithRouteAttribute()
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request =  new Request($serverRequest);
        $mockContentObject->method('createUrl')->willReturn('/benni');
        $request = $request->withAttribute('currentContentObject', $mockContentObject);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        $subject->setTargetPageUid(1);
        $subject->setArguments(['somePrefix' => ['someDomainObject' => StringBackedEnum::FirstCase]]);
        self::assertEquals([
            'parameter' => 1,
            'additionalParams' => '&somePrefix%5BsomeDomainObject%5D=' . StringBackedEnum::FirstCase->value,
        ], $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function resetSetsAllOptionsToTheirDefaultValue(): void
    {
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject
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
        $subject->reset();
        self::assertEquals([], $subject->getArguments());
        self::assertEquals('', $subject->getSection());
        self::assertEquals('', $subject->getFormat());
        self::assertFalse($subject->getCreateAbsoluteUri());
        self::assertFalse($subject->getAddQueryString());
        self::assertEquals([], $subject->getArgumentsToBeExcludedFromQueryString());
        self::assertEquals('', $subject->getArgumentPrefix());
        self::assertFalse($subject->getLinkAccessRestrictedPages());
        self::assertNull($subject->getTargetPageUid());
        self::assertEquals(0, $subject->getTargetPageType());
        self::assertFalse($subject->getNoCache());
        self::assertFalse($subject->getNoCache());
        self::assertNull($subject->getAbsoluteUriScheme());
    }

    #[Test]
    public function buildTypolinkConfigurationRespectsSpecifiedTargetPageUid(): void
    {
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setTargetPageUid(321);
        $expectedConfiguration = ['parameter' => 321];
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function buildTypolinkConfigurationUsesCurrentPageUidIfTargetPageUidIsNotSet(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setId(123);
        $request = (new ServerRequest())
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $request = (new Request($request));
        $currentContentObject = new ContentObjectRenderer();
        $currentContentObject->setRequest($request);
        $request = $request->withAttribute('currentContentObject', $currentContentObject);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $expectedConfiguration = ['parameter' => 123];
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setRequest($request);
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function buildTypolinkConfigurationProperlySetsAdditionalArguments(): void
    {
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setTargetPageUid(123);
        $subject->setArguments(['foo' => 'bar', 'baz' => ['extbase' => 'fluid']]);
        $expectedConfiguration = ['parameter' => 123, 'additionalParams' => '&foo=bar&baz%5Bextbase%5D=fluid'];
        $actualConfiguration = $subject->_call('buildTypolinkConfiguration');
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
    public function buildTypolinkConfigurationProperlySetsAddQueryString(bool|string|int|null $addQueryString, array $expectedConfiguration): void
    {
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setTargetPageUid(123);
        if ($addQueryString !== null) {
            $subject->setAddQueryString($addQueryString);
        }
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function buildTypolinkConfigurationConvertsDomainObjects(): void
    {
        $mockDomainObject1 = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockDomainObject1->_set('uid', 123);
        $mockDomainObject2 = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockDomainObject2->_set('uid', 321);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setTargetPageUid(123);
        $subject->setArguments([
            'someDomainObject' => $mockDomainObject1,
            'baz' => ['someOtherDomainObject' => $mockDomainObject2],
        ]);
        $expectedConfiguration = ['parameter' => 123, 'additionalParams' => '&someDomainObject=123&baz%5BsomeOtherDomainObject%5D=321'];
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function buildTypolinkConfigurationResolvesPageTypeFromFormat(): void
    {
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $mockExtensionService->method('getTargetPageTypeByFormat')->with('SomeExtensionNameFromRequest', 'txt')->willReturn(2);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->expects(self::once())->method('getControllerExtensionName')->willReturn('SomeExtensionNameFromRequest');
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [$mockExtensionService]);
        $subject->setRequest($mockRequest);
        $subject->setTargetPageUid(123);
        $subject->setFormat('txt');
        $expectedConfiguration = ['parameter' => '123,2'];
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function buildTypolinkConfigurationResolvesDefaultPageTypeFromFormatIfNoMappingIsConfigured(): void
    {
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $mockExtensionService->method('getTargetPageTypeByFormat')->with(null, 'txt')->willReturn(0);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [$mockExtensionService]);
        $subject->setTargetPageUid(123);
        $subject->setFormat('txt');
        $mockRequest = $this->createMock(Request::class);
        $subject->setRequest($mockRequest);
        $expectedConfiguration = ['parameter' => '123,0'];
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function buildTypolinkConfigurationResolvesDefaultPageTypeFromFormatIfFormatIsNotMapped(): void
    {
        $mockExtensionService = $this->createMock(ExtensionService::class);
        $mockExtensionService->method('getTargetPageTypeByFormat')->with(null, 'txt')->willReturn(0);
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [$mockExtensionService]);
        $subject->setTargetPageUid(123);
        $subject->setFormat('txt');
        $subject->setRequest($this->createMock(Request::class));
        $expectedConfiguration = ['parameter' => '123,0'];
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function buildTypolinkConfigurationDisablesCacheHashIfNoCacheIsSet(): void
    {
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setTargetPageUid(123);
        $subject->setNoCache(true);
        $expectedConfiguration = ['parameter' => 123, 'no_cache' => 1];
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function buildTypolinkConfigurationConsidersSection(): void
    {
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setTargetPageUid(123);
        $subject->setSection('SomeSection');
        $expectedConfiguration = ['parameter' => 123, 'section' => 'SomeSection'];
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function buildTypolinkConfigurationLinkAccessRestrictedPagesSetting(): void
    {
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->setTargetPageUid(123);
        $subject->setLinkAccessRestrictedPages(true);
        $expectedConfiguration = ['parameter' => 123, 'linkAccessRestrictedPages' => 1];
        self::assertEquals($expectedConfiguration, $subject->_call('buildTypolinkConfiguration'));
    }

    #[Test]
    public function convertDomainObjectsToIdentityArraysConvertsDomainObjects(): void
    {
        $mockDomainObject1 = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockDomainObject1->_set('uid', 123);
        $mockDomainObject2 = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockDomainObject2->_set('uid', 321);
        $expectedResult = ['foo' => ['bar' => 'baz'], 'domainObject1' => '123', 'second' => ['domainObject2' => '321']];
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $actualResult = $subject->_call('convertDomainObjectsToIdentityArrays', ['foo' => ['bar' => 'baz'], 'domainObject1' => $mockDomainObject1, 'second' => ['domainObject2' => $mockDomainObject2]]);
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function convertDomainObjectsToIdentityArraysConvertsObjectStoragesWithDomainObjects(): void
    {
        $objectStorage  = new ObjectStorage();
        $mockChildObject1 = $this->getAccessibleMock(AbstractEntity::class, null);
        $mockChildObject1->_set('uid', 123);
        $objectStorage->attach($mockChildObject1);
        $expectedResult = ['foo' => ['bar' => 'baz'], 'objectStorage' => ['123']];
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        self::assertEquals($expectedResult, $subject->_call('convertDomainObjectsToIdentityArrays', ['foo' => ['bar' => 'baz'], 'objectStorage' => $objectStorage]));
    }

    #[Test]
    public function conversionOfTransientObjectsIsInvoked(): void
    {
        $mockValueObject = new ValueObjectFixture();
        $mockValueObject->name = 'foo';
        $subject = $this->getAccessibleMock(UriBuilder::class, ['convertTransientObjectToArray'], [], '', false);
        $subject->expects(self::once())->method('convertTransientObjectToArray')->willReturn(['foo' => 'bar']);
        $expectedResult = ['object' => ['foo' => 'bar']];
        self::assertEquals($expectedResult, $subject->_call('convertDomainObjectsToIdentityArrays', ['object' => $mockValueObject]));
    }

    #[Test]
    public function conversionOfTransientObjectsThrowsExceptionForOtherThanValueObjects(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionCode(1260881688);
        $mockEntity = new EntityFixture();
        $mockEntity->name = 'foo';
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $subject->_call('convertDomainObjectsToIdentityArrays', ['object' => $mockEntity]);
    }

    #[Test]
    public function transientObjectsAreConvertedToAnArrayOfProperties(): void
    {
        $mockValueObject = new ValueObjectFixture();
        $mockValueObject->name = 'foo';
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        $expectedResult = ['name' => 'foo', 'object' => null, 'uid' => null, 'pid' => null];
        self::assertEquals($expectedResult, $subject->_call('convertTransientObjectToArray', $mockValueObject));
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
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
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
        self::assertEquals($expectedResult, $subject->_call('convertTransientObjectToArray', $mockValueObject));
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
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
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
        self::assertEquals($expectedResult, $subject->_call('convertTransientObjectToArray', $mockValueObject));
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
        $subject = $this->getAccessibleMock(UriBuilder::class, null, [], '', false);
        self::assertIsArray($subject->_call('convertIteratorToArray', $iterator));
    }
}
