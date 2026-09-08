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
use TYPO3\CMS\Lowlevel\Command\ConfigurationRemoveCommand;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConfigurationRemoveCommandTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = [
        'lowlevel',
    ];

    #[Test]
    public function removeCommandRemovesConfiguration(): void
    {
        // First, ensure the value exists in local configuration by setting it
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS/toremove/setting1', 'willberemoved');

        $tester = new CommandTester($this->get(ConfigurationRemoveCommand::class));
        $tester->execute(['paths' => 'EXTENSIONS/toremove/setting1', '--force' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Removed', $tester->getDisplay());

        // Verify the value was removed
        $localConfig = $configurationManager->getLocalConfiguration();
        self::assertArrayNotHasKey('setting1', $localConfig['EXTENSIONS']['toremove'] ?? []);
    }

    #[Test]
    public function removeCommandRemovesMultiplePaths(): void
    {
        // First, ensure the values exist in local configuration
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValuesByPathValuePairs([
            'EXTENSIONS/multiremove/setting1' => 'value1',
            'EXTENSIONS/multiremove/setting2' => 'value2',
        ]);

        $tester = new CommandTester($this->get(ConfigurationRemoveCommand::class));
        $tester->execute(['paths' => 'EXTENSIONS/multiremove/setting1,EXTENSIONS/multiremove/setting2', '--force' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $output = $tester->getDisplay();
        self::assertStringContainsString('setting1', $output);
        self::assertStringContainsString('setting2', $output);

        // Verify the values were removed
        $localConfig = $configurationManager->getLocalConfiguration();
        self::assertArrayNotHasKey('setting1', $localConfig['EXTENSIONS']['multiremove'] ?? []);
        self::assertArrayNotHasKey('setting2', $localConfig['EXTENSIONS']['multiremove'] ?? []);
    }

    #[Test]
    public function removeCommandSkipsNonexistentPath(): void
    {
        $tester = new CommandTester($this->get(ConfigurationRemoveCommand::class));
        $tester->execute(['paths' => 'EXTENSIONS/nonexistent/setting', '--force' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('does not exist', $tester->getDisplay());
    }

    #[Test]
    public function removeCommandRequiresConfirmationWithoutForce(): void
    {
        // First, ensure the value exists
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS/confirmtest/setting1', 'testvalue');

        $tester = new CommandTester($this->get(ConfigurationRemoveCommand::class));
        // Simulate answering 'no' to the confirmation
        $tester->setInputs(['no']);
        $tester->execute(['paths' => 'EXTENSIONS/confirmtest/setting1']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Skipped', $tester->getDisplay());

        // Verify the value was NOT removed
        self::assertSame('testvalue', $configurationManager->getLocalConfigurationValueByPath('EXTENSIONS/confirmtest/setting1'));
    }

    #[Test]
    public function removeCommandRemovesWithConfirmation(): void
    {
        // First, ensure the value exists
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS/confirmremove/setting1', 'willberemoved');

        $tester = new CommandTester($this->get(ConfigurationRemoveCommand::class));
        // Simulate answering 'yes' to the confirmation
        $tester->setInputs(['yes']);
        $tester->execute(['paths' => 'EXTENSIONS/confirmremove/setting1']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Removed', $tester->getDisplay());

        // Verify the value was removed
        $localConfig = $configurationManager->getLocalConfiguration();
        self::assertArrayNotHasKey('setting1', $localConfig['EXTENSIONS']['confirmremove'] ?? []);
    }

    #[Test]
    public function removeCommandCanRemoveEntireSection(): void
    {
        // First, ensure the section exists
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValuesByPathValuePairs([
            'EXTENSIONS/sectiontoremove/setting1' => 'value1',
            'EXTENSIONS/sectiontoremove/setting2' => 'value2',
        ]);

        $tester = new CommandTester($this->get(ConfigurationRemoveCommand::class));
        $tester->execute(['paths' => 'EXTENSIONS/sectiontoremove', '--force' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $localConfig = $configurationManager->getLocalConfiguration();
        self::assertArrayNotHasKey('sectiontoremove', $localConfig['EXTENSIONS'] ?? []);
    }
}
