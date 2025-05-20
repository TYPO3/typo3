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

use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Http\RouteDispatcher;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessFactory;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessStorage;
use TYPO3\CMS\Backend\Tests\Unit\Http\Fixtures\RouteDispatcherClassFixture;
use TYPO3\CMS\Backend\Tests\Unit\Http\Fixtures\RouteDispatcherClassInvokeFixture;
use TYPO3\CMS\Backend\Tests\Unit\Http\Fixtures\RouteDispatcherClassWithoutInvokeFixture;
use TYPO3\CMS\Backend\Tests\Unit\Http\Fixtures\RouteDispatcherStaticClassFixture;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\Security\ReferrerEnforcer;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RouteDispatcherTest extends UnitTestCase
{
    public function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function dispatchThrowsExceptionIfTargetIsNotCallable(): void
    {
        $formProtectionMock = $this->createMock(AbstractFormProtection::class);
        $formProtectionMock->method('validateToken')->with(self::anything())->willReturn(true);
        $formProtectionFactory = $this->createMock(FormProtectionFactory::class);
        $formProtectionFactory->method('createFromRequest')->willReturn($formProtectionMock);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $accessFactoryMock = $this->createMock(AccessFactory::class);
        $accessStorageMock = $this->createMock(AccessStorage::class);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with(self::anything())->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1425381442);

        $route = new Route('not important', ['access' => 'public', 'target' => 42]);
        $request = (new ServerRequest())->withAttribute('route', $route);

        $subject = new RouteDispatcher(
            $formProtectionFactory,
            $eventDispatcherMock,
            $accessFactoryMock,
            $accessStorageMock,
            new Features(),
            new ReferrerEnforcer(),
            $containerMock
        );
        $subject->dispatch($request);
    }

    #[Test]
    public function dispatchCallsTargetIfTargetIsArray(): void
    {
        $formProtectionMock = $this->createMock(AbstractFormProtection::class);
        $formProtectionMock->method('validateToken')->with(self::anything())->willReturn(true);
        $formProtectionFactory = $this->createMock(FormProtectionFactory::class);
        $formProtectionFactory->method('createFromRequest')->willReturn($formProtectionMock);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $accessFactoryMock = $this->createMock(AccessFactory::class);
        $accessStorageMock = $this->createMock(AccessStorage::class);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with(self::anything())->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756142);

        $target = [
            new RouteDispatcherClassFixture(),
            'mainAction',
        ];
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $request = (new ServerRequest())->withAttribute('route', $route);

        $subject = new RouteDispatcher(
            $formProtectionFactory,
            $eventDispatcherMock,
            $accessFactoryMock,
            $accessStorageMock,
            new Features(),
            new ReferrerEnforcer(),
            $containerMock
        );
        $subject->dispatch($request);
    }

    #[Test]
    public function dispatchCallsTargetIfTargetIsClosure(): void
    {
        $formProtectionMock = $this->createMock(AbstractFormProtection::class);
        $formProtectionMock->method('validateToken')->with(self::anything())->willReturn(true);
        $formProtectionFactory = $this->createMock(FormProtectionFactory::class);
        $formProtectionFactory->method('createFromRequest')->willReturn($formProtectionMock);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $accessFactoryMock = $this->createMock(AccessFactory::class);
        $accessStorageMock = $this->createMock(AccessStorage::class);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with(self::anything())->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756466);

        $target = static function (ServerRequestInterface $request) {
            throw new \RuntimeException('I have been called. Good!', 1520756466);
        };
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $request = (new ServerRequest())->withAttribute('route', $route);

        $subject = new RouteDispatcher(
            $formProtectionFactory,
            $eventDispatcherMock,
            $accessFactoryMock,
            $accessStorageMock,
            new Features(),
            new ReferrerEnforcer(),
            $containerMock
        );
        $subject->dispatch($request);
    }

    #[Test]
    public function dispatchCallsTargetIfTargetIsClassImplementingInvoke(): void
    {
        $formProtectionMock = $this->createMock(AbstractFormProtection::class);
        $formProtectionMock->method('validateToken')->with(self::anything())->willReturn(true);
        $formProtectionFactory = $this->createMock(FormProtectionFactory::class);
        $formProtectionFactory->method('createFromRequest')->willReturn($formProtectionMock);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $accessFactoryMock = $this->createMock(AccessFactory::class);
        $accessStorageMock = $this->createMock(AccessStorage::class);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with(self::anything())->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756623);

        $target = RouteDispatcherClassInvokeFixture::class;
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $request = (new ServerRequest())->withAttribute('route', $route);

        $subject = new RouteDispatcher(
            $formProtectionFactory,
            $eventDispatcherMock,
            $accessFactoryMock,
            $accessStorageMock,
            new Features(),
            new ReferrerEnforcer(),
            $containerMock
        );
        $subject->dispatch($request);
    }

    #[Test]
    public function dispatchCallsTargetIfTargetIsInContainer(): void
    {
        $formProtectionMock = $this->createMock(AbstractFormProtection::class);
        $formProtectionMock->method('validateToken')->with(self::anything())->willReturn(true);
        $formProtectionFactory = $this->createMock(FormProtectionFactory::class);
        $formProtectionFactory->method('createFromRequest')->willReturn($formProtectionMock);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $accessFactoryMock = $this->createMock(AccessFactory::class);
        $accessStorageMock = $this->createMock(AccessStorage::class);

        $target = 'routedispatcher.classinvokefixture';
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $request = (new ServerRequest())->withAttribute('route', $route);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with($target)->willReturn(true);
        $containerMock->method('get')->with($target)->willReturn(new RouteDispatcherClassInvokeFixture());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756623);

        $subject = new RouteDispatcher(
            $formProtectionFactory,
            $eventDispatcherMock,
            $accessFactoryMock,
            $accessStorageMock,
            new Features(),
            new ReferrerEnforcer(),
            $containerMock
        );
        $subject->dispatch($request);
    }

    #[Test]
    public function dispatchThrowsExceptionIfTargetWithClassNameOnlyDoesNotImplementInvoke(): void
    {
        $formProtectionMock = $this->createMock(AbstractFormProtection::class);
        $formProtectionMock->method('validateToken')->with(self::anything())->willReturn(true);
        $formProtectionFactory = $this->createMock(FormProtectionFactory::class);
        $formProtectionFactory->method('createFromRequest')->willReturn($formProtectionMock);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $accessFactoryMock = $this->createMock(AccessFactory::class);
        $accessStorageMock = $this->createMock(AccessStorage::class);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with(self::anything())->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1442431631);

        $target = RouteDispatcherClassWithoutInvokeFixture::class;
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $request = (new ServerRequest())->withAttribute('route', $route);

        $subject = new RouteDispatcher(
            $formProtectionFactory,
            $eventDispatcherMock,
            $accessFactoryMock,
            $accessStorageMock,
            new Features(),
            new ReferrerEnforcer(),
            $containerMock
        );
        $subject->dispatch($request);
    }

    #[Test]
    public function dispatchCallsClassMethodCombinationGivenAsString(): void
    {
        $formProtectionMock = $this->createMock(AbstractFormProtection::class);
        $formProtectionMock->method('validateToken')->with(self::anything())->willReturn(true);
        $formProtectionFactory = $this->createMock(FormProtectionFactory::class);
        $formProtectionFactory->method('createFromRequest')->willReturn($formProtectionMock);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $accessFactoryMock = $this->createMock(AccessFactory::class);
        $accessStorageMock = $this->createMock(AccessStorage::class);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with(self::anything())->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520756142);

        $target = RouteDispatcherClassFixture::class . '::mainAction';
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $request = (new ServerRequest())->withAttribute('route', $route);

        $subject = new RouteDispatcher(
            $formProtectionFactory,
            $eventDispatcherMock,
            $accessFactoryMock,
            $accessStorageMock,
            new Features(),
            new ReferrerEnforcer(),
            $containerMock
        );
        $subject->dispatch($request);
    }

    #[Test]
    public function dispatchCallsStaticClassMethodCombinationGivenAsString(): void
    {
        $formProtectionMock = $this->createMock(AbstractFormProtection::class);
        $formProtectionMock->method('validateToken')->with(self::anything())->willReturn(true);
        $formProtectionFactory = $this->createMock(FormProtectionFactory::class);
        $formProtectionFactory->method('createFromRequest')->willReturn($formProtectionMock);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $accessFactoryMock = $this->createMock(AccessFactory::class);
        $accessStorageMock = $this->createMock(AccessStorage::class);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('has')->with(self::anything())->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1520757000);

        $target = RouteDispatcherStaticClassFixture::class . '::mainAction';
        $route = new Route('not important', ['access' => 'public', 'target' => $target]);
        $request = (new ServerRequest())->withAttribute('route', $route);

        $subject = new RouteDispatcher(
            $formProtectionFactory,
            $eventDispatcherMock,
            $accessFactoryMock,
            $accessStorageMock,
            new Features(),
            new ReferrerEnforcer(),
            $containerMock
        );
        $subject->dispatch($request);
    }
}
