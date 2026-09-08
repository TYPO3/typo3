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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Lowlevel\Command\ConfigurationShowCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConfigurationShowCommandTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'lowlevel',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'sitename' => 'Test Site',
        ],
        'BE' => [
            'debug' => false,
        ],
        'EXTENSIONS' => [
            'testextension' => [
                'setting1' => 'value1',
                'setting2' => 42,
            ],
        ],
    ];

    #[Test]
    public function showCommandDisplaysActiveConfiguration(): void
    {
        $tester = new CommandTester($this->get(ConfigurationShowCommand::class));
        $tester->execute(['path' => 'SYS/sitename', '--type' => 'active']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Test Site', $tester->getDisplay());
    }

    #[Test]
    public function showCommandDisplaysLocalConfiguration(): void
    {
        $tester = new CommandTester($this->get(ConfigurationShowCommand::class));
        $tester->execute(['path' => 'SYS/sitename', '--type' => 'local']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Test Site', $tester->getDisplay());
    }

    #[Test]
    public function showCommandDisplaysConfigurationAsJson(): void
    {
        $tester = new CommandTester($this->get(ConfigurationShowCommand::class));
        $tester->execute(['path' => 'BE/debug', '--type' => 'active', '--json' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('false', $tester->getDisplay());
    }

    #[Test]
    public function showCommandDisplaysArrayAsJson(): void
    {
        $tester = new CommandTester($this->get(ConfigurationShowCommand::class));
        $tester->execute(['path' => 'EXTENSIONS/testextension', '--type' => 'active', '--json' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $output = $tester->getDisplay();
        self::assertStringContainsString('"setting1"', $output);
        self::assertStringContainsString('"value1"', $output);
        self::assertStringContainsString('"setting2"', $output);
        self::assertStringContainsString('42', $output);
    }

    #[Test]
    public function showCommandWithoutTypeFallsBackToDiffMode(): void
    {
        $tester = new CommandTester($this->get(ConfigurationShowCommand::class));
        $tester->execute(['path' => 'SYS/sitename']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        // When local equals active, just the value is shown
        self::assertStringContainsString('Test Site', $tester->getDisplay());
    }

    #[Test]
    public function showCommandFailsForInvalidPath(): void
    {
        $tester = new CommandTester($this->get(ConfigurationShowCommand::class));
        $tester->execute(['path' => 'INVALID/nonexistent/path', '--type' => 'active']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No configuration found', $tester->getDisplay());
    }

    #[Test]
    public function showCommandFailsForInvalidType(): void
    {
        $tester = new CommandTester($this->get(ConfigurationShowCommand::class));
        $tester->execute(['path' => 'SYS/sitename', '--type' => 'invalid']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Invalid type', $tester->getDisplay());
    }

    #[Test]
    public function showCommandDisplaysNestedConfiguration(): void
    {
        $tester = new CommandTester($this->get(ConfigurationShowCommand::class));
        $tester->execute(['path' => 'DB/Connections/Default', '--type' => 'active']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        // DB/Connections/Default should exist in any TYPO3 installation
        $output = $tester->getDisplay();
        self::assertStringContainsString('driver', $output);
    }
}
