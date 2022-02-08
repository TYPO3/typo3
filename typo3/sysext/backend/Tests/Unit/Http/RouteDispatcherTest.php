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

namespace TYPO3\CMS\Backend\Tests\Unit\Http;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Http\RouteDispatcher;
use TYPO3\CMS\Backend\Routing\Route;
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
    use ProphecyTrait;

    public function tearDown(): void
    {
        FormProtectionFactory::purgeInstances();
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function dispatchThrowsExceptionIfTargetIsNotCallable(): void
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $route = new Route('not important', ['access' => 'public', 'referrer' => false, 'target' => 42]);
        $requestProphecy->getAttribute('route')->willReturn($route);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1425381442);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsTargetIfTargetIsArray(): void
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());
        $target = [
            new RouteDispatcherClassFixture(),
            'mainAction',
        ];
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('route')->willReturn($route);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756142);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsTargetIfTargetIsClosure(): void
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $target = static function (ServerRequestInterface $request) {
            throw new \RuntimeException('I have been called. Good!', 1520756466);
        };
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('route')->willReturn($route);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756466);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsTargetIfTargetIsClassImplementingInvoke(): void
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());
        $target = RouteDispatcherClassInvokeFixture::class;
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('route')->willReturn($route);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756623);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsTargetIfTargetIsInContainer(): void
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $target = 'routedispatcher.classinvokefixture';
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('route')->willReturn($route);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has($target)->willReturn(true);
        $containerProphecy->get($target)->willReturn(new RouteDispatcherClassInvokeFixture());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756623);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchThrowsExceptionIfTargetWithClassNameOnlyDoesNotImplementInvoke(): void
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $target = RouteDispatcherClassWithoutInvokeFixture::class;
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('route')->willReturn($route);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1442431631);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsClassMethodCombinationGivenAsString(): void
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $target = RouteDispatcherClassFixture::class . '::mainAction';
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('route')->willReturn($route);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756142);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal());
    }

    /**
     * @test
     */
    public function dispatchCallsStaticClassMethodCombinationGivenAsString(): void
    {
        $formProtectionProphecy = $this->prophesize(AbstractFormProtection::class);
        $formProtectionProphecy->validateToken(Argument::cetera())->willReturn(true);
        FormProtectionFactory::set('default', $formProtectionProphecy->reveal());

        $target = RouteDispatcherStaticClassFixture::class . '::mainAction';
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getAttribute('route')->willReturn($route);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(Argument::any())->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520757000);

        $subject = new RouteDispatcher($containerProphecy->reveal());
        $subject->dispatch($requestProphecy->reveal());
    }
}
