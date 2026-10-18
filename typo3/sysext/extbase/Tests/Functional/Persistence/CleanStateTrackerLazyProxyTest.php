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
use TYPO3\CMS\Extbase\Persistence\Generic\CleanStateTracker;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Repository\AdministratorRepository;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

final class CleanStateTrackerLazyProxyTest extends FunctionalTestCase
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
     * An uninitialized lazy proxy can only have been created by the DataMapper, so the tracker treats it as managed
     * without initializing it.
     */
    #[Test]
    public function uninitializedLazyProxyIsContainedInTracker(): void
    {
        // Given: an uninitialized lazy proxy of the administrator of a loaded blog
        $blog = $this->get(BlogRepository::class)->findByUid(3);
        $proxy = $blog->getAdministrator();
        self::assertTrue(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($proxy)); // precondition

        // When: the tracker is asked whether it contains the proxy
        $contains = $this->get(CleanStateTracker::class)->contains($proxy);

        // Then: it does, and the proxy is still not initialized
        self::assertTrue($contains);
        self::assertTrue(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($proxy));
    }

    /**
     * The session resolves a lazy proxy to the real instance it stands for, so updating an uninitialized proxy
     * directly is accepted. The tracker must not reject it as an unmanaged (unserialized or cloned) object.
     */
    #[Test]
    public function updatingUninitializedLazyProxyIsNotRejectedAsUnmanaged(): void
    {
        // Given: an uninitialized lazy proxy of the administrator of a loaded blog
        $blog = $this->get(BlogRepository::class)->findByUid(3);
        $proxy = $blog->getAdministrator();
        self::assertTrue(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($proxy)); // precondition

        // When: the proxy is passed to update() and persisted
        $this->get(AdministratorRepository::class)->update($proxy);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: no exception is thrown and the record is left untouched
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('fe_users');
        self::assertSame(
            'administratora',
            $connection->select(['username'], 'fe_users', ['uid' => 3])->fetchOne()
        );
    }
}
