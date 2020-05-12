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
    public function tearDown()
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
        $routeProphecy->getOption('referrer')->willReturn(false);
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());

        $target = 42;
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1425381442);

        $subject = new RouteDispatcher();
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
        $routeProphecy->getOption('referrer')->willReturn(false);
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());

        $target = RouteDispatcherClassFixture::class . '::mainAction';
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756142);

        $subject = new RouteDispatcher();
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
        $routeProphecy->getOption('referrer')->willReturn(false);
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());

        $target = static function (ServerRequestInterface $request) {
            throw new \RuntimeException('I have been called. Good!', 1520756466);
        };
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', Argument::type(\Closure::class))->willReturn($requestProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756466);

        $subject = new RouteDispatcher();
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
        $routeProphecy->getOption('referrer')->willReturn(false);
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());

        $target = RouteDispatcherClassInvokeFixture::class;
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756623);

        $subject = new RouteDispatcher();
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
        $routeProphecy->getOption('referrer')->willReturn(false);
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());

        $target = RouteDispatcherClassWithoutInvokeFixture::class;
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1442431631);

        $subject = new RouteDispatcher();
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
        $routeProphecy->getOption('referrer')->willReturn(false);
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());

        $target = RouteDispatcherClassFixture::class . '::mainAction';
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756142);

        $subject = new RouteDispatcher();
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
        $routeProphecy->getOption('referrer')->willReturn(false);
        $routeProphecy->getOption('module')->willReturn(false);
        $requestProphecy->withAttribute('route', $routeProphecy->reveal())->willReturn($requestProphecy->reveal());
        $requestProphecy->getAttribute('route')->willReturn($routeProphecy->reveal());

        $target = RouteDispatcherStaticClassFixture::class . '::mainAction';
        $routeProphecy->getOption('target')->willReturn($target);
        $requestProphecy->withAttribute('target', $target)->willReturn($requestProphecy->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520757000);

        $subject = new RouteDispatcher();
        $subject->dispatch($requestProphecy->reveal(), $responseProphecy->reveal());
    }
}
