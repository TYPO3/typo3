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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Person;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class UpdateTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    private PersistenceManager $persistentManager;
    private PostRepository $postRepository;
    protected BlogRepository $blogRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->persistentManager = $this->get(PersistenceManager::class);
        $this->postRepository = $this->get(PostRepository::class);
        $this->blogRepository = $this->get(BlogRepository::class);
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function nullableDomainModelRelationCanBeSetToNullAndIsPersistedAsZero(): void
    {
        $person = new Person('Firstname', 'Lastname', 'email@domain.tld');
        $post = new Post();
        $post->setTitle('A blogpost');
        $post->setContent('Content of the blogpost');
        $post->setAuthor($person);

        $this->postRepository->add($post);
        $this->persistentManager->persistAll();

        // Make sure the author field can be set to null
        $insertedPost = $this->postRepository->findByUid(1);
        $insertedPost->setAuthor(null);
        $this->postRepository->update($insertedPost);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultNullableDomainModelRelation.csv');
    }

    #[Test]
    public function nullableDateTimePropertyCanBeSetToNullAndIsPersistedAsZero(): void
    {
        $archiveDate = new \DateTime();

        $post = new Post();
        $post->setTitle('A blogpost');
        $post->setContent('Content of the blogpost');
        $post->setArchiveDate($archiveDate);

        $this->postRepository->add($post);
        $this->persistentManager->persistAll();

        // Make sure the archive_date field can be set to null
        $insertedPost = $this->postRepository->findByUid(1);
        $insertedPost->setArchiveDate(null);
        $this->postRepository->update($insertedPost);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultNullableDateTimeProperty.csv');
    }

    #[Test]
    public function updateObjectSetsNullAsNullForSimpleTypes(): void
    {
        $newBlogTitle = 'aDi1oogh';
        $newBlog = new Blog();
        $newBlog->setTitle($newBlogTitle);
        $newBlog->setSubtitle('subtitle');

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        // make sure null can be set explicitly
        $insertedBlog = $this->blogRepository->findByUid(1);
        $insertedBlog->setSubtitle(null);
        $this->blogRepository->update($insertedBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultUpdateObjectSetsNullAsNullForSimpleTypes.csv');
    }
}
