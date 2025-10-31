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

namespace TYPO3\CMS\Beuser\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendUserRepositoryTest extends FunctionalTestCase
{
    protected BackendUserRepository $subject;

    protected array $testExtensionsToLoad = [
        'beuser',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');

        $this->subject = $this->get(BackendUserRepository::class);
    }

    #[Test]
    public function findDemandedInitiallyWillFindActiveAndHiddenBackendUsers(): void
    {
        $demand = new Demand();

        $queryResult = $this->subject->findDemanded($demand);

        self::assertCount(5, $queryResult);
    }

    #[Test]
    public function findDemandedWithAdminFilterWillOnlyFindAdmins(): void
    {
        $demand = new Demand();
        $demand->setUserType(Demand::USERTYPE_ADMINONLY);

        $queryResult = $this->subject->findDemanded($demand);

        self::assertCount(1, $queryResult);
    }

    #[Test]
    public function findDemandedWithUserFilterWillOnlyFindUsers(): void
    {
        $demand = new Demand();
        $demand->setUserType(Demand::USERTYPE_USERONLY);

        $queryResult = $this->subject->findDemanded($demand);

        self::assertCount(4, $queryResult);
    }

    #[Test]
    public function findDemandedWithActiveFilterWillOnlyFindActiveUsers(): void
    {
        $demand = new Demand();
        $demand->setStatus(Demand::STATUS_ACTIVE);

        $queryResult = $this->subject->findDemanded($demand);

        self::assertCount(4, $queryResult);
    }

    #[Test]
    public function findDemandedWithInactiveFilterWillOnlyFindInactiveUsers(): void
    {
        $demand = new Demand();
        $demand->setStatus(Demand::STATUS_INACTIVE);

        $queryResult = $this->subject->findDemanded($demand);

        self::assertCount(1, $queryResult);
    }

    #[Test]
    public function findDemandedWithSomeLoginFilterWillFindUsersLoggedInOverTime(): void
    {
        $demand = new Demand();
        $demand->setLogins(Demand::LOGIN_SOME);

        $queryResult = $this->subject->findDemanded($demand);

        self::assertCount(3, $queryResult);
    }

    #[Test]
    public function findDemandedWithNeverLoginFilterWillFindUsersNeverLoggedIn(): void
    {
        $demand = new Demand();
        $demand->setLogins(Demand::LOGIN_NONE);

        $queryResult = $this->subject->findDemanded($demand);

        self::assertCount(2, $queryResult);
    }

    #[Test]
    public function findDemandedWithCurrentLoginFilterWillFindNoCurrentLoggedInUsers(): void
    {
        $demand = new Demand();
        $demand->setLogins(Demand::LOGIN_CURRENT);

        $queryResult = $this->subject->findDemanded($demand);

        self::assertCount(0, $queryResult);
    }

    #[Test]
    public function findDemandedWithCurrentLoginFilterWillFindCurrentLoggedInUsers(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('be_users');
        $connection->insert(
            'be_users',
            [
                'pid' => 0,
                'tstamp' => 1366642540,
                'usergroup' => 3,
                'username' => 'currently-logged-in-user',
                'password' => '$1$tCrlLajZ$C0sikFQQ3SWaFAZ1Me0Z/1',
                'admin' => 0,
                'disable' => 0,
                'deleted' => 0,
                'lastlogin' => time() - 3600, // must be within NOW() and BE/sessionTimeout
                'workspace_id' => 0,
            ]
        );

        $demand = new Demand();
        $demand->setLogins(Demand::LOGIN_CURRENT);

        $queryResult = $this->subject->findDemanded($demand);

        self::assertCount(1, $queryResult);
    }
}
