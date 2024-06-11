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

namespace TYPO3\CMS\Install\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer;
use TYPO3\CMS\Install\Command\PasswordSetCommand;
use TYPO3\CMS\Install\ServiceProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PasswordSetCommandTest extends FunctionalTestCase
{
    protected string $originalSettings = '';
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSettings = file_get_contents(self::getInstancePath() . '/typo3conf/system/settings.php');
    }

    protected function tearDown(): void
    {
        file_put_contents(self::getInstancePath() . '/typo3conf/system/settings.php', $this->originalSettings);
        parent::tearDown();
    }

    #[Test]
    public function canRunDry(): void
    {
        $container = $this->createInstallToolContainer();

        $command = $container->get(PasswordSetCommand::class);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->setInputs(['my-password']);
        $result = $tester->execute(['--dry-run' => true]);
        $output = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringNotContainsString('my-password', $output);
        self::assertStringNotContainsString('Generated password: ', $output);
        self::assertStringContainsString('Password hashed (dry run): ', $output);
        self::assertStringNotContainsString('Install Tool password updated', $output);

        // Password must not have been persisted.
        $settingsPath = self::getInstancePath() . '/typo3conf/system/settings.php';
        clearstatcache(true, $settingsPath);
        $newSettings = include $settingsPath;
        self::assertEmpty($newSettings['BE']['installToolPassword'] ?? '');
    }

    #[Test]
    public function canActuallyUpdateTheSettings(): void
    {
        $settingsPath = self::getInstancePath() . '/typo3conf/system/settings.php';
        $container = $this->createInstallToolContainer();
        $inputPassword = 'my-password';

        $command = $container->get(PasswordSetCommand::class);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->setInputs([$inputPassword]);
        $result = $tester->execute([]);
        $output = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringNotContainsString($inputPassword, $output);
        self::assertStringNotContainsString('Generated password: ', $output);
        self::assertStringNotContainsString('Password hashed (dry run): ', $output);

        $newSettings = include $settingsPath;
        self::assertNotEmpty($newSettings['BE']['installToolPassword']);

        try {
            $valid = $container->get(PasswordHashFactory::class)
                ->get($newSettings['BE']['installToolPassword'], 'BE')
                ->checkPassword($inputPassword, $newSettings['BE']['installToolPassword']);
        } catch (InvalidPasswordHashException) {
            $valid = false;
        }
        self::assertTrue($valid, 'Password hash does not match');
    }

    #[Test]
    public function canAutogeneratePassword(): void
    {
        $container = $this->createInstallToolContainer();

        $command = $container->get(PasswordSetCommand::class);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->setInputs(['']);
        $result = $tester->execute([]);
        $output = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('Generated password: ', $output);
    }

    #[Test]
    public function canGeneratePasswordWithNoInteraction(): void
    {
        $container = $this->createInstallToolContainer();

        $command = $container->get(PasswordSetCommand::class);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $result = $tester->execute(['--no-interaction' => true]);
        $output = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('Generated password: ', $output);
        self::assertStringNotContainsString('Password hashed (dry run): ', $output);
    }

    #[Test]
    public function falsyPasswordsAreStillTreatedAsSet(): void
    {
        $container = $this->createInstallToolContainer();

        $command = $container->get(PasswordSetCommand::class);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->setInputs(['0']);
        $result = $tester->execute([]);
        $output = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringNotContainsString('Generated password: ', $output);
        self::assertStringNotContainsString('Password hashed (dry run): ', $output);
    }

    #[Test]
    public function autogeneratedPasswordWithoutInteractionAndDryRunShowsHashForFurtherProcessing(): void
    {
        $container = $this->createInstallToolContainer();
        $settingsPath = self::getInstancePath() . '/typo3conf/system/settings.php';
        $oldSettings = include $settingsPath;

        $command = $container->get(PasswordSetCommand::class);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $result = $tester->execute(['--no-interaction' => true, '--dry-run' => true]);
        $output = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('Generated password: ', $output);
        self::assertStringContainsString('Password hashed (dry run): ', $output);

        // Password must not have been persisted.
        clearstatcache(true, $settingsPath);
        $newSettings = include $settingsPath;
        self::assertSame($oldSettings['BE']['installToolPassword'] ?? '', $newSettings['BE']['installToolPassword'] ?? '');

        // Ensure auto-generated password is verifiable with the emitted hash
        preg_match('/Password hashed \(dry run\): (\S+)/', $output, $matches);
        $passwordHashed = $matches[1] ?? null;
        preg_match('/Generated password: (\S+)/', $output, $matches);
        $passwordPlain = $matches[1] ?? null;

        try {
            $valid = $container->get(PasswordHashFactory::class)
                ->get($passwordHashed, 'BE')
                ->checkPassword($passwordPlain, $passwordHashed);
        } catch (InvalidPasswordHashException) {
            $valid = false;
        }
        self::assertTrue($valid, 'Password hash does not match');
    }

    private function createInstallToolContainer(): FailsafeContainer
    {
        return new FailsafeContainer(
            [new ServiceProvider()],
            [
                PasswordHashFactory::class => $this->getContainer()->get(PasswordHashFactory::class),
                ConfigurationManager::class => $this->getContainer()->get(ConfigurationManager::class),
            ]
        );
    }

}
