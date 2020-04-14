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
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\FrontendLogin\Service\UserService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FrontendUserRepositoryTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'felogin'];

    /**
     * @var FrontendUserRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $context = new Context();
        $GLOBALS['TSFE'] = static::getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $GLOBALS['TSFE']->fe_user = new FrontendUserAuthentication();

        $this->repository = new FrontendUserRepository(
            new UserService(),
            $context
        );

        $this->importDataSet(__DIR__ . '/../../Fixtures/fe_users.xml');
    }

    /**
     * @test
     */
    public function getTable()
    {
        self::assertSame('fe_users', $this->repository->getTable());
    }

    /**
     * @test
     */
    public function findEmailByUsernameOrEmailOnPages()
    {
        self::assertNull($this->repository->findEmailByUsernameOrEmailOnPages(''));
        self::assertNull($this->repository->findEmailByUsernameOrEmailOnPages('non-existent-email-or-username'));
        self::assertNull($this->repository->findEmailByUsernameOrEmailOnPages('user-with-username-without-email'));
        self::assertNull($this->repository->findEmailByUsernameOrEmailOnPages('foobar', [99]));

        self::assertSame('foo@bar.baz', $this->repository->findEmailByUsernameOrEmailOnPages('foobar'));
        self::assertSame('foo@bar.baz', $this->repository->findEmailByUsernameOrEmailOnPages('foo@bar.baz'));
    }

    /**
     * @test
     */
    public function existsUserWithHash()
    {
        self::assertFalse($this->repository->existsUserWithHash('non-existent-hash'));
        self::assertTrue($this->repository->existsUserWithHash('cf8edd6fa435b4a9fcbb953f81bd84f2'));
    }

    /**
     * @test
     * @dataProvider fetchUserInformationByEmailDataProvider
     * @param string $emailAddress
     * @param array $expected
     */
    public function fetchUserInformationByEmail(string $emailAddress, array $expected)
    {
        // strval() is used since not all of the DBMS return an integer for the "uid" field
        self::assertSame($expected, array_map('strval', $this->repository->fetchUserInformationByEmail($emailAddress)));
    }

    public function fetchUserInformationByEmailDataProvider(): array
    {
        return [
            'foo@bar.baz' => [
                'foo@bar.baz',
                [
                    'uid' => '1',
                    'username' => 'foobar',
                    'email' => 'foo@bar.baz',
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                ]
            ],
            '' => [
                '',
                [
                    'uid' => '2',
                    'username' => 'user-with-username-without-email',
                    'email' => '',
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                ]
            ],
        ];
    }

    /**
     * @test
     */
    public function findOneByForgotPasswordHash()
    {
        self::assertNull($this->repository->findOneByForgotPasswordHash(''));
        self::assertNull($this->repository->findOneByForgotPasswordHash('non-existent-hash'));
        self::assertIsArray($this->repository->findOneByForgotPasswordHash('cf8edd6fa435b4a9fcbb953f81bd84f2'));
    }

    /**
     * @test
     */
    public function findRedirectIdPageByUserId()
    {
        self::assertNull($this->repository->findRedirectIdPageByUserId(99));
        self::assertSame(10, $this->repository->findRedirectIdPageByUserId(1));
    }

    /**
     * @test
     */
    public function updateForgotHashForUserByEmail()
    {
        $email = 'foo@bar.baz';
        $newPasswordHash = 'new-hash';

        $this->repository->updateForgotHashForUserByEmail($email, $newPasswordHash);

        $queryBuilder = $this->getConnectionPool()
            ->getConnectionForTable('fe_users')
            ->createQueryBuilder();

        $query = $queryBuilder
            ->select('felogin_forgotHash')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq(
                'email',
                $queryBuilder->createNamedParameter($email)
            ))
        ;

        self::assertSame($newPasswordHash, $query->execute()->fetchColumn());
    }

    /**
     * @test
     */
    public function updatePasswordAndInvalidateHash()
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

        $user = $query->execute()->fetch();

        self::assertSame('new-password', $user['password']);
        self::assertSame('', $user['felogin_forgotHash']);
    }
}
