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

namespace TYPO3\CMS\Backend\Tests\Unit\Module;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ModuleAccessGateInterface;
use TYPO3\CMS\Backend\Module\ModuleAccessGateRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModuleAccessGateRegistryTest extends UnitTestCase
{
    #[Test]
    public function hasReturnsFalseForUnregisteredGate(): void
    {
        $registry = new ModuleAccessGateRegistry();
        self::assertFalse($registry->has('nonexistent'));
    }

    #[Test]
    public function hasReturnsTrueForRegisteredGate(): void
    {
        $gate = self::createStub(ModuleAccessGateInterface::class);
        $registry = new ModuleAccessGateRegistry(['myGate' => $gate]);
        self::assertTrue($registry->has('myGate'));
    }

    #[Test]
    public function getReturnsRegisteredGate(): void
    {
        $gate = self::createStub(ModuleAccessGateInterface::class);
        $registry = new ModuleAccessGateRegistry(['myGate' => $gate]);
        self::assertSame($gate, $registry->get('myGate'));
    }

    #[Test]
    public function getThrowsExceptionForUnregisteredGate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1774436666);
        $registry = new ModuleAccessGateRegistry();
        $registry->get('nonexistent');
    }

    #[Test]
    public function getAllReturnsAllGates(): void
    {
        $gate1 = self::createStub(ModuleAccessGateInterface::class);
        $gate2 = self::createStub(ModuleAccessGateInterface::class);
        $registry = new ModuleAccessGateRegistry(['gate1' => $gate1, 'gate2' => $gate2]);
        self::assertSame(['gate1' => $gate1, 'gate2' => $gate2], $registry->getAll());
    }
}
