<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Http;

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

use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Http\RouteDispatcher;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Tests\Unit\Http\Fixtures\RouteDispatcherClassFixture;
use TYPO3\CMS\Backend\Tests\Unit\Http\Fixtures\RouteDispatcherClassInvokeFixture;
use TYPO3\CMS\Backend\Tests\Unit\Http\Fixtures\RouteDispatcherClassWithoutInvokeFixture;
use TYPO3\CMS\Backend\Tests\Unit\Http\Fixtures\RouteDispatcherStaticClassFixture;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RouteDispatcherTest extends UnitTestCase
{
    public function tearDown(): void
    {
        FormProtectionFactory::purgeInstances();
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function dispatchThrowsExceptionIfTargetIsNotCallable()
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $routerProphecy = $this->prophesize(Router::class);
        GeneralUtility::setSingletonInstance(Router::class, $routerProphecy->reveal());
        $routeProphecy = $this->prophesize(Route::class);
        $routerProphecy->matchRequest($requestProphecy->reveal())->willReturn($routeProphecy->reveal());
        $routeProphecy->getOption('access')->willReturn('public');
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $target = 42;
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1425381442);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsTargetIfTargetIsArray()
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $routerProphecy = $this->prophesize(Router::class);
        GeneralUtility::setSingletonInstance(Router::class, $routerProphecy->reveal());
        $routeProphecy = $this->prophesize(Route::class);
        $routerProphecy->matchRequest($requestProphecy->reveal())->willReturn($routeProphecy->reveal());
        $routeProphecy->getOption('access')->willReturn('public');
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $target = [
            RouteDispatcherClassFixture::class,
            'mainAction'
        ];
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756142);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsTargetIfTargetIsClosure()
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $routerProphecy = $this->prophesize(Router::class);
        GeneralUtility::setSingletonInstance(Router::class, $routerProphecy->reveal());
        $routeProphecy = $this->prophesize(Route::class);
        $routerProphecy->matchRequest($requestProphecy->reveal())->willReturn($routeProphecy->reveal());
        $routeProphecy->getOption('access')->willReturn('public');
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $target = function (ServerRequestInterface $request) {
            throw new \RuntimeException('I have been called. Good!', 1520756466);
        };
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756466);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsTargetIfTargetIsClassImplementingInvoke()
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $routerProphecy = $this->prophesize(Router::class);
        GeneralUtility::setSingletonInstance(Router::class, $routerProphecy->reveal());
        $routeProphecy = $this->prophesize(Route::class);
        $routerProphecy->matchRequest($requestProphecy->reveal())->willReturn($routeProphecy->reveal());
        $routeProphecy->getOption('access')->willReturn('public');
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $target = RouteDispatcherClassInvokeFixture::class;
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756623);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsTargetIfTargetIsInContainer()
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $routerProphecy = $this->prophesize(Router::class);
        GeneralUtility::setSingletonInstance(Router::class, $routerProphecy->reveal());
        $routeProphecy = $this->prophesize(Route::class);
        $routerProphecy->matchRequest($requestProphecy->reveal())->willReturn($routeProphecy->reveal());
        $routeProphecy->getOption('access')->willReturn('public');
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());

        $target = 'routedispatcher.classinvokefixture';
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has($target)->willReturn(true);
        $containerProphecy->get($target)->willReturn(new RouteDispatcherClassInvokeFixture);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756623);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchThrowsExceptionIfTargetWithClassNameOnlyDoesNotImplementInvoke()
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $routerProphecy = $this->prophesize(Router::class);
        GeneralUtility::setSingletonInstance(Router::class, $routerProphecy->reveal());
        $routeProphecy = $this->prophesize(Route::class);
        $routerProphecy->matchRequest($requestProphecy->reveal())->willReturn($routeProphecy->reveal());
        $routeProphecy->getOption('access')->willReturn('public');
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $target = RouteDispatcherClassWithoutInvokeFixture::class;
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1442431631);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsClassMethodCombinationGivenAsString()
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $routerProphecy = $this->prophesize(Router::class);
        GeneralUtility::setSingletonInstance(Router::class, $routerProphecy->reveal());
        $routeProphecy = $this->prophesize(Route::class);
        $routerProphecy->matchRequest($requestProphecy->reveal())->willReturn($routeProphecy->reveal());
        $routeProphecy->getOption('access')->willReturn('public');
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $target = RouteDispatcherClassFixture::class . '::mainAction';
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756142);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal(), $responseProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsStaticClassMethodCombinationGivenAsString()
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $routerProphecy = $this->prophesize(Router::class);
        GeneralUtility::setSingletonInstance(Router::class, $routerProphecy->reveal());
        $routeProphecy = $this->prophesize(Route::class);
        $routerProphecy->matchRequest($requestProphecy->reveal())->willReturn($routeProphecy->reveal());
        $routeProphecy->getOption('access')->willReturn('public');
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $target = RouteDispatcherStaticClassFixture::class . '::mainAction';
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520757000);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal(), $responseProphecy->reveal());
    }
}
