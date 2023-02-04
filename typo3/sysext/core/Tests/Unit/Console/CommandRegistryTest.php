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

namespace TYPO3\CMS\Core\Tests\Unit\Console;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CommandRegistryTest extends UnitTestCase
{
    protected ContainerInterface&MockObject $containerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->containerMock = $this->createMock(ContainerInterface::class);
    }

    /**
     * @test
     */
    public function implementsCommandLoaderInterface(): void
    {
        $commandRegistry = new CommandRegistry($this->containerMock);
        self::assertInstanceof(CommandLoaderInterface::class, $commandRegistry);
    }

    /**
     * @test
     */
    public function iteratesLazyCommandsOfActivePackages(): void
    {
        $command1Mock = $this->createMock(Command::class);
        $command2Mock = $this->createMock(Command::class);

        $this->containerMock->method('get')->willReturnMap([
            ['command1', $command1Mock],
            ['command2', $command2Mock],
        ]);

        $commandRegistry = new CommandRegistry($this->containerMock);
        $commandRegistry->addLazyCommand('test:command', 'command1');
        $commandRegistry->addLazyCommand('test:command2', 'command2');

        $commandNames = $commandRegistry->getNames();

        self::assertCount(2, $commandNames);
        self::assertInstanceOf(get_class($command1Mock), $commandRegistry->get('test:command'));
        self::assertInstanceOf(get_class($command2Mock), $commandRegistry->get('test:command2'));
    }
}
