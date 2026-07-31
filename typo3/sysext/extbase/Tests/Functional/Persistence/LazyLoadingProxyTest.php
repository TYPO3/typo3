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
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Post;

final class LazyLoadingProxyTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['extbase'];
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LazyLoadingProxyTestImport.csv');
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function lazyRelationIsANativeLazyProxyOfTheTargetEntityClass(): void
    {
        $blog = new Blog();
        $blog->_setProperty('administrator', $this->get(DataMapper::class)->fetchRelated($blog, 'administrator', 1));

        $administrator = $blog->getAdministrator();
        $reflection = new \ReflectionClass(Administrator::class);
        self::assertInstanceOf(Administrator::class, $administrator);
        self::assertTrue($reflection->isUninitializedLazyObject($administrator));

        // The uid of the related record is available without initializing the proxy
        self::assertSame(1, $administrator->getUid());
        self::assertTrue($reflection->isUninitializedLazyObject($administrator));

        // Accessing any other property initializes the proxy
        self::assertSame('Blog Admin', $administrator->getUsername());
        self::assertFalse($reflection->isUninitializedLazyObject($administrator));
    }

    #[Test]
    public function foreignSideLazyRelationDoesNotExposeRelationCountAsUid(): void
    {
        $blog = new Blog();
        $blog->_setProperty('uid', 10);
        $blog->_setProperty('featuredPost', $this->get(DataMapper::class)->fetchRelated($blog, 'featuredPost', 1));

        $featuredPost = $blog->getFeaturedPost();
        $reflection = new \ReflectionClass(Post::class);
        self::assertInstanceOf(Post::class, $featuredPost);
        self::assertTrue($reflection->isUninitializedLazyObject($featuredPost));

        self::assertSame(42, $featuredPost->getUid(), 'Assert that the uid of the related record is available');
    }

    #[Test]
    public function initializedProxyReplacesItselfInParentObject(): void
    {
        $blog = new Blog();
        $blog->_setProperty('administrator', $this->get(DataMapper::class)->fetchRelated($blog, 'administrator', 1));

        $proxy = $blog->getAdministrator();
        $proxy->getUsername();

        $administrator = $blog->getAdministrator();
        self::assertInstanceOf(Administrator::class, $administrator);
        self::assertFalse(new \ReflectionClass(Administrator::class)->isUninitializedLazyObject($administrator));
        self::assertFalse($blog->_isDirty());
    }

    #[Test]
    public function serializeAndUnserialize(): void
    {
        $blog = new Blog();
        $blog->_setProperty('administrator', $this->get(DataMapper::class)->fetchRelated($blog, 'administrator', 1));

        // Serializing an uninitialized proxy initializes it and serializes the actual entity state
        $serialized = serialize($blog->getAdministrator());

        self::assertFalse(str_contains($serialized, 'dataMapper'), 'Assert that serialized object string does not contain dataMapper');
        self::assertFalse(str_contains($serialized, 'parentObject'), 'Assert that serialized object string does not contain parentObject');

        /* @phpstan-ignore unserialize.allowedClasses.insecure (Serialization within testing context does no harm) */
        $administrator = unserialize($serialized, ['allowed_classes' => true]);
        self::assertInstanceOf(Administrator::class, $administrator, 'Assert that $administrator is an instance of Administrator');

        self::assertSame('Blog Admin', $administrator->getUsername());
    }

    #[Test]
    public function danglingLazyRelationIsResolvedToNullInParentObject(): void
    {
        $blog = new Blog();
        $blog->_setProperty('administrator', $this->get(DataMapper::class)->fetchRelated($blog, 'administrator', 88));

        $administrator = $blog->getAdministrator();
        self::assertInstanceOf(Administrator::class, $administrator);

        // Initializing the proxy of a non-resolvable record exposes an empty instance ...
        self::assertSame('', $administrator->getUsername());
        // ... and resets the parent property to null
        self::assertNull($blog->getAdministrator());
    }
}
