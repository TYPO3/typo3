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

namespace TYPO3\CMS\FrontendLogin\Tests\Functional\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FrontendUserRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['felogin'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/fe_users.csv');
    }

    /**
     * @test
     */
    public function findUserByUsernameOrEmailOnPages(): void
    {
        $subject = new FrontendUserRepository(new Context(), new ConnectionPool());
        self::assertNull($subject->findUserByUsernameOrEmailOnPages(''));
        self::assertNull($subject->findUserByUsernameOrEmailOnPages('non-existent-email-or-username'));
        self::assertNull($subject->findUserByUsernameOrEmailOnPages('user-with-username-without-email'));
        self::assertNull($subject->findUserByUsernameOrEmailOnPages('foobar', [99]));
        $userByUsername = $subject->findUserByUsernameOrEmailOnPages('foobar');
        self::assertSame(1, $userByUsername['uid'] ?? 0);
        $userByEmail = $subject->findUserByUsernameOrEmailOnPages('foo@bar.baz');
        self::assertSame(1, $userByEmail['uid'] ?? 0);
    }

    /**
     * @test
     */
    public function existsUserWithHash(): void
    {
        $subject = new FrontendUserRepository(new Context(), new ConnectionPool());
        self::assertFalse($subject->existsUserWithHash('non-existent-hash'));
        self::assertTrue($subject->existsUserWithHash('cf8edd6fa435b4a9fcbb953f81bd84f2'));
    }

    /**
     * @test
     */
    public function findOneByForgotPasswordHash(): void
    {
        $subject = new FrontendUserRepository(new Context(), new ConnectionPool());
        self::assertNull($subject->findOneByForgotPasswordHash(''));
        self::assertNull($subject->findOneByForgotPasswordHash('non-existent-hash'));
        self::assertIsArray($subject->findOneByForgotPasswordHash('cf8edd6fa435b4a9fcbb953f81bd84f2'));
    }

    /**
     * @test
     */
    public function findRedirectIdPageByUserId(): void
    {
        $subject = new FrontendUserRepository(new Context(), new ConnectionPool());
        self::assertNull($subject->findRedirectIdPageByUserId(99));
        self::assertSame(10, $subject->findRedirectIdPageByUserId(1));
    }

    /**
     * @test
     */
    public function updateForgotHashForUserByUid(): void
    {
        $uid = 1;
        $newPasswordHash = 'new-hash';
        $subject = new FrontendUserRepository(new Context(), new ConnectionPool());
        $subject->updateForgotHashForUserByUid($uid, $newPasswordHash);
        $queryBuilder = $this->getConnectionPool()->getConnectionForTable('fe_users')->createQueryBuilder();
        $result = $queryBuilder
            ->select('felogin_forgotHash')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
            ->executeQuery()
            ->fetchOne();
        self::assertSame($newPasswordHash, $result);
    }

    /**
     * @test
     */
    public function updatePasswordAndInvalidateHash(): void
    {
        $subject = new FrontendUserRepository(new Context(), new ConnectionPool());
        $subject->updatePasswordAndInvalidateHash('cf8edd6fa435b4a9fcbb953f81bd84f2', 'new-password');
        $queryBuilder = $this->getConnectionPool()->getConnectionForTable('fe_users')->createQueryBuilder();
        $user = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1)))
            ->executeQuery()
            ->fetchAssociative();
        self::assertSame('new-password', $user['password']);
        self::assertSame('', $user['felogin_forgotHash']);
    }
}
