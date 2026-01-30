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
use TYPO3\CMS\Backend\Backend\Bookmark\Security\BookmarkGroupVoter;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BookmarkGroupVoterTest extends UnitTestCase
{
    private BookmarkGroupVoter $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new BookmarkGroupVoter();
    }

    /**
     * @return \Generator<string, array{userUid: int, userAdmin: bool, expected: bool}>
     */
    public static function createDataProvider(): \Generator
    {
        yield 'regular user can create groups' => [
            'userUid' => 2,
            'userAdmin' => false,
            'expected' => true,
        ];
        yield 'admin can create groups' => [
            'userUid' => 1,
            'userAdmin' => true,
            'expected' => true,
        ];
        yield 'user with uid 0 cannot create groups' => [
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

        self::assertSame($expected, $this->subject->vote(BookmarkGroupVoter::CREATE, [], $backendUser));
    }

    /**
     * @return \Generator<string, array{userUid: int, userAdmin: bool, groupUserid: int, expected: bool}>
     */
    public static function readDataProvider(): \Generator
    {
        yield 'user reads own group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupUserid' => 2,
            'expected' => true,
        ];
        yield 'admin reads own group' => [
            'userUid' => 1,
            'userAdmin' => true,
            'groupUserid' => 1,
            'expected' => true,
        ];
        yield 'user cannot read other users group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupUserid' => 1,
            'expected' => false,
        ];
        yield 'admin cannot read other users group' => [
            'userUid' => 1,
            'userAdmin' => true,
            'groupUserid' => 2,
            'expected' => false,
        ];
    }

    #[DataProvider('readDataProvider')]
    #[Test]
    public function voterRead(int $userUid, bool $userAdmin, int $groupUserid, bool $expected): void
    {
        $backendUser = $this->createBackendUser($userUid, $userAdmin);
        $group = $this->createGroup('abc-123', $groupUserid);

        self::assertSame($expected, $this->subject->vote(BookmarkGroupVoter::READ, $group, $backendUser));
    }

    /**
     * @return \Generator<string, array{userUid: int, userAdmin: bool, groupId: int|string, groupUserid: ?int, expected: bool}>
     */
    public static function editDataProvider(): \Generator
    {
        yield 'user edits own user-created group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 'abc-123-def',
            'groupUserid' => 2,
            'expected' => true,
        ];
        yield 'admin edits own user-created group' => [
            'userUid' => 1,
            'userAdmin' => true,
            'groupId' => 'abc-123-def',
            'groupUserid' => 1,
            'expected' => true,
        ];
        yield 'user cannot edit other users user-created group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 'abc-123-def',
            'groupUserid' => 1,
            'expected' => false,
        ];
        yield 'admin cannot edit other users user-created group' => [
            'userUid' => 1,
            'userAdmin' => true,
            'groupId' => 'abc-123-def',
            'groupUserid' => 2,
            'expected' => false,
        ];
        yield 'user cannot edit system group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 1,
            'groupUserid' => null,
            'expected' => false,
        ];
        yield 'admin cannot edit system group' => [
            'userUid' => 1,
            'userAdmin' => true,
            'groupId' => 1,
            'groupUserid' => null,
            'expected' => false,
        ];
        yield 'user cannot edit default group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 0,
            'groupUserid' => null,
            'expected' => false,
        ];
        yield 'user cannot edit global group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => -1,
            'groupUserid' => null,
            'expected' => false,
        ];
        yield 'admin cannot edit global group' => [
            'userUid' => 1,
            'userAdmin' => true,
            'groupId' => -1,
            'groupUserid' => null,
            'expected' => false,
        ];
        yield 'admin cannot edit superglobal group' => [
            'userUid' => 1,
            'userAdmin' => true,
            'groupId' => -100,
            'groupUserid' => null,
            'expected' => false,
        ];
        yield 'cannot edit group without userid' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 'abc-123-def',
            'groupUserid' => null,
            'expected' => false,
        ];
    }

    #[DataProvider('editDataProvider')]
    #[Test]
    public function voterEdit(
        int $userUid,
        bool $userAdmin,
        int|string $groupId,
        ?int $groupUserid,
        bool $expected,
    ): void {
        $backendUser = $this->createBackendUser($userUid, $userAdmin);
        $group = $this->createGroup($groupId, $groupUserid);

        self::assertSame($expected, $this->subject->vote(BookmarkGroupVoter::EDIT, $group, $backendUser));
    }

    #[Test]
    public function voterEditDeniedWithNumericStringGroupId(): void
    {
        $backendUser = $this->createBackendUser(2, false);
        $group = ['id' => '123', 'userid' => 2];

        self::assertFalse($this->subject->vote(BookmarkGroupVoter::EDIT, $group, $backendUser));
    }

    /**
     * @return \Generator<string, array{userUid: int, userAdmin: bool, groupId: int|string, groupUserid: ?int, expected: bool}>
     */
    public static function deleteDataProvider(): \Generator
    {
        yield 'user deletes own user-created group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 'abc-123-def',
            'groupUserid' => 2,
            'expected' => true,
        ];
        yield 'user cannot delete other users user-created group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 'abc-123-def',
            'groupUserid' => 1,
            'expected' => false,
        ];
        yield 'user cannot delete system group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 1,
            'groupUserid' => null,
            'expected' => false,
        ];
        yield 'admin cannot delete global group' => [
            'userUid' => 1,
            'userAdmin' => true,
            'groupId' => -1,
            'groupUserid' => null,
            'expected' => false,
        ];
    }

    #[DataProvider('deleteDataProvider')]
    #[Test]
    public function voterDelete(
        int $userUid,
        bool $userAdmin,
        int|string $groupId,
        ?int $groupUserid,
        bool $expected,
    ): void {
        $backendUser = $this->createBackendUser($userUid, $userAdmin);
        $group = $this->createGroup($groupId, $groupUserid);

        self::assertSame($expected, $this->subject->vote(BookmarkGroupVoter::DELETE, $group, $backendUser));
    }

    /**
     * @return \Generator<string, array{userUid: int, userAdmin: bool, groupId: int|string, expected: bool}>
     */
    public static function selectDataProvider(): \Generator
    {
        yield 'user selects user-created group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 'abc-123-def',
            'expected' => true,
        ];
        yield 'user selects system group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 1,
            'expected' => true,
        ];
        yield 'user selects default group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => 0,
            'expected' => true,
        ];
        yield 'user cannot select global group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => -1,
            'expected' => false,
        ];
        yield 'admin selects global group' => [
            'userUid' => 1,
            'userAdmin' => true,
            'groupId' => -1,
            'expected' => true,
        ];
        yield 'user cannot select superglobal group' => [
            'userUid' => 2,
            'userAdmin' => false,
            'groupId' => -100,
            'expected' => false,
        ];
        yield 'admin selects superglobal group' => [
            'userUid' => 1,
            'userAdmin' => true,
            'groupId' => -100,
            'expected' => true,
        ];
    }

    #[DataProvider('selectDataProvider')]
    #[Test]
    public function voterSelect(int $userUid, bool $userAdmin, int|string $groupId, bool $expected): void
    {
        $backendUser = $this->createBackendUser($userUid, $userAdmin);
        $group = $this->createGroup($groupId, null);

        self::assertSame($expected, $this->subject->vote(BookmarkGroupVoter::SELECT, $group, $backendUser));
    }

    #[Test]
    public function voterReturnsFalseForUnknownAttribute(): void
    {
        $backendUser = $this->createBackendUser(1, true);
        $group = $this->createGroup('abc-123', 1);

        self::assertFalse($this->subject->vote('unknown_attribute', $group, $backendUser));
    }

    private function createBackendUser(int $uid, bool $admin): BackendUserAuthentication
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['uid' => $uid, 'admin' => $admin ? 1 : 0];
        $backendUser->method('isAdmin')->willReturn($admin);
        return $backendUser;
    }

    private function createGroup(int|string $id, ?int $userid = null): array
    {
        return [
            'id' => $id,
            'userid' => $userid,
            'label' => 'Test Group',
        ];
    }
}
