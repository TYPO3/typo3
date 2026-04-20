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

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Upgrades\UserSettingsScrubbingMigration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UserSettingsScrubbingMigrationTest extends FunctionalTestCase
{
    #[Test]
    public function updateNecessaryReturnsTrueWhenDoubleJsonEncodedUserSettingsWithInvalidFieldsExists(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        // Create a user with uc but without user_settings
        $connection->insert(
            'be_users',
            [
                'username' => 'testuser',
                'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50, 'password' => 'some-plain-password']),
                // The array would normally be passed directly to prevent double JSON encoding;
                // however, in this case the desired test context requires it to be intentionally
                // JSON-encoded here.
                // See: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293
                'user_settings' => json_encode(['colorScheme' => 'dark', 'titleLen' => 50, 'password' => 'some-plain-password']),
            ],
            [
                'uc' => Connection::PARAM_LOB,
                // @todo This behavior cannot be modified yet; the array value must be passed directly
                //       until https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293 is merged,
                //       otherwise the value will be JSON-encoded twice.
                'user_settings' => Type::getType(Types::JSON),
            ],
        );

        $subject = $this->get(UserSettingsScrubbingMigration::class);
        self::assertTrue($subject->updateNecessary());
    }

    #[Test]
    public function updateNecessaryReturnsTrueWhenSingleJsonEncodedUserSettingsWithInvalidFieldsExists(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        // Create a user with uc but without user_settings
        $connection->insert(
            'be_users',
            [
                'username' => 'testuser',
                'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50, 'password' => 'some-plain-password']),
                // The array must be passed directly to prevent double JSON encoding.
                // See: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293
                'user_settings' => ['colorScheme' => 'dark', 'titleLen' => 50, 'password' => 'some-plain-password'],
            ],
            [
                'uc' => Connection::PARAM_LOB,
                // @todo This behavior cannot be modified yet; the array value must be passed directly
                //       until https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293 is merged,
                //       otherwise the value will be JSON-encoded twice.
                'user_settings' => Type::getType(Types::JSON),
            ],
        );

        $subject = $this->get(UserSettingsScrubbingMigration::class);
        self::assertTrue($subject->updateNecessary());
    }

    #[Test]
    public function updateNecessaryReturnsFalseWhenDoubleJsonEncodedUserSettingsWithoutInvalidFieldsExists(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        // Create a user with uc but without user_settings
        $connection->insert(
            'be_users',
            [
                'username' => 'testuser',
                'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50]),
                // The array would normally be passed directly to prevent double JSON encoding;
                // however, in this case the desired test context requires it to be intentionally
                // JSON-encoded here.
                // See: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293
                'user_settings' => json_encode(['colorScheme' => 'dark', 'titleLen' => 50]),
            ],
            [
                'uc' => Connection::PARAM_LOB,
                // @todo This behavior cannot be modified yet; the array value must be passed directly
                //       until https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293 is merged,
                //       otherwise the value will be JSON-encoded twice.
                'user_settings' => Type::getType(Types::JSON),
            ],
        );

        $subject = $this->get(UserSettingsScrubbingMigration::class);
        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function updateNecessaryReturnsFalseWhenSingleJsonEncodedUserSettingsWithoutInvalidFieldsExists(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        // Create a user with uc but without user_settings
        $connection->insert(
            'be_users',
            [
                'username' => 'testuser',
                'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50]),
                // The array must be passed directly to prevent double JSON encoding.
                // See: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293
                'user_settings' => ['colorScheme' => 'dark', 'titleLen' => 50],
            ],
            [
                'uc' => Connection::PARAM_LOB,
                // @todo This behavior cannot be modified yet; the array value must be passed directly
                //       until https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293 is merged,
                //       otherwise the value will be JSON-encoded twice.
                'user_settings' => Type::getType(Types::JSON),
            ],
        );

        $subject = $this->get(UserSettingsScrubbingMigration::class);
        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function executeUpdateMigratesDoubleJsonEncodedUserSettingsRemovingInvalidValues(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        // Create a user with uc but without user_settings
        $connection->insert(
            'be_users',
            [
                'username' => 'testuser',
                'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50]),
                // The array would normally be passed directly to prevent double JSON encoding;
                // however, in this case the desired test context requires it to be intentionally
                // JSON-encoded here.
                // See: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293
                'user_settings' => json_encode(['colorScheme' => 'dark', 'titleLen' => 50, 'password' => 'some-plain-password']),
            ],
            [
                'uc' => Connection::PARAM_LOB,
                // @todo This behavior cannot be modified yet; the array value must be passed directly
                //       until https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293 is merged,
                //       otherwise the value will be JSON-encoded twice.
                'user_settings' => Type::getType(Types::JSON),
            ],
        );
        $userId = (int)$connection->lastInsertId();

        // Check user settings is double json_encoded
        $row = $connection->select(['user_settings'], 'be_users', ['uid' => $userId])->fetchAssociative();
        self::assertNotEmpty($row['user_settings'], 'user_settings should not be empty');
        $rawValue = $row['user_settings'];
        self::assertTrue(str_starts_with($rawValue, '"{'));
        self::assertTrue(str_ends_with($rawValue, '}"'));
        $decoded = json_decode(json: $rawValue, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
        self::assertIsString($decoded);
        $decoded = json_decode(json: $decoded, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('password', $decoded);

        $subject = $this->get(UserSettingsScrubbingMigration::class);
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());

        // Check user settings is now single json_encoded
        $row = $connection->select(['user_settings'], 'be_users', ['uid' => $userId])->fetchAssociative();
        self::assertNotEmpty($row['user_settings'], 'user_settings should not be empty');
        $rawValue = $row['user_settings'];
        self::assertFalse(str_starts_with($rawValue, '"{'));
        self::assertFalse(str_ends_with($rawValue, '}"'));
        $decoded = json_decode(json: $rawValue, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
        self::assertIsArray($decoded);
        self::assertArrayNotHasKey('password', $decoded);

        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function executeUpdateMigratesSingleJsonEncodedUserSettingsRemovingInvalidValues(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('be_users');

        // Create a user with uc but without user_settings
        $connection->insert(
            'be_users',
            [
                'username' => 'testuser',
                'uc' => serialize(['colorScheme' => 'dark', 'titleLen' => 50]),
                // The array must be passed directly to prevent double JSON encoding.
                // See: https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293
                'user_settings' => ['colorScheme' => 'dark', 'titleLen' => 50, 'password' => 'some-plain-password'],
            ],
            [
                'uc' => Connection::PARAM_LOB,
                // @todo This behavior cannot be modified yet; the array value must be passed directly
                //       until https://review.typo3.org/c/Packages/TYPO3.CMS/+/89293 is merged,
                //       otherwise the value will be JSON-encoded twice.
                'user_settings' => Type::getType(Types::JSON),
            ],
        );
        $userId = (int)$connection->lastInsertId();

        // Check user settings is double json_encoded
        $row = $connection->select(['user_settings'], 'be_users', ['uid' => $userId])->fetchAssociative();
        self::assertNotEmpty($row['user_settings'], 'user_settings should not be empty');
        $rawValue = $row['user_settings'];
        self::assertTrue(str_starts_with($rawValue, '{'));
        self::assertTrue(str_ends_with($rawValue, '}'));
        $decoded = json_decode(json: $rawValue, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('password', $decoded);

        $subject = $this->get(UserSettingsScrubbingMigration::class);
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());

        // Check user settings is now single json_encoded
        $row = $connection->select(['user_settings'], 'be_users', ['uid' => $userId])->fetchAssociative();
        self::assertNotEmpty($row['user_settings'], 'user_settings should not be empty');
        $rawValue = $row['user_settings'];
        self::assertFalse(str_starts_with($rawValue, '"{'));
        self::assertFalse(str_ends_with($rawValue, '}"'));
        $decoded = json_decode(json: $rawValue, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
        self::assertIsArray($decoded);
        self::assertArrayNotHasKey('password', $decoded);
    }
}
