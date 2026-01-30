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

namespace TYPO3\CMS\Backend\Tests\Unit\Backend\Bookmark\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Backend\Backend\Bookmark\Security\BookmarkVoter;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BookmarkVoterTest extends UnitTestCase
{
    private BookmarkVoter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $moduleProvider = $this->createMock(ModuleProvider::class);
        $router = $this->createMock(Router::class);
        $logger = new NullLogger();

        $storageRepository = $this->createMock(StorageRepository::class);
        $this->subject = new BookmarkVoter($moduleProvider, $router, $logger, $storageRepository);
    }

    /**
     * @return \Generator<string, array{userUid: int, userAdmin: bool, expected: bool}>
     */
    public static function createDataProvider(): \Generator
    {
        yield 'regular user can create bookmarks' => [
            'userUid' => 2,
            'userAdmin' => false,
            'expected' => true,
        ];
        yield 'admin can create bookmarks' => [
            'userUid' => 1,
            'userAdmin' => true,
            'expected' => true,
        ];
        yield 'user with uid 0 cannot create bookmarks' => [
            'userUid' => 0,
            'userAdmin' => false,
            'expected' => false,
        ];
    }

    #[DataProvider('createDataProvider')]
    #[Test]
    public function voterCreate(int $userUid, bool $userAdmin, bool $expected): void
    {
        $backendUser = $this->createBackendUser($userUid, $userAdmin);

        self::assertSame($expected, $this->subject->vote(BookmarkVoter::CREATE, [], $backendUser));
    }

    #[Test]
    public function voterCreateDeniedWhenUserUidMissing(): void
    {
        $backendUser = $this->createBackendUser(null, false);

        self::assertFalse($this->subject->vote(BookmarkVoter::CREATE, [], $backendUser));
    }

    /**
     * @return \Generator<string, array{userUid: int, userAdmin: bool, bookmarkUserid: int, bookmarkGroup: int, expected: bool}>
     */
    public static function readDataProvider(): \Generator
    {
        yield 'user reads own private bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => 1,
            'expected' => true,
        ];
        yield 'admin reads own private bookmark' => [
            'userUid' => 1,
            'userAdmin' => true,
            'bookmarkUserid' => 1,
            'bookmarkGroup' => 1,
            'expected' => true,
        ];
        yield 'user reads own bookmark in default group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => 0,
            'expected' => true,
        ];
        yield 'user cannot read other users private bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 1,
            'bookmarkGroup' => 1,
            'expected' => false,
        ];
        yield 'admin cannot read other users private bookmark' => [
            'userUid' => 1,
            'userAdmin' => true,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => 1,
            'expected' => false,
        ];
    }

    #[DataProvider('readDataProvider')]
    #[Test]
    public function voterRead(
        int $userUid,
        bool $userAdmin,
        int $bookmarkUserid,
        int $bookmarkGroup,
        bool $expected,
    ): void {
        $backendUser = $this->createBackendUser($userUid, $userAdmin);
        $bookmark = $this->createBookmark($bookmarkUserid, $bookmarkGroup);

        self::assertSame($expected, $this->subject->vote(BookmarkVoter::READ, $bookmark, $backendUser));
    }

    /**
     * @return \Generator<string, array{userUid: int, userAdmin: bool, bookmarkUserid: int, bookmarkGroup: int, groupUuid: string, expected: bool}>
     */
    public static function editDataProvider(): \Generator
    {
        yield 'user edits own private bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => 1,
            'groupUuid' => '',
            'expected' => true,
        ];
        yield 'admin edits own private bookmark' => [
            'userUid' => 1,
            'userAdmin' => true,
            'bookmarkUserid' => 1,
            'bookmarkGroup' => 1,
            'groupUuid' => '',
            'expected' => true,
        ];
        yield 'user edits own bookmark in default group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => 0,
            'groupUuid' => '',
            'expected' => true,
        ];
        yield 'user edits own bookmark in user-created group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => 0,
            'groupUuid' => 'abc-123-def',
            'expected' => true,
        ];
        yield 'user cannot edit own global bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => -1,
            'groupUuid' => '',
            'expected' => false,
        ];
        yield 'admin edits own global bookmark' => [
            'userUid' => 1,
            'userAdmin' => true,
            'bookmarkUserid' => 1,
            'bookmarkGroup' => -1,
            'groupUuid' => '',
            'expected' => true,
        ];
        yield 'user cannot edit other users private bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 1,
            'bookmarkGroup' => 1,
            'groupUuid' => '',
            'expected' => false,
        ];
        yield 'admin cannot edit other users private bookmark' => [
            'userUid' => 1,
            'userAdmin' => true,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => 1,
            'groupUuid' => '',
            'expected' => false,
        ];
        yield 'admin edits superglobal bookmark' => [
            'userUid' => 1,
            'userAdmin' => true,
            'bookmarkUserid' => 1,
            'bookmarkGroup' => -100,
            'groupUuid' => '',
            'expected' => true,
        ];
        yield 'user cannot edit superglobal bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => -100,
            'groupUuid' => '',
            'expected' => false,
        ];
    }

    #[DataProvider('editDataProvider')]
    #[Test]
    public function voterEdit(
        int $userUid,
        bool $userAdmin,
        int $bookmarkUserid,
        int $bookmarkGroup,
        string $groupUuid,
        bool $expected,
    ): void {
        $backendUser = $this->createBackendUser($userUid, $userAdmin);
        $bookmark = $this->createBookmark($bookmarkUserid, $bookmarkGroup, $groupUuid);

        self::assertSame($expected, $this->subject->vote(BookmarkVoter::EDIT, $bookmark, $backendUser));
    }

    /**
     * @return \Generator<string, array{userUid: int, userAdmin: bool, bookmarkUserid: int, bookmarkGroup: int, expected: bool}>
     */
    public static function deleteDataProvider(): \Generator
    {
        yield 'user deletes own private bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => 1,
            'expected' => true,
        ];
        yield 'admin deletes own private bookmark' => [
            'userUid' => 1,
            'userAdmin' => true,
            'bookmarkUserid' => 1,
            'bookmarkGroup' => 1,
            'expected' => true,
        ];
        yield 'user deletes own global bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => -1,
            'expected' => true,
        ];
        yield 'user cannot delete other users private bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 1,
            'bookmarkGroup' => 1,
            'expected' => false,
        ];
        yield 'admin cannot delete other users private bookmark' => [
            'userUid' => 1,
            'userAdmin' => true,
            'bookmarkUserid' => 2,
            'bookmarkGroup' => 1,
            'expected' => false,
        ];
        yield 'user cannot delete other users global bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 1,
            'bookmarkGroup' => -1,
            'expected' => false,
        ];
        yield 'admin deletes other users global bookmark' => [
            'userUid' => 1,
            'userAdmin' => true,
            'bookmarkUserid' => 3,
            'bookmarkGroup' => -1,
            'expected' => true,
        ];
        yield 'admin deletes superglobal bookmark' => [
            'userUid' => 1,
            'userAdmin' => true,
            'bookmarkUserid' => 3,
            'bookmarkGroup' => -100,
            'expected' => true,
        ];
        yield 'user cannot delete superglobal bookmark' => [
            'userUid' => 2,
            'userAdmin' => false,
            'bookmarkUserid' => 1,
            'bookmarkGroup' => -100,
            'expected' => false,
        ];
    }

    #[DataProvider('deleteDataProvider')]
    #[Test]
    public function voterDelete(
        int $userUid,
        bool $userAdmin,
        int $bookmarkUserid,
        int $bookmarkGroup,
        bool $expected,
    ): void {
        $backendUser = $this->createBackendUser($userUid, $userAdmin);
        $bookmark = $this->createBookmark($bookmarkUserid, $bookmarkGroup);

        self::assertSame($expected, $this->subject->vote(BookmarkVoter::DELETE, $bookmark, $backendUser));
    }

    #[Test]
    public function voterNavigateDeniedWithInvalidJson(): void
    {
        $backendUser = $this->createBackendUser(1, true);
        $bookmark = [
            'uid' => 1,
            'userid' => 1,
            'route' => 'web_layout',
            'arguments' => 'invalid json',
            'sc_group' => 0,
        ];

        self::assertFalse($this->subject->vote(BookmarkVoter::NAVIGATE, $bookmark, $backendUser));
    }

    #[Test]
    public function voterNavigateDeniedWithNullArguments(): void
    {
        $backendUser = $this->createBackendUser(1, true);
        $bookmark = [
            'uid' => 1,
            'userid' => 1,
            'route' => 'web_layout',
            'arguments' => 'null',
            'sc_group' => 0,
        ];

        self::assertFalse($this->subject->vote(BookmarkVoter::NAVIGATE, $bookmark, $backendUser));
    }

    #[Test]
    public function voterNavigateDeniedWithEmptyRoute(): void
    {
        $backendUser = $this->createBackendUser(1, true);
        $bookmark = [
            'uid' => 1,
            'userid' => 1,
            'route' => '',
            'arguments' => '{}',
            'sc_group' => 0,
        ];

        self::assertFalse($this->subject->vote(BookmarkVoter::NAVIGATE, $bookmark, $backendUser));
    }

    #[Test]
    public function voterReturnsFalseForUnknownAttribute(): void
    {
        $backendUser = $this->createBackendUser(1, true);
        $bookmark = $this->createBookmark(1, 0);

        self::assertFalse($this->subject->vote('unknown_attribute', $bookmark, $backendUser));
    }

    private function createBackendUser(?int $uid, bool $admin): BackendUserAuthentication
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $userData = ['admin' => $admin ? 1 : 0];
        if ($uid !== null) {
            $userData['uid'] = $uid;
        }
        $backendUser->user = $userData;
        $backendUser->method('isAdmin')->willReturn($admin);
        return $backendUser;
    }

    private function createBookmark(int $userid, int $scGroup, string $groupUuid = ''): array
    {
        return [
            'uid' => 1,
            'userid' => $userid,
            'description' => 'Test Bookmark',
            'sc_group' => $scGroup,
            'route' => 'web_layout',
            'arguments' => '{"id":1}',
            'group_uuid' => $groupUuid,
        ];
    }
}
