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
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Repository\AdministratorRepository;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

final class IsDirtyTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/IsDirtyTestImport.csv');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function objectFetchedFromDbIsNotDirty(): void
    {
        $blog = $this->get(BlogRepository::class)->findByUid(3);
        self::assertFalse($blog->_isDirty());
    }

    #[Test]
    public function lazyLoadingProxyReplacedByRealInstanceIsNotDirty(): void
    {
        $blog = $this->get(BlogRepository::class)->findByUid(3);
        self::assertInstanceOf(LazyLoadingProxy::class, $blog->getAdministrator()); // precondition
        $admin = $this->get(AdministratorRepository::class)->findByUid(3);
        self::assertInstanceOf(Administrator::class, $admin); // precondition
        $blog->setAdministrator($admin);
        self::assertFalse($blog->_isDirty());
    }

    #[Test]
    public function lazyLoadingProxyReplacedByWrongInstanceIsDirty(): void
    {
        $blog = $this->get(BlogRepository::class)->findByUid(3);
        self::assertInstanceOf(LazyLoadingProxy::class, $blog->getAdministrator()); //precondition
        $blog->setAdministrator(new Administrator());
        self::assertTrue($blog->_isDirty());
    }

    #[Test]
    public function realInstanceReplacedByLazyLoadingProxyIsNotDirty(): void
    {
        $blog = $this->get(BlogRepository::class)->findByUid(3);
        $lazyLoadingProxy = $blog->getAdministrator();
        self::assertInstanceOf(LazyLoadingProxy::class, $lazyLoadingProxy); //precondition
        $admin = $this->get(AdministratorRepository::class)->findByUid(3);
        self::assertInstanceOf(Administrator::class, $admin); // precondition
        $blog->setAdministrator($admin);
        $blog->_memorizeCleanState();
        $blog->_setProperty('administrator', $lazyLoadingProxy);
        self::assertFalse($blog->_isDirty());
    }

    #[Test]
    public function lazyLoadingProxyByWrongLazyLoadingProxyIsDirtyAndUpdated(): void
    {
        $blogRepository = $this->get(BlogRepository::class);
        $blogOne = $blogRepository->findByUid(3);
        self::assertInstanceOf(LazyLoadingProxy::class, $blogOne->getAdministrator()); //precondition
        $blogTwo = $blogRepository->findByUid(2);
        self::assertInstanceOf(LazyLoadingProxy::class, $blogTwo->getAdministrator()); //precondition
        $blogOne->_setProperty('administrator', $blogTwo->getAdministrator());
        self::assertTrue($blogOne->_isDirty());
        $blogRepository->update($blogOne);
        $updatedBlogOne = $blogRepository->findByUid(3);
        self::assertSame($updatedBlogOne->getAdministrator()->getUid(), $blogTwo->getAdministrator()->getUid());
    }
}
