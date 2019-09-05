<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Console;

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

use org\bovigo\vfs\vfsStream;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for CommandRegistry
 */
class CommandRegistryTest extends UnitTestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $rootDirectory;

    /**
     * @var PackageManager|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $packageManagerProphecy;

    /**
     * @var ContainerInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $containerProphecy;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var PackageManager */
        $this->packageManagerProphecy = $this->prophesize(PackageManager::class);
        $this->packageManagerProphecy->getActivePackages()->willReturn([]);

        /** @var ContainerInterface */
        $this->containerProphecy = $this->prophesize(ContainerInterface::class);
    }

    /**
     * @test
     */
    public function implementsCommandLoaderInterface()
    {
        $commandRegistry = new CommandRegistry($this->packageManagerProphecy->reveal(), $this->containerProphecy->reveal());
        self::assertInstanceof(CommandLoaderInterface::class, $commandRegistry);
    }

    /**
     * @test
     */
    public function iteratesLazyCommandsOfActivePackages()
    {
        $command1MockClass = $this->getMockClass(Command::class, ['dummy']);
        $command2MockClass = $this->getMockClass(Command::class, ['dummy']);

        $this->containerProphecy->get('command1')->willReturn(new $command1MockClass);
        $this->containerProphecy->get('command2')->willReturn(new $command2MockClass);

        $commandRegistry = new CommandRegistry($this->packageManagerProphecy->reveal(), $this->containerProphecy->reveal());
        $commandRegistry->addLazyCommand('test:command', 'command1');
        $commandRegistry->addLazyCommand('test:command2', 'command2');

        $commandNames = $commandRegistry->getNames();

        self::assertCount(2, $commandNames);
        self::assertInstanceOf($command1MockClass, $commandRegistry->get('test:command'));
        self::assertInstanceOf($command1MockClass, $commandRegistry->get('test:command2'));
    }

    /**
     * @test
     */
    public function iteratesLegacyCommandsOfActivePackages()
    {
        $commandRegistry = new CommandRegistry($this->packageManagerProphecy->reveal(), $this->containerProphecy->reveal());
        $commands = iterator_to_array($commandRegistry->getLegacyCommands());

        self::assertCount(0, $commands);
        self::assertContainsOnlyInstancesOf(Command::class, $commands);
    }

    /**
     * @test
     */
    public function lazyCommandOverridesLegacyCommandsWithoutDeprecationError()
    {
        $commandMockClass = $this->getMockClass(Command::class, ['dummy']);
        $this->rootDirectory = vfsStream::setup('root', null, [
            'package1' => [
                'Configuration' => [
                    'Commands.php' => '<?php return ["first:command" => [ "class" => "' . $commandMockClass . '" ]];',
                ],
            ],
        ]);
        /** @var PackageInterface */
        $package = $this->prophesize(PackageInterface::class);
        $package->getPackagePath()->willReturn($this->rootDirectory->getChild('package1')->url() . '/');

        $this->packageManagerProphecy->getActivePackages()->willReturn([$package->reveal()]);

        $this->containerProphecy->get($commandMockClass)->willReturn(new $commandMockClass);

        $commandRegistry = new CommandRegistry($this->packageManagerProphecy->reveal(), $this->containerProphecy->reveal());
        $commandRegistry->addLazyCommand('first:command', $commandMockClass);

        $nonLazyCommands = iterator_to_array($commandRegistry->getLegacyCommands());
        $lazyCommands = $commandRegistry->getNames();

        self::assertCount(0, $nonLazyCommands);
        self::assertCount(1, $lazyCommands);
    }
}
