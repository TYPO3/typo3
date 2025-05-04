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

use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Person;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;
use TYPO3Tests\TestJsonFields\Domain\Model\Example;
use TYPO3Tests\TestJsonFields\Domain\Repository\ExampleRepository;

final class UpdateTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/test_json_fields',
    ];

    private PersistenceManager $persistentManager;
    private PostRepository $postRepository;
    private BlogRepository $blogRepository;
    private PersonRepository $personRepository;
    private ExampleRepository $exampleRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->persistentManager = $this->get(PersistenceManager::class);
        $this->postRepository = $this->get(PostRepository::class);
        $this->blogRepository = $this->get(BlogRepository::class);
        $this->personRepository = $this->get(PersonRepository::class);
        $this->exampleRepository = $this->get(ExampleRepository::class);

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = true;
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = false;
        parent::tearDown();
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

    #[Test]
    public function updateObjectWritesHistoryEntry(): void
    {
        $blog = new Blog();
        $blog->setTitle('A test blog');

        $this->blogRepository->add($blog);
        $this->persistentManager->persistAll();

        $insertedBlog = $this->blogRepository->findByUid($blog->getUid());
        $insertedBlog->setTitle('Updated title');
        $this->blogRepository->update($insertedBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultUpdateObjectWritesHistoryEntry.csv');
    }

    #[Test]
    public function updateObjectDoesNotWriteHistoryEntryWhenTrackingIsDisabled(): void
    {
        $person = new Person('Firstname', 'Lastname', 'test@example.com');

        $this->personRepository->add($person);
        $this->persistentManager->persistAll();

        $insertedPerson = $this->personRepository->findByUid($person->getUid());
        $insertedPerson->setFirstname('UpdatedFirstname');
        $this->personRepository->update($insertedPerson);
        $this->persistentManager->persistAll();

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_history');
        $count = (int)$connection->count('uid', 'sys_history', []);
        self::assertSame(0, $count);
    }

    #[Test]
    public function updateObjectDoesNotWriteHistoryEntryWhenFeatureFlagIsDisabled(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = false;
        $blog = new Blog();
        $blog->setTitle('A test blog');

        $this->blogRepository->add($blog);
        $this->persistentManager->persistAll();

        $insertedBlog = $this->blogRepository->findByUid($blog->getUid());
        $insertedBlog->setTitle('Updated title');
        $this->blogRepository->update($insertedBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultUpdateObjectDoesNotWriteHistoryEntry.csv');
    }

    #[Test]
    public function updateObjectWithJsonAsStringsCanBePersisted(): void
    {
        $platform = $this->get(ConnectionPool::class)->getConnectionForTable('tx_testjsonfields_domain_model_example')->getDatabasePlatform();
        $platformName = match (true) {
            ($platform instanceof DoctrineMySQLPlatform) => 'MySQL',
            ($platform instanceof DoctrineMariaDBPlatform) => 'MariaDB',
            ($platform instanceof DoctrinePostgreSQLPlatform) => 'Postgres',
            ($platform instanceof DoctrineSQLitePlatform) => 'SQLite',
            default => throw new \RuntimeException(
                sprintf('Unsupported database platform %s', $platform::class),
                1746387704,
            ),
        };

        $newExample = new Example();
        $newExample->setTitle('test 1');
        $newExample->setNativeJsonAsTextField(json_encode(['nativeJsonAsTextField' => 123], JSON_THROW_ON_ERROR));
        $newExample->setTcaJsonField(json_encode(['tcaJsonField' => 987], JSON_THROW_ON_ERROR));

        $this->exampleRepository->add($newExample);
        $this->persistentManager->persistAll();
        $this->assertCSVDataSet(__DIR__ . sprintf('/Fixtures/TestJsonFields/repositoryAdd_assertFor%s.csv', $platformName));

        $insertedExample = $this->exampleRepository->findByUid(1);
        $insertedExample->setTitle('test 1 updated');
        $newExample->setNativeJsonAsTextField(json_encode(['nativeJsonAsTextField' => 234], JSON_THROW_ON_ERROR));
        $newExample->setTcaJsonField(json_encode(['tcaJsonField' => 876], JSON_THROW_ON_ERROR));

        $this->exampleRepository->update($insertedExample);
        $this->persistentManager->persistAll();
        $this->assertCSVDataSet(__DIR__ . sprintf('/Fixtures/TestJsonFields/repositoryUpdate_assertFor%s.csv', $platformName));
    }
}
