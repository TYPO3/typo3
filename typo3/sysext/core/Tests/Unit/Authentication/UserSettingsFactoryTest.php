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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\UserSettingsFactory;
use TYPO3\CMS\Core\Authentication\UserSettingsSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UserSettingsFactoryTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'colorScheme' => ['type' => 'select'],
                'titleLen' => ['type' => 'number'],
                'emailMeAtLogin' => ['type' => 'check'],
                'lang' => ['type' => 'language', 'table' => 'be_users'],
                'email' => ['type' => 'email', 'table' => 'be_users'],
                'realName' => ['type' => 'text', 'table' => 'be_users'],
                'password' => ['type' => 'password', 'table' => 'be_users'],
                'resetConfiguration' => ['type' => 'button'],
                'mfaProviders' => ['type' => 'mfa'],
            ],
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_USER_SETTINGS']);
        parent::tearDown();
    }

    #[Test]
    public function createFromUserRecordExtractsJsonFieldSettings(): void
    {
        $schema = new UserSettingsSchema();
        $factory = new UserSettingsFactory($schema);

        $userRecord = [
            'user_settings' => json_encode(['colorScheme' => 'dark', 'titleLen' => 80]),
            'lang' => 'de',
            'email' => 'test@example.com',
        ];

        $userSettings = $factory->createFromUserRecord($userRecord);

        self::assertSame('dark', $userSettings->get('colorScheme'));
        self::assertSame(80, $userSettings->get('titleLen'));
    }

    #[Test]
    public function createFromUserRecordExtractsDbColumnSettings(): void
    {
        $schema = new UserSettingsSchema();
        $factory = new UserSettingsFactory($schema);

        $userRecord = [
            'user_settings' => json_encode(['colorScheme' => 'dark']),
            'lang' => 'de',
            'email' => 'test@example.com',
            'realName' => 'Test User',
        ];

        $userSettings = $factory->createFromUserRecord($userRecord);

        self::assertSame('de', $userSettings->get('lang'));
        self::assertSame('test@example.com', $userSettings->get('email'));
        self::assertSame('Test User', $userSettings->get('realName'));
    }

    #[Test]
    public function createFromUserRecordFallsBackToUcForMissingJsonFieldSettings(): void
    {
        $schema = new UserSettingsSchema();
        $factory = new UserSettingsFactory($schema);

        $userRecord = [
            'user_settings' => json_encode(['colorScheme' => 'dark']),
            'lang' => 'de',
        ];
        $uc = [
            'titleLen' => 100,
            'emailMeAtLogin' => 1,
        ];

        $userSettings = $factory->createFromUserRecord($userRecord, $uc);

        // From JSON field
        self::assertSame('dark', $userSettings->get('colorScheme'));
        // From uc fallback
        self::assertSame(100, $userSettings->get('titleLen'));
        self::assertSame(1, $userSettings->get('emailMeAtLogin'));
    }

    #[Test]
    public function createFromUserRecordJsonFieldSettingsTakePrecedenceOverUc(): void
    {
        $schema = new UserSettingsSchema();
        $factory = new UserSettingsFactory($schema);

        $userRecord = [
            'user_settings' => json_encode(['colorScheme' => 'dark']),
        ];
        $uc = [
            'colorScheme' => 'light', // This should be ignored
        ];

        $userSettings = $factory->createFromUserRecord($userRecord, $uc);

        self::assertSame('dark', $userSettings->get('colorScheme'));
    }

    #[Test]
    public function createFromUcExtractsOnlyJsonFieldSettings(): void
    {
        $schema = new UserSettingsSchema();
        $factory = new UserSettingsFactory($schema);

        $uc = [
            'colorScheme' => 'dark',
            'titleLen' => 80,
            'emailMeAtLogin' => 1,
            'moduleData' => ['some' => 'data'], // Not in TYPO3_USER_SETTINGS
        ];

        $userSettings = $factory->createFromUc($uc);

        self::assertSame('dark', $userSettings->get('colorScheme'));
        self::assertSame(80, $userSettings->get('titleLen'));
        self::assertSame(1, $userSettings->get('emailMeAtLogin'));
        self::assertFalse($userSettings->has('moduleData'));
    }

    #[Test]
    public function createFromUcIgnoresDbColumnFields(): void
    {
        $schema = new UserSettingsSchema();
        $factory = new UserSettingsFactory($schema);

        $uc = [
            'colorScheme' => 'dark',
            'lang' => 'de', // This is a DB column field, should not be extracted from uc
        ];

        $userSettings = $factory->createFromUc($uc);

        self::assertTrue($userSettings->has('colorScheme'));
        self::assertFalse($userSettings->has('lang'));
    }

    #[Test]
    public function createFromUserRecordMergesDbColumnAndJsonFieldSettings(): void
    {
        $schema = new UserSettingsSchema();
        $factory = new UserSettingsFactory($schema);

        $userRecord = [
            'user_settings' => json_encode(['colorScheme' => 'dark', 'titleLen' => 80]),
            'lang' => 'de',
            'email' => 'test@example.com',
        ];

        $userSettings = $factory->createFromUserRecord($userRecord);
        $allSettings = $userSettings->toArray();

        self::assertArrayHasKey('colorScheme', $allSettings);
        self::assertArrayHasKey('titleLen', $allSettings);
        self::assertArrayHasKey('lang', $allSettings);
        self::assertArrayHasKey('email', $allSettings);
    }
}
