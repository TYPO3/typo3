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
use TYPO3\CMS\Core\Authentication\Exception\UserSettingsNotFoundException;
use TYPO3\CMS\Core\Authentication\UserSettings;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UserSettingsTest extends UnitTestCase
{
    #[Test]
    public function hasReturnsTrueForExistingKey(): void
    {
        $settings = new UserSettings(['colorScheme' => 'dark']);
        self::assertTrue($settings->has('colorScheme'));
    }

    #[Test]
    public function hasReturnsFalseForNonExistingKey(): void
    {
        $settings = new UserSettings(['colorScheme' => 'dark']);
        self::assertFalse($settings->has('nonExisting'));
    }

    #[Test]
    public function hasReturnsTrueForNullValue(): void
    {
        $settings = new UserSettings(['nullValue' => null]);
        self::assertTrue($settings->has('nullValue'));
    }

    #[Test]
    public function getReturnsValueForExistingKey(): void
    {
        $settings = new UserSettings(['colorScheme' => 'dark', 'titleLen' => 50]);
        self::assertSame('dark', $settings->get('colorScheme'));
        self::assertSame(50, $settings->get('titleLen'));
    }

    #[Test]
    public function getReturnsNullForExistingKeyWithNullValue(): void
    {
        $settings = new UserSettings(['nullValue' => null]);
        self::assertNull($settings->get('nullValue'));
    }

    #[Test]
    public function getThrowsExceptionForNonExistingKey(): void
    {
        $settings = new UserSettings(['colorScheme' => 'dark']);
        $this->expectException(UserSettingsNotFoundException::class);
        $this->expectExceptionCode(1738500000);
        $settings->get('nonExisting');
    }

    #[Test]
    public function toArrayReturnsAllSettings(): void
    {
        $settings = new UserSettings([
            'lang' => 'de',
            'email' => 'test@example.com',
            'colorScheme' => 'dark',
            'titleLen' => 50,
        ]);
        $expected = [
            'lang' => 'de',
            'email' => 'test@example.com',
            'colorScheme' => 'dark',
            'titleLen' => 50,
        ];
        self::assertSame($expected, $settings->toArray());
    }

    #[Test]
    public function toArrayReturnsEmptyArrayForEmptySettings(): void
    {
        $settings = new UserSettings([]);
        self::assertSame([], $settings->toArray());
    }

    #[Test]
    public function isEmailMeAtLoginEnabledReturnsTrueWhenEnabled(): void
    {
        $settings = new UserSettings(['emailMeAtLogin' => 1]);
        self::assertTrue($settings->isEmailMeAtLoginEnabled());
    }

    #[Test]
    public function isEmailMeAtLoginEnabledReturnsFalseWhenDisabled(): void
    {
        $settings = new UserSettings(['emailMeAtLogin' => 0]);
        self::assertFalse($settings->isEmailMeAtLoginEnabled());
    }

    #[Test]
    public function isEmailMeAtLoginEnabledReturnsFalseByDefault(): void
    {
        $settings = new UserSettings([]);
        self::assertFalse($settings->isEmailMeAtLoginEnabled());
    }

    #[Test]
    public function isUploadFieldsInTopOfEBEnabledReturnsTrueWhenEnabled(): void
    {
        $settings = new UserSettings(['edit_docModuleUpload' => '1']);
        self::assertTrue($settings->isUploadFieldsInTopOfEBEnabled());
    }

    #[Test]
    public function isUploadFieldsInTopOfEBEnabledReturnsFalseWhenDisabled(): void
    {
        $settings = new UserSettings(['edit_docModuleUpload' => 0]);
        self::assertFalse($settings->isUploadFieldsInTopOfEBEnabled());
    }

    #[Test]
    public function isUploadFieldsInTopOfEBEnabledReturnsTrueByDefault(): void
    {
        $settings = new UserSettings([]);
        self::assertTrue($settings->isUploadFieldsInTopOfEBEnabled());
    }
}
