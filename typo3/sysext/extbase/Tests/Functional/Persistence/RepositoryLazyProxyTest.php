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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Repository\AdministratorRepository;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

/**
 * Passing a lazy relation value to the repository of its own type: update() and remove().
 * https://forge.typo3.org/issues/111139
 */
final class RepositoryLazyProxyTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/IsDirtyTestImport.csv');
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    /**
     * A lazy proxy that is still uninitialized is accepted by update() of its own repository;
     * nothing changed, so the record stays as it is.
     */
    #[Test]
    public function updatingUninitializedLazyProxyKeepsRecordUnchanged(): void
    {
        // Given: the uninitialized lazy administrator of blog 3
        $administrator = $this->get(BlogRepository::class)->findByUid(3)->getAdministrator();
        self::assertTrue(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($administrator));

        // When: it is passed to update() of its own repository
        $this->get(AdministratorRepository::class)->update($administrator);

        // Then: no exception is thrown and the record is left untouched
        $this->get(PersistenceManager::class)->persistAll();
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('fe_users');
        self::assertSame(
            'administratora',
            $connection->select(['username'], 'fe_users', ['uid' => 3])->fetchOne()
        );
    }

    /**
     * Changes on an initialized lazy proxy are written when it is passed to update() of its own
     * repository.
     */
    #[Test]
    public function updatingInitializedLazyProxyWritesChangedProperty(): void
    {
        // Given: the lazy administrator of blog 3, initialized by reading a property
        $administrator = $this->get(BlogRepository::class)->findByUid(3)->getAdministrator();
        $administrator->getUsername();
        self::assertFalse(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($administrator));

        // When: it is changed and passed to update() of its own repository, then persisted
        $administrator->setUsername('administrator-changed');
        $this->get(AdministratorRepository::class)->update($administrator);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: no exception is thrown and the changed username is written to fe_users
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('fe_users');
        self::assertSame(
            'administrator-changed',
            $connection->select(['username'], 'fe_users', ['uid' => 3])->fetchOne()
        );
    }

    /**
     * remove() of a lazy proxy deletes the record it stands for.
     */
    #[Test]
    public function removingLazyProxyDeletesTheRecord(): void
    {
        // Given: the uninitialized lazy administrator of blog 3
        $administrator = $this->get(BlogRepository::class)->findByUid(3)->getAdministrator();
        self::assertTrue(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($administrator));

        // When: it is removed through its repository and persisted
        $this->get(AdministratorRepository::class)->remove($administrator);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: fe_users 3 is deleted
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
        $queryBuilder->getRestrictions()->removeAll();
        self::assertSame(
            1,
            (int)$queryBuilder
                ->select('deleted')
                ->from('fe_users')
                ->where($queryBuilder->expr()->eq('uid', 3))
                ->executeQuery()
                ->fetchOne()
        );
    }
}
