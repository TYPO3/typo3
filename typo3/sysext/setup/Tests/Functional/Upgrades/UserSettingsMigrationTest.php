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

namespace TYPO3\CMS\Setup\Tests\Functional\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Setup\Upgrades\UserSettingsMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UserSettingsMigrationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['setup'];

    #[Test]
    public function updateNecessaryReturnsTrueWhenUsersHaveUcButNoUserSettings(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        // Create a user with uc but without user_settings
        $connection->insert('be_users', [
            'username' => 'testuser',
            'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50]),
            'user_settings' => null,
        ]);

        $subject = new UserSettingsMigration();
        self::assertTrue($subject->updateNecessary());
    }

    #[Test]
    public function updateNecessaryReturnsFalseWhenNoUserNeedsMigration(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        // Create a user that already has user_settings populated
        $connection->insert('be_users', [
            'username' => 'testuser',
            'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50]),
            'user_settings' => json_encode(['colorScheme' => 'dark', 'titleLen' => 50]),
        ]);

        $subject = new UserSettingsMigration();
        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function executeUpdateMigratesProfileSettingsToJson(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        // Create user with profile settings in uc
        $ucData = [
            'colorScheme' => 'dark',
            'titleLen' => 80,
            'emailMeAtLogin' => 1,
            'moduleData' => ['web_list' => ['expandedTree' => [1, 2, 3]]],
            'moduleSessionID' => ['web_list' => 'abc123'],
        ];

        $connection->insert('be_users', [
            'username' => 'testuser',
            'uc' => serialize($ucData),
            'user_settings' => null,
        ]);

        $userId = (int)$connection->lastInsertId();

        $subject = new UserSettingsMigration();
        self::assertTrue($subject->updateNecessary());

        $result = $subject->executeUpdate();
        self::assertTrue($result);

        // Check that user_settings now contains the profile settings (not moduleData)
        $row = $connection->select(['user_settings'], 'be_users', ['uid' => $userId])->fetchAssociative();
        self::assertNotEmpty($row['user_settings'], 'user_settings should not be empty');

        // Parse user_settings JSON
        $rawValue = $row['user_settings'];
        $userSettings = json_decode($rawValue, true);

        // In some DBMS the JSON might be double-encoded or returned differently
        if (is_string($userSettings)) {
            $userSettings = json_decode($userSettings, true);
        }

        self::assertIsArray($userSettings, 'user_settings should be a valid JSON array');

        self::assertArrayHasKey('colorScheme', $userSettings);
        self::assertSame('dark', $userSettings['colorScheme']);
        self::assertArrayHasKey('titleLen', $userSettings);
        self::assertSame(80, $userSettings['titleLen']);
        self::assertArrayHasKey('emailMeAtLogin', $userSettings);
        self::assertSame(1, $userSettings['emailMeAtLogin']);

        // moduleData and moduleSessionID should NOT be in user_settings
        self::assertArrayNotHasKey('moduleData', $userSettings);
        self::assertArrayNotHasKey('moduleSessionID', $userSettings);

        // After migration, updateNecessary should return false
        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function executeUpdateHandlesEmptyUcGracefully(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        // Create user with empty uc
        $connection->insert('be_users', [
            'username' => 'testuser',
            'uc' => serialize([]),
            'user_settings' => null,
        ]);

        $subject = new UserSettingsMigration();

        // Empty uc should still be considered for migration
        self::assertTrue($subject->updateNecessary());

        $result = $subject->executeUpdate();
        self::assertTrue($result);
    }
}
