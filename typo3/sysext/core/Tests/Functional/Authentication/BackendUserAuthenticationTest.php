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

use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaRequiredException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendUserAuthenticationTest extends FunctionalTestCase
{
    /**
     * @var AuthenticationService
     */
    protected $authenticationService;

    /**
     * @var BackendUserAuthentication
     */
    protected $subject;

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \TYPO3\TestingFramework\Core\Exception
     */
    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = 'be_typo_user';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'] = 4;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIPv6'] = 8;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] = 28800;

        $this->subject = new BackendUserAuthentication();
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->setUpBackendUser(2);
        /** @var BackendUserAuthentication $backendUser */
        $backendUser = $GLOBALS['BE_USER'];
        $this->subject = $backendUser;
    }

    /**
     * @test
     */
    public function getTranslatedPageOnWebMountIsInWebMountForNonAdminUser(): void
    {
        $result = $this->subject->isInWebMount(2);
        self::assertNotNull($result);
    }

    /**
     * @test
     */
    public function userTsConfigIsResolvedProperlyWithPrioritization(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] = "custom.generic = installation-wide-configuration\ncustom.property = from configuration";
        $this->subject->user['realName'] = 'Test user';
        $this->subject->user['TSconfig'] = 'custom.property = from user';
        $this->subject->userGroupsUID[] = 13;
        $this->subject->userGroups[13]['TSconfig'] = "custom.property = from group\ncustom.groupProperty = 13";
        $this->subject->fetchGroupData();
        $result = $this->subject->getTSConfig();
        self::assertEquals($this->subject->user['realName'], $result['TCAdefaults.']['sys_note.']['author']);
        self::assertEquals('from user', $result['custom.']['property']);
        self::assertEquals('13', $result['custom.']['groupProperty']);
        self::assertEquals('installation-wide-configuration', $result['custom.']['generic']);
    }

    /**
     * @test
     */
    public function returnWebmountsFilterOutInaccessiblePages(): void
    {
        $result = $this->subject->returnWebmounts();

        self::assertNotContains('3', $result, 'Deleted page is not filtered out');
        self::assertNotContains('4', $result, 'Page user has no permission to read is not filtered out');
        self::assertNotContains('5', $result, 'Not existing page is not filtered out');
        self::assertContains('40', $result, 'Accessible db mount page, child of a not accessible page is not shown');
        self::assertEquals(['1', '40'], $result);
    }

    /**
     * @test
     */
    public function getDefaultUploadFolderFallsBackToDefaultStorage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $path = 'user_upload/some-folder-that-does-not-exist';
        $fullPathToStorageBase = Environment::getPublicPath() . '/fileadmin/' . $path;
        GeneralUtility::rmdir($fullPathToStorageBase);
        // Skip access permissions, as this is not checked here
        $this->subject->user['admin'] = 1;
        $this->subject->user['TSconfig'] = 'options.defaultUploadFolder = 1:/' . $path;
        $this->subject->fetchGroupData();
        $folder = $this->subject->getDefaultUploadFolder();
        self::assertEquals('/user_upload/', $folder->getIdentifier());
        // Now create the folder and check again
        GeneralUtility::mkdir_deep($fullPathToStorageBase);
        $folder = $this->subject->getDefaultUploadFolder();
        self::assertEquals('/' . $path . '/', $folder->getIdentifier());
    }

    /**
     * @test
     */
    public function loadGroupsWithProperSettingsAndOrder(): void
    {
        $subject = $this->setUpBackendUser(3);
        $subject->fetchGroupData();
        self::assertEquals('web_info,web_layout,web_list,file_filelist', $subject->groupData['modules']);
        self::assertEquals([1, 4, 5, 3, 2, 6], $subject->userGroupsUID);
        self::assertEquals(['groupValue' => 'from_group_6', 'userValue' => 'from_user_3'], $subject->getTSConfig()['test.']['default.']);
    }

    /**
     * @test
     */
    public function mfaRequiredExceptionIsThrown(): void
    {
        $this->expectException(MfaRequiredException::class);
        // This will setup a user and therefore implicit call the ->checkAuthentication() method
        // which should fail since the user in the fixture has MFA activated but not yet passed.
        $this->setUpBackendUser(4);
    }

    public function isImportEnabledDataProvider(): array
    {
        return [
            'admin user' => [
                1,
                '',
                true,
            ],
            'editor user' => [
                2,
                '',
                false,
            ],
            'editor user - enableImportForNonAdminUser = 1' => [
                2,
                'options.impexp.enableImportForNonAdminUser = 1',
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isImportEnabledDataProvider
     */
    public function isImportEnabledReturnsExpectedValues(int $userId, string $tsConfig, bool $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] = $tsConfig;

        $subject = $this->setUpBackendUser($userId);
        self::assertEquals($expected, $subject->isImportEnabled());
    }

    public function isExportEnabledDataProvider(): array
    {
        return [
            'admin user' => [
                1,
                '',
                true,
            ],
            'editor user' => [
                2,
                '',
                false,
            ],
            'editor user - enableExportForNonAdminUser = 1' => [
                2,
                'options.impexp.enableExportForNonAdminUser = 1',
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isExportEnabledDataProvider
     */
    public function isExportEnabledReturnsExpectedValues(int $userId, string $tsConfig, bool $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] = $tsConfig;

        $subject = $this->setUpBackendUser($userId);
        self::assertEquals($expected, $subject->isExportEnabled());
    }
}
