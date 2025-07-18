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
use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\PasswordPolicy\PasswordService;
use TYPO3\CMS\Install\Command\PasswordSetCommand;
use TYPO3\CMS\Install\ServiceProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PasswordSetCommandTest extends FunctionalTestCase
{
    private string $originalSettings = '';
    protected array $coreExtensionsToLoad = ['install'];

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
        $tester->setInputs(['My-password-123']);
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
        $inputPassword = 'My-password-123';

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

        // it shall not auto generate password and fail with normal validation errors
        self::assertSame(Command::FAILURE, $result);
        self::assertStringNotContainsString('Generated password: ', $output);
        self::assertStringNotContainsString('Password hashed (dry run): ', $output);
        self::assertStringContainsString('Your password could not be used', $output);
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
        self::assertSame($oldSettings['BE']['installToolPassword'] ?? '', $newSettings['BE']['installToolPassword'] ?? '', 'Passwords were not persisted.');

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

    #[Test]
    public function weakPasswordFails(): void
    {
        $container = $this->createInstallToolContainer();

        $command = $container->get(PasswordSetCommand::class);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->setInputs(['my-password-has-no-upper-case-letter']);
        $result = $tester->execute(['--dry-run' => true]);
        $output = $tester->getDisplay();

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('The password must at least contain one upper case char', $output);
    }

    #[Test]
    public function canDetectAdditionalSettingsMismatch(): void
    {
        $old = $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] ?? null;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] = 'simulated-password-as-additional.php-would-set-it';

        $container = $this->createInstallToolContainer();

        $command = $container->get(PasswordSetCommand::class);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->setInputs(['A-Strong-4711-Password-Very-Long-And-$hiny']);
        $result = $tester->execute([]);
        $output = $tester->getDisplay();

        if ($old !== null) {
            $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] = $old;
        }

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('Your Install Tool password is different', $output);
    }

    #[Test]
    public function autogeneratedPasswordRespectsConfiguredLength(): void
    {
        $passwordLength = 42;
        $container = $this->createInstallToolContainer();

        $command = $container->get(PasswordSetCommand::class);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $result = $tester->execute(['--no-interaction' => true, '--password-length' => $passwordLength]);
        $output = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('Password length: ' . $passwordLength . ' characters', $output);
        self::assertStringContainsString('Generated password: ', $output);

        preg_match('/Generated password: (\S+)/', $output, $matches);
        $passwordPlain = $matches[1] ?? null;

        self::assertSame(strlen($passwordPlain), $passwordLength, 'Password length mismatch.');
    }

    #[Test]
    public function autogeneratedPasswordRespectsConfiguredLengthAndFailsOnValidationForMinimumLength(): void
    {
        $this->expectException(InvalidPasswordRulesException::class);
        $passwordLength = 2;
        $container = $this->createInstallToolContainer();

        $command = $container->get(PasswordSetCommand::class);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $result = $tester->execute(['--no-interaction' => true, '--password-length' => $passwordLength]);
    }

    private function createInstallToolContainer(): FailsafeContainer
    {
        return new FailsafeContainer(
            [new ServiceProvider()],
            [
                PasswordHashFactory::class => $this->getContainer()->get(PasswordHashFactory::class),
                ConfigurationManager::class => $this->getContainer()->get(ConfigurationManager::class),
                LanguageServiceFactory::class => $this->getContainer()->get(LanguageServiceFactory::class),
                PasswordService::class => $this->getContainer()->get(PasswordService::class),
            ]
        );
    }

}
