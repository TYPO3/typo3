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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Enum\Salutation;
use TYPO3Tests\BlogExample\Domain\Model\Info;
use TYPO3Tests\BlogExample\Domain\Model\NoTcaEntity;
use TYPO3Tests\BlogExample\Domain\Model\Person;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;
use TYPO3Tests\TestJsonFields\Domain\Model\Example;
use TYPO3Tests\TestJsonFields\Domain\Repository\ExampleRepository;

final class AddTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/test_json_fields',
    ];

    private PersistenceManager $persistentManager;
    private BlogRepository $blogRepository;
    private PersonRepository $personRepository;
    private PostRepository $postRepository;
    private ExampleRepository $exampleRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->persistentManager = $this->get(PersistenceManager::class);
        $this->blogRepository = $this->get(BlogRepository::class);
        $this->personRepository = $this->get(PersonRepository::class);
        $this->postRepository = $this->get(PostRepository::class);
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
    public function addObjectSetsDefaultLanguageTest(): void
    {
        $newBlogTitle = 'aDi1oogh';
        $newBlog = new Blog();
        $newBlog->setTitle($newBlogTitle);

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultAddObjectSetsDefaultLanguage.csv');
    }

    #[Test]
    public function addObjectSetsDefinedLanguageTest(): void
    {
        $newBlogTitle = 'aDi1oogh';
        $newBlog = new Blog();
        $newBlog->setTitle($newBlogTitle);
        $newBlog->_setProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID, -1);

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultAddObjectSetsDefinedLanguage.csv');
    }

    #[Test]
    public function addObjectPersistsEnumProperty(): void
    {
        $person = new Person();
        $person->setSalutation(Salutation::MR);

        $this->persistentManager->add($person);
        $this->persistentManager->persistAll();
        unset($person);

        /** @var Person $person */
        $person = $this->persistentManager->getObjectByIdentifier(1, Person::class);

        self::assertSame(Salutation::MR, $person->getSalutation());
    }

    #[Test]
    public function addObjectSetsPidFromParentObjectToObjectStorageProperty(): void
    {
        $post = new Post();
        $post->setTitle('My Post');

        $newBlog = new Blog();
        $newBlog->setPid(123);
        $newBlog->setTitle('My Blog');
        $newBlog->addPost($post);

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultAddObjectSetsPidFromParentObjectToObjectStorageProperty.csv');
    }

    #[Test]
    public function addObjectSetsPidFromParentObjectToDomainObjectProperty(): void
    {
        $administrator = new Administrator();
        $administrator->setName('Admin');

        $newBlog = new Blog();
        $newBlog->setTitle('My Blog');
        $newBlog->setPid(123);
        $newBlog->setAdministrator($administrator);

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultAddObjectSetsPidFromParentObjectToDomainObjectProperty.csv');
    }

    #[Test]
    public function addObjectRespectsPersistenceStoragePid(): void
    {
        $configuration = [
            'persistence' => [
                'storagePid' => 10,
            ],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $newBlog = new Blog();
        $newBlog->setTitle('My Blog');

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultAddObjectRespectsPersistenceStoragePid.csv');
    }

    #[Test]
    public function addObjectRespectsNewRecordStoragePid(): void
    {
        $configuration = [
            'persistence' => [
                'classes' => [
                    Blog::class => [
                        'newRecordStoragePid' => 20,
                    ],
                    Post::class => [
                        'newRecordStoragePid' => 30,
                    ],
                ],
            ],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $post = new Post();
        $post->setTitle('My Post');

        $newBlog = new Blog();
        $newBlog->setTitle('My Blog');
        $newBlog->addPost($post);

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultAddObjectRespectsNewRecordStoragePid.csv');
    }

    #[Test]
    public function addObjectDoesNotWriteHistoryEntryWhenFeatureFlagDisabled(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.enableHistoryTracking'] = false;
        $newBlog = new Blog();
        $newBlog->setTitle('A test blog');

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultAddObjectDoesNotWriteHistoryEntry.csv');
    }

    #[Test]
    public function addObjectWritesHistoryEntry(): void
    {
        $newBlog = new Blog();
        $newBlog->setTitle('A test blog');

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultAddObjectWritesHistoryEntry.csv');
    }

    #[Test]
    public function addObjectWritesHistoryEntryWithFrontendUserContext(): void
    {
        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->user = ['uid' => 7];
        $this->get(Context::class)->setAspect('frontend.user', new UserAspect($frontendUser));

        $newBlog = new Blog();
        $newBlog->setTitle('A test blog');

        $this->blogRepository->add($newBlog);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultAddObjectWritesHistoryEntryFrontendUser.csv');
    }

    #[Test]
    public function addObjectDoesNotWriteHistoryEntryWhenTrackingIsDisabled(): void
    {
        $person = new Person('Firstname', 'Lastname', 'test@example.com');

        $this->personRepository->add($person);
        $this->persistentManager->persistAll();

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_history');
        $count = (int)$connection->count('uid', 'sys_history', []);
        self::assertSame(0, $count);
    }

    #[Test]
    public function addObjectDoesNotFailAndSkipsHistoryWhenTableIsNotInTcaSchema(): void
    {
        $entity = new NoTcaEntity();
        $entity->setTitle('Test');

        $this->persistentManager->add($entity);
        $this->persistentManager->persistAll();

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_history');
        $count = (int)$connection->count('uid', 'sys_history', []);
        self::assertSame(0, $count);
    }

    #[Test]
    public function addObjectStoresDomainObjectRelationAsUidNotToStringInHistory(): void
    {
        // Info implements __toString() and DomainObjectInterface. The tracker must
        // check DomainObjectInterface first and store the UID, not the __toString() result.
        $info = new Info();
        $info->setName('ShouldNotAppearInHistory');
        $this->persistentManager->add($info);
        $this->persistentManager->persistAll();
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_history')->delete('sys_history', []);

        $post = new Post();
        $post->setTitle('Test Post');
        $post->setAdditionalName($info);

        $this->postRepository->add($post);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TestResultAddObjectStoresDomainObjectRelationAsUidNotToStringInHistory.csv');
    }

    #[Test]
    public function addObjectWithJsonAsStringsCanBePersisted(): void
    {
        $platform = $this->get(ConnectionPool::class)->getConnectionForTable('tx_testjsonfields_domain_model_example')->getDatabasePlatform();
        $platformName = match (true) {
            ($platform instanceof DoctrineMySQLPlatform) => 'MySQL',
            ($platform instanceof DoctrineMariaDBPlatform) => 'MariaDB',
            ($platform instanceof DoctrinePostgreSQLPlatform) => 'Postgres',
            ($platform instanceof DoctrineSQLitePlatform) => 'SQLite',
            default => throw new \RuntimeException(
                sprintf('Unsupported database platform %s', $platform::class),
                1746384276,
            ),
        };

        $newExample = new Example();
        $newExample->setTitle('test 1');
        $newExample->setNativeJsonAsTextField(json_encode(['nativeJsonAsTextField' => 123], JSON_THROW_ON_ERROR));
        $newExample->setTcaJsonField(json_encode(['tcaJsonField' => 987], JSON_THROW_ON_ERROR));

        $this->exampleRepository->add($newExample);
        $this->persistentManager->persistAll();

        $this->assertCSVDataSet(__DIR__ . sprintf('/Fixtures/TestJsonFields/repositoryAdd_assertFor%s.csv', $platformName));
    }
}
