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
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Administrator;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Enum\Salutation;
use TYPO3Tests\BlogExample\Domain\Model\Person;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

final class AddTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected PersistenceManager $persistentManager;
    protected BlogRepository $blogRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->persistentManager = $this->get(PersistenceManager::class);
        $this->blogRepository = $this->get(BlogRepository::class);
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
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
}
