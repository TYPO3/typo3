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
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\FrontendLogin\Service\UserService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FrontendUserRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['felogin'];
    protected FrontendUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TSFE'] = $this->createMock(TypoScriptFrontendController::class);
        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();

        $this->repository = new FrontendUserRepository(new UserService(), new Context(), new ConnectionPool());

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/fe_users.csv');
    }

    /**
     * @test
     */
    public function getTable(): void
    {
        self::assertSame('fe_users', $this->repository->getTable());
    }

    /**
     * @test
     */
    public function findUserByUsernameOrEmailOnPages(): void
    {
        self::assertNull($this->repository->findUserByUsernameOrEmailOnPages(''));
        self::assertNull($this->repository->findUserByUsernameOrEmailOnPages('non-existent-email-or-username'));
        self::assertNull($this->repository->findUserByUsernameOrEmailOnPages('user-with-username-without-email'));
        self::assertNull($this->repository->findUserByUsernameOrEmailOnPages('foobar', [99]));

        $userByUsername = $this->repository->findUserByUsernameOrEmailOnPages('foobar');
        self::assertSame(1, $userByUsername['uid'] ?? 0);

        $userByEmail = $this->repository->findUserByUsernameOrEmailOnPages('foo@bar.baz');
        self::assertSame(1, $userByEmail['uid'] ?? 0);
    }

    /**
     * @test
     */
    public function existsUserWithHash(): void
    {
        self::assertFalse($this->repository->existsUserWithHash('non-existent-hash'));
        self::assertTrue($this->repository->existsUserWithHash('cf8edd6fa435b4a9fcbb953f81bd84f2'));
    }

    /**
     * @test
     */
    public function findOneByForgotPasswordHash(): void
    {
        self::assertNull($this->repository->findOneByForgotPasswordHash(''));
        self::assertNull($this->repository->findOneByForgotPasswordHash('non-existent-hash'));
        self::assertIsArray($this->repository->findOneByForgotPasswordHash('cf8edd6fa435b4a9fcbb953f81bd84f2'));
    }

    /**
     * @test
     */
    public function findRedirectIdPageByUserId(): void
    {
        self::assertNull($this->repository->findRedirectIdPageByUserId(99));
        self::assertSame(10, $this->repository->findRedirectIdPageByUserId(1));
    }

    /**
     * @test
     */
    public function updateForgotHashForUserByUid(): void
    {
        $uid = 1;
        $newPasswordHash = 'new-hash';

        $this->repository->updateForgotHashForUserByUid($uid, $newPasswordHash);

        $queryBuilder = $this->getConnectionPool()
            ->getConnectionForTable('fe_users')
            ->createQueryBuilder();

        $query = $queryBuilder
            ->select('felogin_forgotHash')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($uid)
            ))
        ;

        self::assertSame($newPasswordHash, $query->executeQuery()->fetchOne());
    }

    /**
     * @test
     */
    public function updatePasswordAndInvalidateHash(): void
    {
        $this->repository->updatePasswordAndInvalidateHash('cf8edd6fa435b4a9fcbb953f81bd84f2', 'new-password');

        $queryBuilder = $this->getConnectionPool()
            ->getConnectionForTable('fe_users')
            ->createQueryBuilder();

        $query = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter(1)
            ))
        ;

        $user = $query->executeQuery()->fetchAssociative();

        self::assertSame('new-password', $user['password']);
        self::assertSame('', $user['felogin_forgotHash']);
    }
}
