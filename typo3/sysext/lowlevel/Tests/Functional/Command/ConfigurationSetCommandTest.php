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
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Lowlevel\Command\ConfigurationSetCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConfigurationSetCommandTest extends FunctionalTestCase
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
    public function setCommandSetsStringValue(): void
    {
        $tester = new CommandTester($this->get(ConfigurationSetCommand::class));
        $tester->execute(['path' => 'SYS/sitename', 'value' => 'New Site Name']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Successfully set', $tester->getDisplay());

        // Verify the value was set
        $configurationManager = $this->get(ConfigurationManager::class);
        self::assertSame('New Site Name', $configurationManager->getLocalConfigurationValueByPath('SYS/sitename'));
    }

    #[Test]
    public function setCommandSetsBooleanValueWithJson(): void
    {
        $tester = new CommandTester($this->get(ConfigurationSetCommand::class));
        $tester->execute(['path' => 'BE/debug', 'value' => 'true', '--json' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $configurationManager = $this->get(ConfigurationManager::class);
        self::assertTrue($configurationManager->getLocalConfigurationValueByPath('BE/debug'));
    }

    #[Test]
    public function setCommandSetsIntegerValueWithJson(): void
    {
        $tester = new CommandTester($this->get(ConfigurationSetCommand::class));
        $tester->execute(['path' => 'SYS/displayErrors', 'value' => '1', '--json' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $configurationManager = $this->get(ConfigurationManager::class);
        self::assertSame(1, $configurationManager->getLocalConfigurationValueByPath('SYS/displayErrors'));
    }

    #[Test]
    public function setCommandSetsArrayValueWithJson(): void
    {
        $tester = new CommandTester($this->get(ConfigurationSetCommand::class));
        $arrayValue = '{"key1": "value1", "key2": 123}';
        $tester->execute(['path' => 'EXTENSIONS/testextension', 'value' => $arrayValue, '--json' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $configurationManager = $this->get(ConfigurationManager::class);
        $value = $configurationManager->getLocalConfigurationValueByPath('EXTENSIONS/testextension');
        self::assertSame('value1', $value['key1']);
        self::assertSame(123, $value['key2']);
    }

    #[Test]
    public function setCommandFailsForInvalidJson(): void
    {
        $tester = new CommandTester($this->get(ConfigurationSetCommand::class));
        $tester->execute(['path' => 'SYS/sitename', 'value' => '{invalid json}', '--json' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Invalid JSON', $tester->getDisplay());
    }

    #[Test]
    public function setCommandFailsForInvalidPath(): void
    {
        $tester = new CommandTester($this->get(ConfigurationSetCommand::class));
        // Use a path that is not in the allowed paths and not in DefaultConfiguration
        $tester->execute(['path' => 'INVALID/completely/new/path', 'value' => 'test']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Could not set', $tester->getDisplay());
    }

    #[Test]
    public function setCommandCanSetNewExtensionConfiguration(): void
    {
        $tester = new CommandTester($this->get(ConfigurationSetCommand::class));
        $tester->execute(['path' => 'EXTENSIONS/newextension/setting', 'value' => 'newvalue']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $configurationManager = $this->get(ConfigurationManager::class);
        self::assertSame('newvalue', $configurationManager->getLocalConfigurationValueByPath('EXTENSIONS/newextension/setting'));
    }
}
