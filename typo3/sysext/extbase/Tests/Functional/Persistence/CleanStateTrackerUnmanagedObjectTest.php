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
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class CleanStateTrackerUnmanagedObjectTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerUnmanagedObjectTestImport.csv');
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    /**
     * A clone of a loaded entity still carries the uid of the record, but the persistence layer does not know it.
     * Adding it would write the record again, so it is rejected and nothing is written.
     */
    #[Test]
    public function addingCloneOfLoadedEntityThrowsAndWritesNothing(): void
    {
        // Given: a loaded tag and a clone of it with a changed name
        $persistenceManager = $this->get(PersistenceManager::class);
        $tag = $persistenceManager->getObjectByIdentifier('1', Tag::class);
        $clone = clone $tag;
        $clone->_setProperty('name', 'Changed name');

        // When: the clone is added
        $exception = null;
        try {
            $persistenceManager->add($clone);
            $persistenceManager->persistAll();
        } catch (UnmanagedObjectException $exception) {
        }

        // Then: the add was rejected and nothing was written
        self::assertInstanceOf(UnmanagedObjectException::class, $exception);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerUnmanagedObjectTestResultUnchanged.csv');
    }

    /**
     * An unserialized entity that still carries the uid of a record is not managed either.
     */
    #[Test]
    public function addingUnserializedEntityThrowsAndWritesNothing(): void
    {
        // Given: a serialized tag with a changed name, restored after the persistence state was cleared
        $persistenceManager = $this->get(PersistenceManager::class);
        $tag = $persistenceManager->getObjectByIdentifier('1', Tag::class);
        $tag->_setProperty('name', 'Changed name');
        $serialized = serialize($tag);
        $persistenceManager->clearState();
        $restored = unserialize($serialized, ['allowed_classes' => [Tag::class]]);
        self::assertInstanceOf(Tag::class, $restored);

        // When: the restored tag is added
        $exception = null;
        try {
            $persistenceManager->add($restored);
            $persistenceManager->persistAll();
        } catch (UnmanagedObjectException $exception) {
        }

        // Then: the add was rejected and nothing was written
        self::assertInstanceOf(UnmanagedObjectException::class, $exception);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerUnmanagedObjectTestResultUnchanged.csv');
    }

    /**
     * A clone of a loaded tag that is attached to a managed post is rejected when the post is persisted,
     * before any relation row or the changed title of the post is written.
     */
    #[Test]
    public function cloneAttachedToManagedMmRelationIsRejectedBeforeAnythingIsWritten(): void
    {
        // Given: a loaded post with three tags, whose third tag is replaced by a clone of it
        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $tags = $post->getTags()->toArray();
        $post->setTitle('Changed title');
        $post->removeTag($tags[2]);
        $post->addTag(clone $tags[2]);

        // When: the post is updated and persisted
        $exception = null;
        try {
            $postRepository->update($post);
            $this->get(PersistenceManager::class)->persistAll();
        } catch (UnmanagedObjectException $exception) {
        }

        // Then: the clone was rejected and nothing was written
        self::assertInstanceOf(UnmanagedObjectException::class, $exception);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerUnmanagedObjectTestResultUnchanged.csv');
    }

    /**
     * A clone of a loaded blog that is set as the blog of a managed post is rejected as well, its changed title
     * is not written.
     */
    #[Test]
    public function cloneSetOnManagedToOneRelationIsRejectedBeforeAnythingIsWritten(): void
    {
        // Given: a loaded post whose blog is replaced by a clone of a loaded blog with a changed title
        $postRepository = $this->get(PostRepository::class);
        $post = $postRepository->findByUid(1);
        $blog = $this->get(PersistenceManager::class)->getObjectByIdentifier('1', Blog::class);
        $clone = clone $blog;
        $clone->setTitle('Changed blog title');
        $post->setTitle('Changed title');
        $post->setBlog($clone);

        // When: the post is updated and persisted
        $exception = null;
        try {
            $postRepository->update($post);
            $this->get(PersistenceManager::class)->persistAll();
        } catch (UnmanagedObjectException $exception) {
        }

        // Then: the clone was rejected and nothing was written
        self::assertInstanceOf(UnmanagedObjectException::class, $exception);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerUnmanagedObjectTestResultUnchanged.csv');
    }
}
