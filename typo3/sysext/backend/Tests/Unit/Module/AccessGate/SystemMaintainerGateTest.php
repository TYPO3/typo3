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
use TYPO3\CMS\Backend\Module\AccessGate\SystemMaintainerGate;
use TYPO3\CMS\Backend\Module\ModuleAccessResult;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SystemMaintainerGateTest extends UnitTestCase
{
    private SystemMaintainerGate $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SystemMaintainerGate();
    }

    #[Test]
    public function abstainForNonSystemMaintainerAccess(): void
    {
        $module = self::createStub(ModuleInterface::class);
        $module->method('getAccess')->willReturn('admin');
        $user = self::createStub(BackendUserAuthentication::class);

        self::assertSame(ModuleAccessResult::Abstain, $this->subject->decide($module, $user));
    }

    #[Test]
    public function grantedForSystemMaintainer(): void
    {
        $module = self::createStub(ModuleInterface::class);
        $module->method('getAccess')->willReturn('systemMaintainer');
        $user = self::createStub(BackendUserAuthentication::class);
        $user->method('isSystemMaintainer')->willReturn(true);

        self::assertSame(ModuleAccessResult::Granted, $this->subject->decide($module, $user));
    }

    #[Test]
    public function deniedForNonSystemMaintainer(): void
    {
        $module = self::createStub(ModuleInterface::class);
        $module->method('getAccess')->willReturn('systemMaintainer');
        $user = self::createStub(BackendUserAuthentication::class);
        $user->method('isSystemMaintainer')->willReturn(false);

        self::assertSame(ModuleAccessResult::Denied, $this->subject->decide($module, $user));
    }
}
