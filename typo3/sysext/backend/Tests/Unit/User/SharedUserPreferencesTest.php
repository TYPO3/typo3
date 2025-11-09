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

namespace TYPO3\CMS\Backend\Tests\Unit\User;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\User\SharedUserPreferences;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SharedUserPreferencesTest extends UnitTestCase
{
    #[Test]
    public function resolveLanguagesReturnsRequestParameterWhenProvided(): void
    {
        $backendUser = $this->createBackendUserWithUc();
        $preferences = new SharedUserPreferences();

        $result = $preferences->resolveLanguages($backendUser, [0, 1, 2], 123);

        self::assertSame([0, 1, 2], $result);
    }

    #[Test]
    public function resolveLanguagesReturnsPageSpecificPreferenceWhenNoRequestParameter(): void
    {
        $backendUser = $this->createBackendUserWithUc([
            'pageLanguages' => [
                123 => [0, 1],
            ],
        ]);
        $preferences = new SharedUserPreferences();

        $result = $preferences->resolveLanguages($backendUser, null, 123);

        self::assertSame([0, 1], $result);
    }

    #[Test]
    public function resolveLanguagesDoesNotReturnPageSpecificPreferenceWhenRequestParameter(): void
    {
        $backendUser = $this->createBackendUserWithUc([
            'pageLanguages' => [
                123 => [0, 1],
            ],
        ]);
        $preferences = new SharedUserPreferences();

        $result = $preferences->resolveLanguages($backendUser, [2, 3], 123);

        self::assertSame([2, 3], $result);
    }

    #[Test]
    public function resolveLanguagesReturnsModuleDataWhenNoPagePreference(): void
    {
        $backendUser = $this->createBackendUserWithUc();
        $preferences = new SharedUserPreferences();

        $result = $preferences->resolveLanguages($backendUser, null, 123, [0, 2]);

        self::assertSame([0, 2], $result);
    }

    #[Test]
    public function resolveLanguagesReturnsDefaultWhenNoPreferencesExist(): void
    {
        $backendUser = $this->createBackendUserWithUc();
        $preferences = new SharedUserPreferences();

        $result = $preferences->resolveLanguages($backendUser, null, 123);

        self::assertSame([0], $result);
    }

    #[Test]
    public function resolveLanguagesConvertsStringValuesToIntegers(): void
    {
        $backendUser = $this->createBackendUserWithUc();
        $preferences = new SharedUserPreferences();

        $result = $preferences->resolveLanguages($backendUser, ['0', '1', '2'], 123);

        self::assertSame([0, 1, 2], $result);
    }

    #[Test]
    public function resolveLanguagesReturnsEmptyArrayAsDefault(): void
    {
        $backendUser = $this->createBackendUserWithUc();
        $preferences = new SharedUserPreferences();

        $result = $preferences->resolveLanguages($backendUser, [], 123);

        self::assertSame([0], $result);
    }

    #[Test]
    public function setPageLanguagesStoresInCorrectStructure(): void
    {
        $backendUser = $this->createBackendUserWithUc();
        $preferences = new SharedUserPreferences();

        $preferences->setPageLanguages($backendUser, 123, [0, 1, 2]);

        self::assertSame([0, 1, 2], $backendUser->uc['pageLanguages'][123]);
    }

    #[Test]
    public function fallbackChainPrioritizesCorrectly(): void
    {
        $backendUser = $this->createBackendUserWithUc([
            'pageLanguages' => [
                123 => [0, 1],        // Should win for page 123
                456 => [0, 2],
            ],
        ]);
        $preferences = new SharedUserPreferences();

        // Page-specific should win over ModuleData
        $resultForPage123 = $preferences->resolveLanguages($backendUser, null, 123, [0, 3]);
        self::assertSame([0, 1], $resultForPage123);

        // ModuleData should win when no page-specific
        $resultForPage789 = $preferences->resolveLanguages($backendUser, null, 789, [0, 3]);
        self::assertSame([0, 3], $resultForPage789);

        // Default should be used when no page-specific or ModuleData
        $resultForPage999 = $preferences->resolveLanguages($backendUser, null, 999);
        self::assertSame([0], $resultForPage999);
    }

    private function createBackendUserWithUc(array $uc = []): BackendUserAuthentication
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->uc = $uc;
        $backendUser->method('writeUC')->willReturnCallback(function (): void {
            // Simulate UC write (no-op for tests)
        });
        return $backendUser;
    }
}
