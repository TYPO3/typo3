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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\Mfa\MfaRequiredException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendUserAuthenticationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_defaulttsconfig',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_filemounts.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
    }

    #[Test]
    public function getFileMountRecordsReturnsFilemounts(): void
    {
        $backendUser = $this->setUpBackendUser(3);
        self::assertCount(3, $backendUser->getFileMountRecords());
    }

    #[Test]
    public function getTranslatedPageOnWebMountIsInWebMountForNonAdminUser(): void
    {
        $subject = $this->setUpBackendUser(2);
        self::assertNotNull($subject->isInWebMount(2));
    }

    #[Test]
    public function userTsConfigIsResolvedProperlyWithPrioritization(): void
    {
        // Uses ext:test_defaulttsconfig/Configuration/user.tsconfig
        $subject = $this->setUpBackendUser(2);
        $subject->user['TSconfig'] = 'custom.property = from user';
        $subject->userGroupsUID[] = 13;
        $subject->userGroups[13]['TSconfig'] = "custom.property = from group\ncustom.groupProperty = 13";
        $subject->fetchGroupData();
        $result = $subject->getTSConfig();
        self::assertEquals('from user', $result['custom.']['property']);
        self::assertEquals('13', $result['custom.']['groupProperty']);
        self::assertEquals('installation-wide-configuration', $result['custom.']['generic']);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function returnWebmountsFilterOutInaccessiblePages(): void
    {
        $subject = $this->setUpBackendUser(2);
        $result = $subject->returnWebmounts();
        self::assertNotContains('3', $result, 'Deleted page is not filtered out');
        self::assertNotContains('4', $result, 'Page user has no permission to read is not filtered out');
        self::assertNotContains('5', $result, 'Not existing page is not filtered out');
        self::assertContains('40', $result, 'Accessible db mount page, child of a not accessible page is not shown');
        self::assertEquals(['1', '40'], $result);
    }

    #[Test]
    public function getWebmountsFilterOutInaccessiblePages(): void
    {
        $subject = $this->setUpBackendUser(2);
        $result = $subject->getWebmounts();
        self::assertNotContains(3, $result, 'Deleted page is not filtered out');
        self::assertNotContains(4, $result, 'Page user has no permission to read is not filtered out');
        self::assertNotContains(5, $result, 'Not existing page is not filtered out');
        self::assertContains(40, $result, 'Accessible db mount page, child of a not accessible page is not shown');
        self::assertEquals([1, 40], $result);
    }

    #[Test]
    public function loadGroupsWithProperSettingsAndOrder(): void
    {
        $subject = $this->setUpBackendUser(3);
        $subject->fetchGroupData();
        self::assertEquals('web_info,web_layout,web_list,file_filelist', $subject->groupData['modules']);
        self::assertEquals([1, 4, 5, 3, 2, 6], $subject->userGroupsUID);
        self::assertEquals(['groupValue' => 'from_group_6', 'userValue' => 'from_user_3'], $subject->getTSConfig()['test.']['default.']);
    }

    #[Test]
    public function mfaRequiredExceptionIsThrown(): void
    {
        $this->expectException(MfaRequiredException::class);
        // This will set up a user and therefore implicit call the ->checkAuthentication() method
        // which should fail since the user in the fixture has MFA activated but not yet passed.
        $this->setUpBackendUser(4);
    }

    public static function isImportEnabledDataProvider(): array
    {
        return [
            'admin user' => [
                1,
                true,
            ],
            'editor user' => [
                2,
                false,
            ],
            'editor user - enableImportForNonAdminUser = 1' => [
                6,
                true,
            ],
        ];
    }

    #[DataProvider('isImportEnabledDataProvider')]
    #[Test]
    public function isImportEnabledReturnsExpectedValues(int $userId, bool $expected): void
    {
        $subject = $this->setUpBackendUser($userId);
        self::assertEquals($expected, $subject->isImportEnabled());
    }

    public static function isExportEnabledDataProvider(): array
    {
        return [
            'admin user' => [
                1,
                true,
            ],
            'editor user' => [
                2,
                false,
            ],
            'editor user - enableExportForNonAdminUser = 1' => [
                6,
                true,
            ],
        ];
    }

    #[DataProvider('isExportEnabledDataProvider')]
    #[Test]
    public function isExportEnabledReturnsExpectedValues(int $userId, bool $expected): void
    {
        $subject = $this->setUpBackendUser($userId);
        self::assertEquals($expected, $subject->isExportEnabled());
    }
}
