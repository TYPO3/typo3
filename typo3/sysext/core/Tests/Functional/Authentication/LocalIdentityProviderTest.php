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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\LocalIdentityProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LocalIdentityProviderTest extends FunctionalTestCase
{
    private LocalIdentityProvider $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LocalIdentityProviderUsers.csv');
        $this->subject = $this->get(LocalIdentityProvider::class);
    }

    #[Test]
    public function findsBackendUserByUsername(): void
    {
        $record = $this->subject->findBackendUserByUsername('rootadmin');
        self::assertSame(100, $record['uid'] ?? null);
    }

    #[Test]
    public function findsBackendUserByUid(): void
    {
        $record = $this->subject->findBackendUserByUid(100);
        self::assertSame('rootadmin', $record['username'] ?? null);
    }

    /**
     * be_users.username is unique by TCA evaluation only, the database
     * does not prevent a second record with the same username.
     */
    #[Test]
    public function refusesAmbiguousBackendUsername(): void
    {
        self::assertNull($this->subject->findBackendUserByUsername('twineditor'));
        self::assertSame('twineditor', $this->subject->findBackendUserByUid(106)['username'] ?? null);
        self::assertSame('twineditor', $this->subject->findBackendUserByUid(107)['username'] ?? null);
    }

    #[Test]
    public function doesNotFindDisabledOrDeletedBackendUser(): void
    {
        self::assertNull($this->subject->findBackendUserByUsername('disablededitor'));
        self::assertNull($this->subject->findBackendUserByUsername('deletededitor'));
        self::assertNull($this->subject->findBackendUserByUid(101));
        self::assertNull($this->subject->findBackendUserByUid(102));
    }

    #[Test]
    public function doesNotFindBackendUserOutsideRootLevel(): void
    {
        self::assertNull($this->subject->findBackendUserByUsername('strayeditor'));
        self::assertNull($this->subject->findBackendUserByUid(103));
    }

    #[Test]
    public function doesNotFindBackendUserBeforeStarttimeOrAfterEndtime(): void
    {
        self::assertNull($this->subject->findBackendUserByUsername('futureeditor'));
        self::assertNull($this->subject->findBackendUserByUsername('expirededitor'));
        self::assertNull($this->subject->findBackendUserByUid(104));
        self::assertNull($this->subject->findBackendUserByUid(105));
    }

    #[Test]
    public function doesNotFindFrontendUserBeforeStarttimeOrAfterEndtime(): void
    {
        self::assertNull($this->subject->findFrontendUserByUsername('futuremember', null));
        self::assertNull($this->subject->findFrontendUserByUsername('expiredmember', null));
        self::assertNull($this->subject->findFrontendUserByUid(204));
        self::assertNull($this->subject->findFrontendUserByUid(205));
    }

    #[Test]
    public function findsFrontendUserByUsernameWithoutStorageRestriction(): void
    {
        $record = $this->subject->findFrontendUserByUsername('othermember', null);
        self::assertSame(201, $record['uid'] ?? null);
    }

    /**
     * "member" exists on two storage pages, an unrestricted lookup must not
     * pick one of them.
     */
    #[Test]
    public function refusesAmbiguousFrontendUsernameWithoutStorageRestriction(): void
    {
        self::assertNull($this->subject->findFrontendUserByUsername('member', null));
    }

    #[Test]
    public function refusesAmbiguousFrontendUsernameWithinStorageRestriction(): void
    {
        self::assertNull($this->subject->findFrontendUserByUsername('member', [10, 11]));
    }

    #[Test]
    public function storagePageRestrictionResolvesAmbiguousFrontendUsername(): void
    {
        self::assertSame(200, $this->subject->findFrontendUserByUsername('member', [10])['uid'] ?? null);
        self::assertSame(203, $this->subject->findFrontendUserByUsername('member', [11])['uid'] ?? null);
    }

    #[Test]
    public function respectsStoragePageRestrictionForFrontendUsers(): void
    {
        self::assertNull($this->subject->findFrontendUserByUsername('othermember', [10]));
    }

    #[Test]
    public function findsFrontendUserByUid(): void
    {
        $record = $this->subject->findFrontendUserByUid(203);
        self::assertSame('member', $record['username'] ?? null);
    }

    #[Test]
    public function doesNotFindFrontendUserForEmptyStoragePageList(): void
    {
        self::assertNull($this->subject->findFrontendUserByUsername('othermember', []));
    }

    #[Test]
    public function doesNotFindDisabledFrontendUser(): void
    {
        self::assertNull($this->subject->findFrontendUserByUsername('disabledmember', null));
        self::assertNull($this->subject->findFrontendUserByUid(202));
    }

    #[Test]
    public function returnsNullForEmptyUsernameAndNonPositiveUid(): void
    {
        self::assertNull($this->subject->findBackendUserByUsername(''));
        self::assertNull($this->subject->findBackendUserByUid(0));
        self::assertNull($this->subject->findFrontendUserByUsername('', null));
        self::assertNull($this->subject->findFrontendUserByUid(-1));
    }
}
