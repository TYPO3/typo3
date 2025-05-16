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
use TYPO3\CMS\Extbase\Persistence\Exception\UnmanagedObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class CleanStateTrackerMmRelationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    private ReflectionService $reflectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reflectionService = $this->get(ReflectionService::class);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerMmTestImport.csv');
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    /**
     * An entity that travelled through serialize() and unserialize() is not managed by the persistence layer.
     * Updating it is rejected and neither the relation rows nor the record are written.
     */
    #[Test]
    public function updatingUnserializedEntityThrowsAndWritesNothing(): void
    {
        // Given: a post with three tags that is serialized, with the persistence state cleared like in a new request
        $postRepository = $this->get(PostRepository::class);
        $persistenceManager = $this->get(PersistenceManager::class);
        $post = $postRepository->findByUid(1);
        $this->reflectionService->getRawEntityPropertyValue($post, 'tags')->count();
        $serialized = serialize($post);
        $persistenceManager->clearState();
        $restored = unserialize($serialized, ['allowed_classes' => [Post::class, Tag::class, ObjectStorage::class, \DateTime::class]]);
        self::assertInstanceOf(Post::class, $restored);

        // When: the third tag is removed from the restored post, which is then updated
        $restored->setTitle('Changed title');
        $restored->removeTag($restored->getTags()->toArray()[2]);
        $exception = null;
        try {
            $postRepository->update($restored);
        } catch (UnmanagedObjectException $exception) {
        }
        $persistenceManager->persistAll();

        // Then: the update was rejected and nothing was written
        self::assertInstanceOf(UnmanagedObjectException::class, $exception);
        self::assertSame(
            'The object of type "' . Post::class . '" with uid 1 is not managed by the persistence layer, for example because it was unserialized or cloned. Fetch it from the repository (or let the property mapper convert its identity) and apply the changes before adding or updating it.',
            $exception->getMessage()
        );
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerMmTestResultUnchanged.csv');
    }

    /**
     * An entity that is still held after the persistence state was cleared is still managed, as the originals
     * live in the tracker and not in the persistence state. Removing one child from an MM relation removes exactly
     * that relation row and adds no duplicates.
     */
    #[Test]
    public function removingChildFromMmRelationOfEntityHeldAfterClearStateRemovesOnlyThatRelationRow(): void
    {
        // Given: a loaded post with three tags, with the persistence state cleared afterwards
        $postRepository = $this->get(PostRepository::class);
        $persistenceManager = $this->get(PersistenceManager::class);
        $session = $this->get(Session::class);
        $post = $postRepository->findByUid(1);
        $this->reflectionService->getRawEntityPropertyValue($post, 'tags')->count();
        $identifier = $session->getIdentifierByObject($post);
        $persistenceManager->clearState();
        $session->registerObject($post, $identifier);

        // When: the third tag is removed from the held post, which is then updated
        $post->removeTag($post->getTags()->toArray()[2]);
        $postRepository->update($post);
        $persistenceManager->persistAll();

        // Then: only the relation row of the third tag is gone
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerMmTestResultChildRemoved.csv');
    }

    /**
     * A clone of a loaded entity is not managed by the persistence layer. Updating it is rejected and nothing is
     * written, in particular no duplicate rows of the MM relation.
     */
    #[Test]
    public function updatingCloneOfEntityWithMmRelationThrowsAndWritesNothing(): void
    {
        // Given: a loaded post with three tags and a clone of it that is known to the persistence session
        $postRepository = $this->get(PostRepository::class);
        $session = $this->get(Session::class);
        $post = $postRepository->findByUid(1);
        $this->reflectionService->getRawEntityPropertyValue($post, 'tags')->count();
        $clone = clone $post;
        $session->registerObject($clone, $session->getIdentifierByObject($post));

        // When: a scalar property of the clone is changed and the clone is updated
        $clone->setTitle('Changed title');
        $exception = null;
        try {
            $postRepository->update($clone);
        } catch (UnmanagedObjectException $exception) {
        }
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the update was rejected and nothing was written
        self::assertInstanceOf(UnmanagedObjectException::class, $exception);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerMmTestResultUnchanged.csv');
    }
}
