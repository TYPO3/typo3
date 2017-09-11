<?php
declare(strict_types=1);
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
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Console\CommandNameAlreadyInUseException;
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
     * Set up this testcase
     */
    protected function setUp()
    {
        $commandMockClass = $this->getMockClass(Command::class, ['dummy']);
        $this->rootDirectory = vfsStream::setup('root', null, [
            'package1' => [
                'Configuration' => [
                    'Commands.php' => '<?php return ["first:command" => [ "class" => "' . $commandMockClass . '" ]];',
                ],
            ],
            'package2' => [
                'Configuration' => [
                    'Commands.php' => '<?php return ["second:command" => [ "class" => "' . $commandMockClass . '" ]];',
                ],
            ],
            'package3' => [
                'Configuration' => [
                    'Commands.php' => '<?php return ["third:command" => [ "class" => "' . $commandMockClass . '" ]];',
                ],
            ],
            'package4' => [
                'Configuration' => [
                    'Commands.php' => '<?php return ["third:command" => [ "class" => "' . $commandMockClass . '" ]];',
                ],
            ],
        ]);

        /** @var PackageManager */
        $this->packageManagerProphecy = $this->prophesize(PackageManager::class);
    }

    /**
     * @test
     */
    public function iteratesCommandsOfActivePackages()
    {
        /** @var PackageInterface */
        $package1 = $this->prophesize(PackageInterface::class);
        $package1->getPackagePath()->willReturn($this->rootDirectory->getChild('package1')->url() . '/');
        /** @var PackageInterface */
        $package2 = $this->prophesize(PackageInterface::class);
        $package2->getPackagePath()->willReturn($this->rootDirectory->getChild('package2')->url() . '/');

        $this->packageManagerProphecy->getActivePackages()->willReturn([$package1->reveal(), $package2->reveal()]);

        $commandRegistry = new CommandRegistry($this->packageManagerProphecy->reveal());
        $commands = iterator_to_array($commandRegistry);

        $this->assertCount(2, $commands);
        $this->assertContainsOnlyInstancesOf(Command::class, $commands);
    }

    /**
     * @test
     */
    public function throwsExceptionOnDuplicateCommand()
    {
        /** @var PackageInterface */
        $package3 = $this->prophesize(PackageInterface::class);
        $package3->getPackagePath()->willReturn($this->rootDirectory->getChild('package3')->url() . '/');
        /** @var PackageInterface */
        $package4 = $this->prophesize(PackageInterface::class);
        $package4->getPackagePath()->willReturn($this->rootDirectory->getChild('package4')->url() . '/');
        $package4->getPackageKey()->willReturn('package4');

        $this->packageManagerProphecy->getActivePackages()->willReturn([$package3->reveal(), $package4->reveal()]);

        $this->expectException(CommandNameAlreadyInUseException::class);
        $this->expectExceptionCode(1484486383);

        $commandRegistry = new CommandRegistry($this->packageManagerProphecy->reveal());
        iterator_to_array($commandRegistry);
    }
}
