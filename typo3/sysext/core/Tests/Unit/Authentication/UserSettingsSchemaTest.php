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
use TYPO3\CMS\Core\Authentication\UserSettingsSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UserSettingsSchemaTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_USER_SETTINGS']);
        parent::tearDown();
    }

    #[Test]
    public function getJsonFieldSettingKeysReturnsKeysWithoutTable(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'colorScheme' => ['type' => 'select'],
                'titleLen' => ['type' => 'number'],
                'email' => ['type' => 'email', 'table' => 'be_users'],
                'lang' => ['type' => 'language', 'table' => 'be_users'],
            ],
        ];

        $schema = new UserSettingsSchema();
        $keys = $schema->getJsonFieldSettingKeys();

        self::assertContains('colorScheme', $keys);
        self::assertContains('titleLen', $keys);
        self::assertNotContains('email', $keys);
        self::assertNotContains('lang', $keys);
    }

    #[Test]
    public function getJsonFieldSettingKeysExcludesButtonAndMfaTypes(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'colorScheme' => ['type' => 'select'],
                'resetConfiguration' => ['type' => 'button'],
                'mfaProviders' => ['type' => 'mfa'],
            ],
        ];

        $schema = new UserSettingsSchema();
        $keys = $schema->getJsonFieldSettingKeys();

        self::assertContains('colorScheme', $keys);
        self::assertNotContains('resetConfiguration', $keys);
        self::assertNotContains('mfaProviders', $keys);
    }

    #[Test]
    public function getDbColumnSettingKeysReturnsKeysWithTable(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'colorScheme' => ['type' => 'select'],
                'email' => ['type' => 'email', 'table' => 'be_users'],
                'lang' => ['type' => 'language', 'table' => 'be_users'],
                'password' => ['type' => 'password', 'table' => 'be_users'],
            ],
        ];

        $schema = new UserSettingsSchema();
        $keys = $schema->getDbColumnSettingKeys();

        self::assertNotContains('colorScheme', $keys);
        self::assertContains('email', $keys);
        self::assertContains('lang', $keys);
        // password type is excluded
        self::assertNotContains('password', $keys);
    }

    #[Test]
    public function isJsonFieldSettingReturnsTrueForJsonFieldSetting(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'colorScheme' => ['type' => 'select'],
            ],
        ];

        $schema = new UserSettingsSchema();
        self::assertTrue($schema->isJsonFieldSetting('colorScheme'));
    }

    #[Test]
    public function isJsonFieldSettingReturnsFalseForTableField(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'email' => ['type' => 'email', 'table' => 'be_users'],
            ],
        ];

        $schema = new UserSettingsSchema();
        self::assertFalse($schema->isJsonFieldSetting('email'));
    }

    #[Test]
    public function isJsonFieldSettingReturnsFalseForUnknownField(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'colorScheme' => ['type' => 'select'],
            ],
        ];

        $schema = new UserSettingsSchema();
        self::assertFalse($schema->isJsonFieldSetting('unknownField'));
    }

    #[Test]
    public function isDbColumnSettingReturnsTrueForDbColumnSetting(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'lang' => ['type' => 'language', 'table' => 'be_users'],
            ],
        ];

        $schema = new UserSettingsSchema();
        self::assertTrue($schema->isDbColumnSetting('lang'));
    }

    #[Test]
    public function isDbColumnSettingReturnsFalseForJsonFieldSetting(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'colorScheme' => ['type' => 'select'],
            ],
        ];

        $schema = new UserSettingsSchema();
        self::assertFalse($schema->isDbColumnSetting('colorScheme'));
    }

    #[Test]
    public function getDefaultReturnsDefaultValue(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'displayRecentlyUsed' => ['type' => 'check', 'default' => 1],
            ],
        ];

        $schema = new UserSettingsSchema();
        self::assertSame(1, $schema->getDefault('displayRecentlyUsed'));
    }

    #[Test]
    public function getDefaultReturnsNullForMissingDefault(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'colorScheme' => ['type' => 'select'],
            ],
        ];

        $schema = new UserSettingsSchema();
        self::assertNull($schema->getDefault('colorScheme'));
    }

    #[Test]
    public function getDefaultReturnsNullForUnknownField(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [],
        ];

        $schema = new UserSettingsSchema();
        self::assertNull($schema->getDefault('unknownField'));
    }
}
