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
use TYPO3\CMS\Extbase\Persistence\Exception\UnmanagedObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\CleanStateTracker;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class CleanStateTrackerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerTestImport.csv');
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    /**
     * A freshly loaded entity has nothing to write. Changing one scalar property marks exactly that property
     * as dirty and leaves all others clean.
     */
    #[Test]
    public function changedScalarPropertyMakesExactlyThatPropertyDirty(): void
    {
        // Given: a blog loaded from the database
        $cleanStateTracker = $this->get(CleanStateTracker::class);
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        self::assertFalse($cleanStateTracker->isEntityDirty($blog));

        // When: the title is changed
        $blog->setTitle('Changed title');

        // Then: only the title is dirty
        self::assertTrue($cleanStateTracker->isEntityDirty($blog));
        self::assertTrue($cleanStateTracker->isEntityPropertyDirty($blog, 'title'));
        self::assertFalse($cleanStateTracker->isEntityPropertyDirty($blog, 'description'));
    }

    /**
     * Only the changed column is written, a value changed in the database behind the entity's back stays untouched.
     */
    #[Test]
    public function updateWritesOnlyTheChangedColumn(): void
    {
        // Given: a loaded blog whose description is changed in the database after loading
        $blogRepository = $this->get(BlogRepository::class);
        $blog = $blogRepository->findByUid(1);
        $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_blogexample_domain_model_blog')
            ->update('tx_blogexample_domain_model_blog', ['description' => 'Description changed in database'], ['uid' => 1]);

        // When: only the title is changed and persisted
        $blog->setTitle('Changed title');
        $blogRepository->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the description in the database is not overwritten
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerTestResultScalarChange.csv');
    }

    /**
     * A DateTime that is modified in place is the same object as before, but must still be detected as changed.
     */
    #[Test]
    public function dateTimeModifiedInPlaceIsDirty(): void
    {
        // Given: a post loaded from the database
        $cleanStateTracker = $this->get(CleanStateTracker::class);
        $post = $this->get(PostRepository::class)->findByUid(1);
        self::assertFalse($cleanStateTracker->isEntityPropertyDirty($post, 'date'));

        // When: the date is modified in place
        $post->getDate()->modify('+1 day');

        // Then: the date is dirty, the title is not
        self::assertTrue($cleanStateTracker->isEntityPropertyDirty($post, 'date'));
        self::assertFalse($cleanStateTracker->isEntityPropertyDirty($post, 'title'));
        self::assertTrue($cleanStateTracker->isEntityDirty($post));
    }

    /**
     * Attaching an object to a to-many relation makes the storage and the owning entity dirty.
     */
    #[Test]
    public function attachingToObjectStorageMakesStorageAndEntityDirty(): void
    {
        // Given: blog 2 with one post, and a post of blog 1
        $cleanStateTracker = $this->get(CleanStateTracker::class);
        $blog = $this->get(BlogRepository::class)->findByUid(2);
        $post = $this->get(PostRepository::class)->findByUid(1);
        self::assertFalse($cleanStateTracker->isEntityDirty($blog->getPosts()));
        self::assertFalse($cleanStateTracker->isEntityPropertyDirty($blog, 'posts'));

        // When: the post is attached to the blog
        $blog->addPost($post);

        // Then: storage and blog are dirty
        self::assertTrue($cleanStateTracker->isEntityDirty($blog->getPosts()));
        self::assertTrue($cleanStateTracker->isEntityPropertyDirty($blog, 'posts'));
        self::assertTrue($cleanStateTracker->isEntityDirty($blog));
    }

    /**
     * Detaching an object from a to-many relation makes the storage and the owning entity dirty.
     */
    #[Test]
    public function detachingFromObjectStorageMakesStorageAndEntityDirty(): void
    {
        // Given: blog 1 with two posts
        $cleanStateTracker = $this->get(CleanStateTracker::class);
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        $post = $this->get(PostRepository::class)->findByUid(1);
        self::assertCount(2, $blog->getPosts());
        self::assertFalse($cleanStateTracker->isEntityPropertyDirty($blog, 'posts'));

        // When: one post is detached from the blog
        $blog->removePost($post);

        // Then: storage and blog are dirty
        self::assertTrue($cleanStateTracker->isEntityDirty($blog->getPosts()));
        self::assertTrue($cleanStateTracker->isEntityPropertyDirty($blog, 'posts'));
    }

    /**
     * A clone is a new object that is not managed by the persistence layer. It has no original data and is never
     * dirty, changing it does not affect the entity it was cloned from.
     */
    #[Test]
    public function cloneOfLoadedEntityIsNotManagedAndNotDirty(): void
    {
        // Given: a loaded blog
        $cleanStateTracker = $this->get(CleanStateTracker::class);
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: the blog is cloned and only the clone is changed
        $clone = clone $blog;
        $clone->setTitle('Changed title');

        // Then: the clone has no originals and is not dirty, the loaded blog is still managed and not dirty
        self::assertTrue($cleanStateTracker->contains($blog));
        self::assertFalse($cleanStateTracker->contains($clone));
        self::assertSame([], $cleanStateTracker->getOriginalEntityData($clone));
        self::assertSame([], $cleanStateTracker->getEntityChangeSet($clone));
        self::assertFalse($cleanStateTracker->isEntityDirty($clone));
        self::assertFalse($cleanStateTracker->isEntityDirty($blog));
    }

    /**
     * An entity that travelled through serialize() and unserialize() (for example stored in a session) is not
     * managed by the persistence layer. It can be read, but updating it is rejected and nothing is written.
     */
    #[Test]
    public function updateOfUnserializedEntityThrowsAndWritesNothing(): void
    {
        // Given: a loaded blog that is serialized, with the persistence state cleared like in a new request
        $blogRepository = $this->get(BlogRepository::class);
        $persistenceManager = $this->get(PersistenceManager::class);
        $cleanStateTracker = $this->get(CleanStateTracker::class);
        $blog = $blogRepository->findByUid(1);
        $blog->getPosts()->count();
        $serialized = serialize($blog);
        $persistenceManager->clearState();
        $restored = unserialize($serialized, ['allowed_classes' => [Blog::class, Post::class, ObjectStorage::class, \DateTime::class]]);
        self::assertInstanceOf(Blog::class, $restored);

        // When: the restored blog is changed and updated
        $restored->setTitle('Changed after restore');
        $exception = null;
        try {
            $blogRepository->update($restored);
        } catch (UnmanagedObjectException $exception) {
        }
        $persistenceManager->persistAll();

        // Then: it can be read, has no originals, is not dirty, and nothing was written
        self::assertInstanceOf(UnmanagedObjectException::class, $exception);
        self::assertSame('Changed after restore', $restored->getTitle());
        self::assertFalse($cleanStateTracker->contains($restored));
        self::assertSame([], $cleanStateTracker->getOriginalEntityData($restored));
        self::assertSame([], $cleanStateTracker->getEntityChangeSet($restored));
        self::assertFalse($cleanStateTracker->isEntityDirty($restored));
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerTestResultUnchanged.csv');
    }

    /**
     * Migration for an entity that was stored in a session: keep the identity and the changed values instead of
     * the entity, load the entity by its uid, apply the change and update it.
     */
    #[Test]
    public function changeAppliedToReloadedEntityIsWritten(): void
    {
        // Given: the uid of a blog, with the persistence state cleared like in a new request
        $blogRepository = $this->get(BlogRepository::class);
        $persistenceManager = $this->get(PersistenceManager::class);
        $uid = $blogRepository->findByUid(1)->getUid();
        $persistenceManager->clearState();

        // When: the blog is fetched again by its uid, changed and updated
        $reloaded = $blogRepository->findByUid($uid);
        $reloaded->setTitle('Changed after restore');
        $blogRepository->update($reloaded);
        $persistenceManager->persistAll();

        // Then: the title is written
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerTestResultReloadedChange.csv');
    }

    /**
     * Migration for a multi-step form: the submitted values are stored together with the identity of the entity.
     * In the last step the property mapper converts them to the managed entity, which is then updated.
     */
    #[Test]
    public function entityConvertedByPropertyMapperFromStoredIdentityAndValuesIsWritten(): void
    {
        // Given: the values of a form, stored together with the identity of the blog, and a mapping configuration
        // that allows the modification of the blog like a controller argument does
        $formValues = ['__identity' => 1, 'title' => 'Title from form'];
        $configuration = new PropertyMappingConfiguration();
        $configuration->allowAllProperties();
        $configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);

        // When: the values are converted to a blog, which is updated
        $blog = $this->get(PropertyMapper::class)->convert($formValues, Blog::class, $configuration);
        self::assertInstanceOf(Blog::class, $blog);
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the title is written and nothing else changed
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CleanStateTrackerTestResultFormTitle.csv');
    }

    /**
     * An entity is managed once it was loaded or inserted by the persistence layer, an object that was built by
     * hand is not, even if it carries a uid.
     */
    #[Test]
    public function trackerContainsLoadedAndInsertedEntitiesOnly(): void
    {
        // Given: a loaded blog, a new blog and a blog built by hand with the uid of a record
        $tracker = $this->get(CleanStateTracker::class);
        $loaded = $this->get(BlogRepository::class)->findByUid(1);
        $new = new Blog();
        $new->setTitle('A new blog');
        $builtByHand = new Blog();
        $builtByHand->_setProperty('uid', 2);

        // When: the new blog is added and persisted
        $this->get(BlogRepository::class)->add($new);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the loaded and the inserted blog are managed, the blog built by hand is not
        self::assertTrue($tracker->contains($loaded));
        self::assertTrue($tracker->contains($new));
        self::assertFalse($tracker->contains($builtByHand));
    }

    /**
     * Extension pattern (femanager): ask per property and for the to-many storage whether the user changed anything.
     */
    #[Test]
    public function perPropertyAndStorageDirtyChecksAnswerWhetherTheUserChangedSomething(): void
    {
        // Given: a loaded blog with a changed title and an unchanged posts storage
        $cleanStateTracker = $this->get(CleanStateTracker::class);
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        $blog->setTitle('Changed title');
        $dirtyProperties = [];

        // When: every property is asked like femanager does
        foreach (['title', 'description', 'posts'] as $propertyName) {
            if ($cleanStateTracker->isEntityPropertyDirty($blog, $propertyName)) {
                $dirtyProperties[] = $propertyName;
            }
        }

        // Then: only the title is reported, the storage reports no user change
        self::assertSame('title', implode(',', $dirtyProperties));
        self::assertFalse($cleanStateTracker->isEntityDirty($blog->getPosts()));
    }

    /**
     * Extension pattern (sf_event_mgt): validate only objects that are new or changed.
     */
    #[Test]
    public function newOrDirtyCheckSelectsOnlyNewAndChangedObjects(): void
    {
        // Given: an unchanged loaded post, a changed loaded post and a new post
        $cleanStateTracker = $this->get(CleanStateTracker::class);
        $postRepository = $this->get(PostRepository::class);
        $unchanged = $postRepository->findByUid(1);
        $changed = $postRepository->findByUid(2);
        $changed->setTitle('Changed title');
        $new = new Post();

        // When: the check is applied like sf_event_mgt does
        $needsValidation = [];
        foreach (['unchanged' => $unchanged, 'changed' => $changed, 'new' => $new] as $name => $post) {
            if ($post->_isNew() || $cleanStateTracker->isEntityDirty($post)) {
                $needsValidation[] = $name;
            }
        }

        // Then: only the changed and the new post are selected
        self::assertSame('changed,new', implode(',', $needsValidation));
    }

    /**
     * Extension pattern (cart): a new object is not dirty, the negation is used as "not yet persisted" marker.
     */
    #[Test]
    public function newObjectIsNotDirty(): void
    {
        // Given: a new post with a title
        $cleanStateTracker = $this->get(CleanStateTracker::class);
        $post = new Post();
        $post->setTitle('A new post');

        // When / Then: it is not dirty, but new
        self::assertFalse($cleanStateTracker->isEntityDirty($post));
        self::assertTrue($post->_isNew());
    }

    /**
     * Extension pattern (headless, core form ViewHelper): a new object is told apart from a loaded one by _isNew(),
     * a clone of a new object is new as well.
     */
    #[Test]
    public function isNewCheckSeparatesNewObjectsFromLoadedOnes(): void
    {
        // Given: a new post, a clone of it and a loaded post
        $new = new Post();
        $clone = clone $new;
        $loaded = $this->get(PostRepository::class)->findByUid(1);

        // When: the check is applied
        $result = [
            'new' => $new->_isNew(),
            'clone' => $clone->_isNew(),
            'loaded' => $loaded->_isNew(),
        ];

        // Then: the new object and its clone match, the loaded object does not
        self::assertSame(['new' => true, 'clone' => true, 'loaded' => false], $result);
    }

    /**
     * The tracker returns the originals of an entity as they were loaded.
     */
    #[Test]
    public function trackerReturnsOriginalEntityData(): void
    {
        // Given: a loaded blog with a changed title
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        $blog->setTitle('Changed title');

        // When: the originals are requested
        $originals = $this->get(CleanStateTracker::class)->getOriginalEntityData($blog);

        // Then: the title still has the loaded value
        self::assertSame('Blog1', $originals['title']);
        self::assertSame('Blog1 description', $originals['description']);
    }

    /**
     * The tracker answers the dirty question per entity and per property.
     */
    #[Test]
    public function trackerAnswersDirtyQuestionPerEntityAndProperty(): void
    {
        // Given: a loaded blog and the tracker
        $tracker = $this->get(CleanStateTracker::class);
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        self::assertFalse($tracker->isEntityDirty($blog));

        // When: the title is changed
        $blog->setTitle('Changed title');

        // Then: the entity and the title are dirty, the description is not
        self::assertTrue($tracker->isEntityDirty($blog));
        self::assertTrue($tracker->isEntityPropertyDirty($blog, 'title'));
        self::assertFalse($tracker->isEntityPropertyDirty($blog, 'description'));
    }

    /**
     * The change set lists old and new value per changed property.
     */
    #[Test]
    public function trackerReturnsChangeSetWithOldAndNewValue(): void
    {
        // Given: a loaded blog with a changed title
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        $blog->setTitle('Changed title');

        // When: the change set is requested
        $changeSet = $this->get(CleanStateTracker::class)->getEntityChangeSet($blog);

        // Then: it contains the title only
        self::assertSame(['title' => ['Blog1', 'Changed title']], $changeSet);
    }

    /**
     * Setting the original of one property accepts the current value of that property as clean.
     */
    #[Test]
    public function trackerSetsOriginalOfOneProperty(): void
    {
        // Given: a loaded blog with a changed title and description
        $tracker = $this->get(CleanStateTracker::class);
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        $blog->setTitle('Changed title');
        $blog->setDescription('Changed description');

        // When: the original of the title is set
        $tracker->setOriginalEntityProperty($blog, 'title');

        // Then: only the description is still dirty
        self::assertFalse($tracker->isEntityPropertyDirty($blog, 'title'));
        self::assertTrue($tracker->isEntityPropertyDirty($blog, 'description'));
    }

    /**
     * Setting the original data of an entity accepts all current values as clean.
     */
    #[Test]
    public function trackerSetsOriginalDataOfWholeEntity(): void
    {
        // Given: a loaded blog with a changed title
        $tracker = $this->get(CleanStateTracker::class);
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        $blog->setTitle('Changed title');

        // When: the original data is set
        $tracker->setOriginalEntityData($blog);

        // Then: the blog is clean and the originals hold the new title
        self::assertFalse($tracker->isEntityDirty($blog));
        self::assertSame('Changed title', $tracker->getOriginalEntityData($blog)['title']);
    }

    /**
     * An object that was never registered has no originals and is not dirty.
     */
    #[Test]
    public function trackerKnowsNothingAboutNewObject(): void
    {
        // Given: a new post
        $tracker = $this->get(CleanStateTracker::class);
        $post = new Post();
        $post->setTitle('A new post');

        // When / Then: there are no originals, no change set and it is not dirty
        self::assertSame([], $tracker->getOriginalEntityData($post));
        self::assertSame([], $tracker->getEntityChangeSet($post));
        self::assertFalse($tracker->isEntityDirty($post));
    }

    /**
     * Setting the original data of an object storage resets its modified flag.
     */
    #[Test]
    public function trackerResetsModifiedStateOfObjectStorage(): void
    {
        // Given: a blog with a modified post storage
        $tracker = $this->get(CleanStateTracker::class);
        $blog = $this->get(BlogRepository::class)->findByUid(2);
        $blog->addPost($this->get(PostRepository::class)->findByUid(1));
        self::assertTrue($tracker->isEntityDirty($blog->getPosts()));

        // When: the original data of the storage is set
        $tracker->setOriginalEntityData($blog->getPosts());

        // Then: the storage is clean
        self::assertFalse($tracker->isEntityDirty($blog->getPosts()));
    }

    /**
     * Asking an uninitialized lazy object storage does not initialize it.
     */
    #[Test]
    public function uninitializedLazyObjectStorageIsNotDirtyAndStaysUninitialized(): void
    {
        // Given: a blog whose post storage is not accessed yet
        $tracker = $this->get(CleanStateTracker::class);
        $blog = $this->get(BlogRepository::class)->findByUid(1);
        $posts = $blog->_getProperty('posts');
        self::assertTrue(new \ReflectionClass(ObjectStorage::class)->isUninitializedLazyObject($posts)); // precondition

        // When: the tracker is asked and the original data is set
        $isDirty = $tracker->isEntityDirty($posts);
        $tracker->setOriginalEntityData($posts);

        // Then: the storage is not dirty and still uninitialized
        self::assertFalse($isDirty);
        self::assertTrue(new \ReflectionClass(ObjectStorage::class)->isUninitializedLazyObject($posts));
    }
}
