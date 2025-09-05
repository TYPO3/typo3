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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\IpLocker;
use TYPO3\CMS\Core\Authentication\JsConfirmation;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform\MockMySQLPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BackendUserAuthenticationTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function logoffCleansFormProtectionIfBackendUserIsLoggedIn(): void
    {
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('delete')->with('sys_lockedrecords', self::anything())->willReturn(1);

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getConnectionForTable')->with(self::anything())->willReturn($connectionMock);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        $formProtectionMock = $this->createMock(BackendFormProtection::class);
        $formProtectionMock->expects($this->once())->method('clean');

        $runtimeCache = new VariableFrontend('null', new TransientMemoryBackend('null', ['logger' => new NullLogger()]));
        $formProtectionFactory = new FormProtectionFactory(
            $this->createMock(FlashMessageService::class),
            $this->createMock(LanguageServiceFactory::class),
            $this->createMock(Registry::class),
            $runtimeCache
        );
        GeneralUtility::addInstance(FormProtectionFactory::class, $formProtectionFactory);
        GeneralUtility::addInstance(BackendFormProtection::class, $formProtectionMock);
        GeneralUtility::setSingletonInstance(EventDispatcherInterface::class, new EventDispatcher($this->createMock(ListenerProviderInterface::class)));

        $sessionBackendMock = $this->createMock(SessionBackendInterface::class);
        $sessionBackendMock->method('remove')->with(self::anything())->willReturn(true);
        $userSessionManager = new UserSessionManager(
            $sessionBackendMock,
            86400,
            new IpLocker(0, 0),
            'BE'
        );

        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)->getMock();
        $GLOBALS['BE_USER']->user = [
            'uid' => 4711,
        ];
        $GLOBALS['BE_USER']->setLogger(new NullLogger());
        $GLOBALS['BE_USER']->initializeUserSessionManager($userSessionManager);

        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $subject->setLogger(new NullLogger());
        $subject->initializeUserSessionManager($userSessionManager);
        $subject->logoff();
    }

    public static function getFilePermissionsTakesUserDefaultAndStoragePermissionsIntoAccountIfUserIsNotAdminDataProvider(): array
    {
        return [
            'Only read permissions' => [
                [
                    'addFile' => 0,
                    'readFile' => 1,
                    'writeFile' => 0,
                    'copyFile' => 0,
                    'moveFile' => 0,
                    'renameFile' => 0,
                    'deleteFile' => 0,
                    'addFolder' => 0,
                    'readFolder' => 1,
                    'copyFolder' => 0,
                    'moveFolder' => 0,
                    'renameFolder' => 0,
                    'writeFolder' => 0,
                    'deleteFolder' => 0,
                    'recursivedeleteFolder' => 0,
                ],
            ],
            'Uploading allowed' => [
                [
                    'addFile' => 1,
                    'readFile' => 1,
                    'writeFile' => 1,
                    'copyFile' => 1,
                    'moveFile' => 1,
                    'renameFile' => 1,
                    'deleteFile' => 1,
                    'addFolder' => 0,
                    'readFolder' => 1,
                    'copyFolder' => 0,
                    'moveFolder' => 0,
                    'renameFolder' => 0,
                    'writeFolder' => 0,
                    'deleteFolder' => 0,
                    'recursivedeleteFolder' => 0,
                ],
            ],
            'One value is enough' => [
                [
                    'addFile' => 1,
                ],
            ],
        ];
    }

    #[DataProvider('getFilePermissionsTakesUserDefaultAndStoragePermissionsIntoAccountIfUserIsNotAdminDataProvider')]
    #[Test]
    public function getFilePermissionsTakesUserDefaultPermissionsFromTsConfigIntoAccountIfUserIsNotAdmin(array $userTsConfiguration): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['isAdmin', 'getTSConfig'])
            ->getMock();

        $subject
            ->method('isAdmin')
            ->willReturn(false);

        $subject->setLogger(new NullLogger());
        $subject
            ->method('getTSConfig')
            ->willReturn([
                'permissions.' => [
                    'file.' => [
                        'default.' => $userTsConfiguration,
                    ],
                ],
            ]);
        $defaultFilePermissions = [
            // File permissions
            'addFile' => false,
            'readFile' => false,
            'writeFile' => false,
            'copyFile' => false,
            'moveFile' => false,
            'renameFile' => false,
            'deleteFile' => false,
            // Folder permissions
            'addFolder' => false,
            'readFolder' => false,
            'writeFolder' => false,
            'copyFolder' => false,
            'moveFolder' => false,
            'renameFolder' => false,
            'deleteFolder' => false,
            'recursivedeleteFolder' => false,
        ];
        $expectedPermissions = array_merge($defaultFilePermissions, $userTsConfiguration);
        array_walk(
            $expectedPermissions,
            static function (&$value) {
                $value = (bool)$value;
            }
        );

        self::assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    public static function getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdminDataProvider(): array
    {
        return [
            'No permission' => [
                '',
                [
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'deleteFile' => false,
                    'addFolder' => false,
                    'readFolder' => false,
                    'copyFolder' => false,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'writeFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => false,
                ],
            ],
            'Standard file permissions' => [
                'addFile,readFile,writeFile,copyFile,moveFile,renameFile,deleteFile',
                [
                    'addFile' => true,
                    'readFile' => true,
                    'writeFile' => true,
                    'copyFile' => true,
                    'moveFile' => true,
                    'renameFile' => true,
                    'deleteFile' => true,
                    'addFolder' => false,
                    'readFolder' => false,
                    'copyFolder' => false,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'writeFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => false,
                ],
            ],
            'Standard folder permissions' => [
                'addFolder,readFolder,moveFolder,renameFolder,writeFolder,deleteFolder',
                [
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'deleteFile' => false,
                    'addFolder' => true,
                    'readFolder' => true,
                    'writeFolder' => true,
                    'copyFolder' => false,
                    'moveFolder' => true,
                    'renameFolder' => true,
                    'deleteFolder' => true,
                    'recursivedeleteFolder' => false,
                ],
            ],
            'Copy folder allowed' => [
                'readFolder,copyFolder',
                [
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'deleteFile' => false,
                    'addFolder' => false,
                    'readFolder' => true,
                    'writeFolder' => false,
                    'copyFolder' => true,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => false,
                ],
            ],
            'Copy folder and remove subfolders allowed' => [
                'readFolder,copyFolder,recursivedeleteFolder',
                [
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'deleteFile' => false,
                    'addFolder' => false,
                    'readFolder' => true,
                    'writeFolder' => false,
                    'copyFolder' => true,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => true,
                ],
            ],
        ];
    }

    #[DataProvider('getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdminDataProvider')]
    #[Test]
    public function getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdmin(string $permissionValue, array $expectedPermissions): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['isAdmin', 'getTSConfig'])
            ->getMock();

        $subject
            ->method('isAdmin')
            ->willReturn(false);

        $subject
            ->method('getTSConfig')
            ->willReturn([]);
        $subject->groupData['file_permissions'] = $permissionValue;
        self::assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    #[Test]
    public function getFilePermissionsGrantsAllPermissionsToAdminUsers(): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $subject
            ->method('isAdmin')
            ->willReturn(true);

        $expectedPermissions = [
            'addFile' => true,
            'readFile' => true,
            'writeFile' => true,
            'copyFile' => true,
            'moveFile' => true,
            'renameFile' => true,
            'deleteFile' => true,
            'addFolder' => true,
            'readFolder' => true,
            'writeFolder' => true,
            'copyFolder' => true,
            'moveFolder' => true,
            'renameFolder' => true,
            'deleteFolder' => true,
            'recursivedeleteFolder' => true,
        ];

        self::assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    #[Test]
    public function jsConfirmationReturnsTrueIfPassedValueEqualsConfiguration(): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with()->willReturn([
            'options.' => [
                'alertPopups' => 1,
            ],
        ]);
        self::assertTrue($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        self::assertFalse($subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
    }

    #[Test]
    public function jsConfirmationAllowsSettingMultipleBitsInValue(): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with()->willReturn([
            'options.' => [
                'alertPopups' => 3,
            ],
        ]);
        self::assertTrue($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        self::assertTrue($subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
    }

    #[DataProvider('jsConfirmationsWithUnsetBits')]
    #[Test]
    public function jsConfirmationAllowsUnsettingBitsInValue(int $jsConfirmation, bool $typeChangeAllowed, bool $copyMovePasteAllowed, bool $deleteAllowed, bool $feEditAllowed, bool $otherAllowed): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with()->willReturn([
            'options.' => [
                'alertPopups' => $jsConfirmation,
            ],
        ]);
        self::assertEquals($typeChangeAllowed, $subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        self::assertEquals($copyMovePasteAllowed, $subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
        self::assertEquals($deleteAllowed, $subject->jsConfirmation(JsConfirmation::DELETE));
        self::assertEquals($feEditAllowed, $subject->jsConfirmation(JsConfirmation::FE_EDIT));
        self::assertEquals($otherAllowed, $subject->jsConfirmation(JsConfirmation::OTHER));
    }

    public static function jsConfirmationsWithUnsetBits(): array
    {
        return [
            'All except "type change" and "copy/move/paste"' => [
                252,
                false,
                false,
                true,
                true,
                true,
            ],
            'All except "other"' => [
                127,
                true,
                true,
                true,
                true,
                false,
            ],
        ];
    }

    #[Test]
    public function jsConfirmationAlwaysReturnsFalseIfNoConfirmationIsSet(): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with()->willReturn([
            'options.' => [
                'alertPopups' => 0,
            ],
        ]);
        self::assertFalse($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        self::assertFalse($subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
    }

    #[Test]
    public function jsConfirmationReturnsTrueIfConfigurationIsMissing(): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['getTSConfig'])
            ->getMock();

        self::assertTrue($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
    }

    /**
     * Data provider to test page permissions constraints
     * cares for non-admin users
     */
    public static function getPagePermissionsClauseWithValidNonAdminUserDataProvider(): array
    {
        return [
            'for user' => [
                'perms' => 2,
                'groups' => [],
                'expected' => ' (((`pages`.`perms_everybody` & 2 = 2) OR' .
                ' (((`pages`.`perms_userid` = 123) AND (`pages`.`perms_user` & 2 = 2)))))',
            ],
            'for user with groups' => [
                'perms' => 8,
                'groups' => [1, 2],
                'expected' => ' (((`pages`.`perms_everybody` & 8 = 8) OR' .
                ' (((`pages`.`perms_userid` = 123) AND (`pages`.`perms_user` & 8 = 8)))' .
                ' OR (((`pages`.`perms_groupid` IN (1, 2)) AND (`pages`.`perms_group` & 8 = 8)))))',
            ],
        ];
    }

    #[DataProvider('getPagePermissionsClauseWithValidNonAdminUserDataProvider')]
    #[Test]
    public function getPagePermissionsClauseWithValidUser(int $perms, array $groups, string $expected): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('getDatabasePlatform')->willReturn(new MockMySQLPlatform());
        $connectionMock->method('quoteIdentifier')
            ->willReturnCallback(fn(string $identifier): string => '`' . str_replace('.', '`.`', $identifier) . '`');

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('expr')->willReturn(
            new ExpressionBuilder($connectionMock)
        );

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getQueryBuilderForTable')->with('pages')->willReturn($queryBuilderMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        $subject = new BackendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->user = ['uid' => 123];
        $subject->userGroupsUID = $groups;

        self::assertEquals($expected, $subject->getPagePermsClause($perms));
    }

    /**
     * Data provider to test page permissions constraints
     * cares for privileged (admin) users
     */
    public static function getPagePermissionsClauseWithValidAdminUserDataProvider(): array
    {
        return [
            'for admin' => [
                'perms' => 1,
                'groups' => [],
                'expected' => ' 1=1',
            ],
            'for admin with groups' => [
                'perms' => 11,
                'groups' => [1, 2],
                'expected' => ' 1=1',
            ],

        ];
    }

    #[DataProvider('getPagePermissionsClauseWithValidAdminUserDataProvider')]
    #[Test]
    public function getPagePermissionsClauseWithValidAdminUser(int $perms, array $groups, string $expected): void
    {
        $subject = new BackendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->user = ['uid' => 123, 'admin' => 1];
        $subject->userGroupsUID = $groups;

        self::assertEquals($expected, $subject->getPagePermsClause($perms));
    }

    #[DataProvider('checkAuthModeReturnsExpectedValueDataProvider')]
    #[Test]
    public function checkAuthModeReturnsExpectedValue(string $theValue, bool $expectedResult): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $subject
            ->method('isAdmin')
            ->willReturn(false);

        $subject->groupData['explicit_allowdeny'] =
            'dummytable:dummyfield:explicitly_allowed_value,'
            . 'dummytable:dummyfield:explicitly_denied_value';

        $result = $subject->checkAuthMode('dummytable', 'dummyfield', $theValue);
        self::assertEquals($expectedResult, $result);
    }

    public static function checkAuthModeReturnsExpectedValueDataProvider(): array
    {
        return [
            'explicit allow, not allowed value' => [
                'non_allowed_field',
                false,
            ],
            'explicit allow, allowed value' => [
                'explicitly_allowed_value',
                true,
            ],
            'invalid value colon' => [
                'containing:invalid:chars',
                false,
            ],
            'invalid value comma' => [
                'containing,invalid,chars',
                false,
            ],
            'blank value' => [
                '',
                true,
            ],
            'divider' => [
                '--div--',
                true,
            ],
        ];
    }
}
