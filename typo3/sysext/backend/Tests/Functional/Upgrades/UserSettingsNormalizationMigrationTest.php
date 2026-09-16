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

namespace TYPO3\CMS\Backend\Tests\Functional\Upgrades;

use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Upgrades\UserSettingsNormalizationMigration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UserSettingsNormalizationMigrationTest extends FunctionalTestCase
{
    #[Test]
    public function updateNecessaryReturnsTrueWhenDoubleJsonEncodedUserSettingsExists(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        $connection->insert(
            'be_users',
            [
                'username' => 'testuser',
                'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50]),
                // Encode before JSON conversion to create a double-encoded value.
                'user_settings' => json_encode(['colorScheme' => 'dark', 'titleLen' => 50]),
            ],
            [
                'uc' => Connection::PARAM_LOB,
                'user_settings' => Types::JSON,
            ],
        );

        $subject = $this->get(UserSettingsNormalizationMigration::class);
        self::assertTrue($subject->updateNecessary());
    }

    #[Test]
    public function updateNecessaryReturnsFalseWhenUserSettingsAreSingleJsonEncoded(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        $connection->insert(
            'be_users',
            [
                'username' => 'testuser',
                'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50]),
                'user_settings' => ['colorScheme' => 'dark', 'titleLen' => 50],
            ],
            [
                'uc' => Connection::PARAM_LOB,
                'user_settings' => Types::JSON,
            ],
        );

        $subject = $this->get(UserSettingsNormalizationMigration::class);
        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function executeUpdateMigratesDoubleJsonEncodedUserSettingsToSingleEncodedSettings(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        $connection->insert(
            'be_users',
            [
                'username' => 'testuser',
                'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50]),
                // Encode before JSON conversion to create a double-encoded value.
                'user_settings' => json_encode(['colorScheme' => 'dark', 'titleLen' => 50]),
            ],
            [
                'uc' => Connection::PARAM_LOB,
                'user_settings' => Types::JSON,
            ],
        );
        $userId = (int)$connection->lastInsertId();

        // Check user settings is double json_encoded
        $row = $connection->select(['user_settings'], 'be_users', ['uid' => $userId])->fetchAssociative();
        self::assertNotEmpty($row['user_settings'], 'user_settings should not be empty');
        $rawValue = $row['user_settings'];
        self::assertTrue(str_starts_with($rawValue, '"{'));
        self::assertTrue(str_ends_with($rawValue, '}"'));
        self::assertIsString(json_decode(json: $rawValue, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY));

        $subject = $this->get(UserSettingsNormalizationMigration::class);
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());

        // Check user settings is now single json_encoded
        $row = $connection->select(['user_settings'], 'be_users', ['uid' => $userId])->fetchAssociative();
        self::assertNotEmpty($row['user_settings'], 'user_settings should not be empty');
        $rawValue = $row['user_settings'];
        self::assertFalse(str_starts_with($rawValue, '"{'));
        self::assertFalse(str_ends_with($rawValue, '}"'));
        self::assertIsArray(json_decode(json: $rawValue, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY));

        self::assertFalse($subject->updateNecessary());
    }
}
