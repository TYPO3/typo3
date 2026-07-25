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

namespace TYPO3\CMS\Backend\Tests\Unit\Module\AccessGate;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\AccessGate\UserGate;
use TYPO3\CMS\Backend\Module\ModuleAccessResult;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UserGateTest extends UnitTestCase
{
    #[Test]
    public function abstainForNonUserAccess(): void
    {
        $module = self::createStub(ModuleInterface::class);
        $module->method('getAccess')->willReturn('admin');
        $user = self::createStub(BackendUserAuthentication::class);

        $subject = new UserGate(new ModuleRegistry([]));
        self::assertSame(ModuleAccessResult::Abstain, $subject->decide($module, $user));
    }

    #[Test]
    public function grantedForAdmin(): void
    {
        $module = self::createStub(ModuleInterface::class);
        $module->method('getAccess')->willReturn('user');
        $module->method('getIdentifier')->willReturn('some_module');
        $user = self::createStub(BackendUserAuthentication::class);
        $user->method('isAdmin')->willReturn(true);

        $subject = new UserGate(new ModuleRegistry([]));
        self::assertSame(ModuleAccessResult::Granted, $subject->decide($module, $user));
    }

    #[Test]
    public function grantedWhenUserHasModulePermission(): void
    {
        $module = self::createStub(ModuleInterface::class);
        $module->method('getAccess')->willReturn('user');
        $module->method('getIdentifier')->willReturn('some_module');
        $user = $this->createMock(BackendUserAuthentication::class);
        $user->method('isAdmin')->willReturn(false);
        $user->expects($this->atLeastOnce())->method('check')->with('modules', 'some_module')->willReturn(true);

        $subject = new UserGate(new ModuleRegistry([]));
        self::assertSame(ModuleAccessResult::Granted, $subject->decide($module, $user));
    }

    #[Test]
    public function deniedWhenUserLacksModulePermission(): void
    {
        $module = self::createStub(ModuleInterface::class);
        $module->method('getAccess')->willReturn('user');
        $module->method('getIdentifier')->willReturn('some_module');
        $user = self::createStub(BackendUserAuthentication::class);
        $user->method('isAdmin')->willReturn(false);
        $user->method('check')->willReturn(false);

        $subject = new UserGate(new ModuleRegistry([]));
        self::assertSame(ModuleAccessResult::Denied, $subject->decide($module, $user));
    }
}
