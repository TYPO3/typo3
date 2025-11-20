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

namespace TYPO3\CMS\Backend\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\Command\AbstractCommandTestCase;

final class CreateBackendUserCommandTest extends AbstractCommandTestCase
{
    /**
     * @var array{
     *     username: string,
     *     password: string,
     *     email: string,
     *     groups: string,
     * }
     */
    private array $userDefaults = [
        'username' => 'picard',
        'password' => 'Engage1701D!',
        'email' => 'starcommand@example.com',
        'groups' => '4,8,16',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_groups_multiple.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_create_backend_command.csv');
    }

    #[Test]
    public function userCanBeCreatedWithShortcuts(): void
    {
        $result = $this->executeConsoleCommand(
            'backend:user:create -u %s -p %s -e %s -g %s --no-interaction',
            $this->userDefaults['username'],
            $this->userDefaults['password'],
            $this->userDefaults['email'],
            $this->userDefaults['groups'],
        );

        self::assertEquals(0, $result['status']);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Expected/be_users_after_normal.csv');
    }

    #[Test]
    public function userCanBeCreatedWithLongOptions(): void
    {
        $result = $this->executeConsoleCommand(
            'backend:user:create --username %s --password %s --email %s --groups %s --no-interaction',
            $this->userDefaults['username'],
            $this->userDefaults['password'],
            $this->userDefaults['email'],
            $this->userDefaults['groups'],
        );

        self::assertEquals(0, $result['status']);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Expected/be_users_after_normal.csv');
    }

    #[Test]
    public function adminUserCanBeCreatedWithShortcuts(): void
    {
        $result = $this->executeConsoleCommand(
            'backend:user:create -u %s -p %s -e %s -g %s -a --no-interaction',
            $this->userDefaults['username'],
            $this->userDefaults['password'],
            $this->userDefaults['email'],
            $this->userDefaults['groups'],
        );

        self::assertEquals(0, $result['status']);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Expected/be_users_after_admin.csv');
    }

    #[Test]
    public function adminUserCanBeCreatedWithLongOptions(): void
    {
        $result = $this->executeConsoleCommand(
            'backend:user:create --username %s --password %s --email %s --groups %s --admin --no-interaction',
            $this->userDefaults['username'],
            $this->userDefaults['password'],
            $this->userDefaults['email'],
            $this->userDefaults['groups'],
        );

        self::assertEquals(0, $result['status']);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Expected/be_users_after_admin.csv');
    }

    #[Test]
    public function maintainerUserCanBeCreatedWithShortcuts(): void
    {
        $result = $this->executeConsoleCommand(
            'backend:user:create -u %s -p %s -e %s -g %s -m --no-interaction',
            $this->userDefaults['username'],
            $this->userDefaults['password'],
            $this->userDefaults['email'],
            $this->userDefaults['groups'],
        );

        self::assertEquals(0, $result['status']);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Expected/be_users_after_maint.csv');
    }

    #[Test]
    public function maintainerUserCanBeCreatedWithLongOptions(): void
    {
        $result = $this->executeConsoleCommand(
            'backend:user:create --username %s --password %s --email %s --groups %s --maintainer --no-interaction',
            $this->userDefaults['username'],
            $this->userDefaults['password'],
            $this->userDefaults['email'],
            $this->userDefaults['groups'],
        );

        self::assertEquals(0, $result['status']);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Expected/be_users_after_maint.csv');
    }

    #[Test]
    public function emptyUsernameFails(): void
    {
        $result = $this->executeConsoleCommand(
            'backend:user:create --username "" --password %s --no-interaction',
            $this->userDefaults['password'],
        );

        self::assertEquals(255, $result['status']);
    }

    public static function existingUsernameFailsDataProvider(): \Generator
    {
        yield 'existing admin' => ['username' => 'admin'];
        yield 'disabled admin' => ['username' => 'admin_disabled'];
        yield 'deleted editor' => ['username' => 'editor_deleted'];
    }

    #[Test]
    #[DataProvider('existingUsernameFailsDataProvider')]
    public function existingUsernameFails(string $username): void
    {
        $result = $this->executeConsoleCommand(
            'backend:user:create --username %s --password %s --no-interaction',
            $username,
            $this->userDefaults['password'],
        );

        self::assertEquals(255, $result['status']);
    }

    #[Test]
    public function weakPasswordFails(): void
    {
        // Insert first time
        $result = $this->executeConsoleCommand(
            'backend:user:create --username %s --password "yes" --no-interaction',
            $this->userDefaults['username'],
        );

        self::assertEquals(255, $result['status']);
    }

    #[Test]
    public function emptyPasswordFails(): void
    {
        // Insert first time
        $result = $this->executeConsoleCommand(
            'backend:user:create --username %s --password "" --no-interaction',
            $this->userDefaults['username'],
        );

        self::assertEquals(255, $result['status']);
    }

    #[Test]
    public function invalidEmailFails(): void
    {
        // Insert first time
        $result = $this->executeConsoleCommand(
            'backend:user:create --username %s --password %s --email "nobody" --no-interaction',
            $this->userDefaults['username'],
            $this->userDefaults['password'],
        );

        self::assertEquals(255, $result['status']);
    }

    #[Test]
    public function invalidGroupIdFails(): void
    {
        // Insert first time
        $result = $this->executeConsoleCommand(
            'backend:user:create --username %s --password %s --groups "4711" --no-interaction',
            $this->userDefaults['username'],
            $this->userDefaults['password'],
        );

        self::assertEquals(255, $result['status']);
    }
}
