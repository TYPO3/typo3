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

use ExtbaseTeam\BlogExample\Domain\Repository\BlogRepository;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class QueryLocalizedDataTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Repository
     */
    protected $postRepository;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/translatedBlogExampleData.csv');
        $this->setUpBasicFrontendEnvironment();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configuration = [
            'persistence' => [
                'storagePid' => 20,
                'classes' => [
                    'TYPO3\CMS\Extbase\Domain\Model\Category' => [
                        'mapping' => ['tableName' => 'sys_category']
                    ]
                ]
            ]
        ];
        $configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);
        $configurationManager->setConfiguration($configuration);
        $this->postRepository = $this->objectManager->get(PostRepository::class);
        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
    }

    /**
     * Minimal frontend environment to satisfy Extbase Typo3DbBackend
     */
    protected function setUpBasicFrontendEnvironment()
    {
        /** @var MockObject|EnvironmentService $environmentServiceMock */
        $environmentServiceMock = $this->createMock(EnvironmentService::class);
        $environmentServiceMock
            ->expects(self::atLeast(1))
            ->method('isEnvironmentInFrontendMode')
            ->willReturn(true);
        GeneralUtility::setSingletonInstance(EnvironmentService::class, $environmentServiceMock);

        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON, []));

        $pageRepositoryFixture = new PageRepository();
        $frontendControllerMock = $this->createMock(TypoScriptFrontendController::class);
        $frontendControllerMock->sys_page = $pageRepositoryFixture;
        $GLOBALS['TSFE'] = $frontendControllerMock;
    }

    /**
     * Test in default language
     *
     * With overlays enabled it doesn't make a difference whether you call findByUid with translated record uid or
     * default language record uid.
     *
     * Note that with feature flag disabled, you'll get same result (not translated record) for both calls ->findByUid(2)
     * and ->findByUid(11)
     *
     * @test
     */
    public function findByUidOverlayModeOnDefaultLanguage()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON));

        $post2 = $this->postRepository->findByUid(2);

        self::assertEquals(['Post 2', 2, 2, 'Blog 1', 1, 1, 'John', 1, 1], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty('_localizedUid'),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty('_localizedUid'),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty('_localizedUid')
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        // with feature flag disable, you'll get default language object here too (Post 2).
        $post2translated = $this->postRepository->findByUid(11);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2translated->getTitle(),
            $post2translated->getUid(),
            $post2translated->_getProperty('_localizedUid'),
            $post2translated->getBlog()->getTitle(),
            $post2translated->getBlog()->getUid(),
            $post2translated->getBlog()->_getProperty('_localizedUid'),
            $post2translated->getAuthor()->getFirstname(),
            $post2translated->getAuthor()->getUid(),
            $post2translated->getAuthor()->_getProperty('_localizedUid')
        ]);
    }

    /**
     * Test in default language, overlays disabled
     *
     * @test
     */
    public function findByUidNoOverlaysDefaultLanguage()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF));

        $post2 = $this->postRepository->findByUid(2);
        self::assertEquals(['Post 2', 2, 2, 'Blog 1', 1, 1, 'John', 1, 1], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty('_localizedUid'),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty('_localizedUid'),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty('_localizedUid')
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $post2translated = $this->postRepository->findByUid(11);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2translated->getTitle(),
            $post2translated->getUid(),
            $post2translated->_getProperty('_localizedUid'),
            $post2translated->getBlog()->getTitle(),
            $post2translated->getBlog()->getUid(),
            $post2translated->getBlog()->_getProperty('_localizedUid'),
            $post2translated->getAuthor()->getFirstname(),
            $post2translated->getAuthor()->getUid(),
            $post2translated->getAuthor()->_getProperty('_localizedUid')
        ]);
    }

    /**
     * Test in language uid:1, overlays enabled
     *
     * @test
     */
    public function findByUidOverlayModeOnLanguage()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        $post2 = $this->postRepository->findByUid(2);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty('_localizedUid'),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty('_localizedUid'),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty('_localizedUid')
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();
        $post2translated = $this->postRepository->findByUid(11);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2translated->getTitle(),
            $post2translated->getUid(),
            $post2translated->_getProperty('_localizedUid'),
            $post2translated->getBlog()->getTitle(),
            $post2translated->getBlog()->getUid(),
            $post2translated->getBlog()->_getProperty('_localizedUid'),
            $post2translated->getAuthor()->getFirstname(),
            $post2translated->getAuthor()->getUid(),
            $post2translated->getAuthor()->_getProperty('_localizedUid')
        ]);
    }

    /**
     * Test in language uid:1, overlays disabled
     *
     * @test
     */
    public function findByUidNoOverlaysLanguage()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF));

        $post2 = $this->postRepository->findByUid(2);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty('_localizedUid'),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty('_localizedUid'),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty('_localizedUid')
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $post2translated = $this->postRepository->findByUid(11);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2translated->getTitle(),
            $post2translated->getUid(),
            $post2translated->_getProperty('_localizedUid'),
            $post2translated->getBlog()->getTitle(),
            $post2translated->getBlog()->getUid(),
            $post2translated->getBlog()->_getProperty('_localizedUid'),
            $post2translated->getAuthor()->getFirstname(),
            $post2translated->getAuthor()->getUid(),
            $post2translated->getAuthor()->_getProperty('_localizedUid')
        ]);
    }

    /**
     * This tests shows what query by uid returns depending on the language,
     * and used uid (default language record or translated record uid).
     * All with overlay mode enabled.
     *
     * The post with uid 2 is translated to language 1, and there has uid 11.
     *
     * @test
     */
    public function customFindByUidOverlayEnabled()
    {
        // we're in default lang and fetching by uid of the record in default language
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid(0);
        $querySettings->setLanguageOverlayMode(true);
        $query->matching($query->equals('uid', 2));
        $post2 = $query->execute()->getFirst();

        //the expected state is the same with and without feature flag
        self::assertEquals(['Post 2', 2, 2, 'Blog 1', 1, 1, 'John', 1, 1], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty('_localizedUid'),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty('_localizedUid'),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty('_localizedUid')
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid(0);
        $querySettings->setLanguageOverlayMode(true);
        $query->matching($query->equals('uid', 11));
        $post2 = $query->execute()->getFirst();

        //this assertion is true for both enabled and disabled flag
        self::assertNull($post2);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid(1);
        $querySettings->setLanguageOverlayMode(true);
        $query->matching($query->equals('uid', 2));
        $post2 = $query->execute()->getFirst();

        self::assertNull($post2);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid(1);
        $querySettings->setLanguageOverlayMode(true);
        $query->matching($query->equals('uid', 11));
        $post2 = $query->execute()->getFirst();

        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty('_localizedUid'),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty('_localizedUid'),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty('_localizedUid')
        ]);
    }

    /**
     * This tests shows what query by uid returns depending on the language,
     * and used uid (default language record or translated record uid).
     * All with overlay mode disabled.
     *
     * The post with uid 2 is translated to language 1, and there has uid 11.
     *
     * @test
     */
    public function customFindByUidOverlayDisabled()
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid(0);
        $querySettings->setLanguageOverlayMode(false);
        $query->matching($query->equals('uid', 2));
        $post2 = $query->execute()->getFirst();

        self::assertEquals(['Post 2', 2, 2, 'Blog 1', 1, 1, 'John', 1, 1], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty('_localizedUid'),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty('_localizedUid'),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty('_localizedUid')
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid(0);
        $querySettings->setLanguageOverlayMode(false);
        $query->matching($query->equals('uid', 11));
        $post2 = $query->execute()->getFirst();

        //this assertion is true for both enabled and disabled flag
        self::assertNull($post2);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid(1);
        $querySettings->setLanguageOverlayMode(false);
        $query->matching($query->equals('uid', 2));
        $post2 = $query->execute()->getFirst();

        self::assertNull($post2);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid(1);
        $querySettings->setLanguageOverlayMode(false);
        $query->matching($query->equals('uid', 11));
        $post2 = $query->execute()->getFirst();

        self::assertEquals(['Post 2 - DK', 11, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty('_localizedUid'),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty('_localizedUid'),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty('_localizedUid')
        ]);
    }

    public function queryFirst5PostsDataProvider()
    {
        //put it to variable to make cases with the same expected values explicit
        $lang0Expected = [
            [
                'title' => 'Post 4',
                'uid' => 4,
                '_localizedUid' => 4,
                'content' => 'A - content',
                'blog.title' => 'Blog 1',
                'blog.uid' => 1,
                'blog._localizedUid' => 1,
                'author.firstname' => 'John',
                'author.uid' => 1,
                'author._localizedUid' => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.uid' => 1,
                'secondAuthor._localizedUid' => 1,
                'tags' => [],
            ],
            [
                'title' => 'Post 2',
                'uid' => 2,
                '_localizedUid' => 2,
                'content' => 'B - content',
                'blog.title' => 'Blog 1',
                'blog.uid' => 1,
                'blog._localizedUid' => 1,
                'author.firstname' => 'John',
                'author.uid' => 1,
                'author._localizedUid' => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.uid' => 1,
                'secondAuthor._localizedUid' => 1,
                'tags.0.name' => 'Tag2',
                'tags.0.uid' => 2,
                'tags.0._localizedUid' => 2,
                'tags.1.name' => 'Tag3',
                'tags.1.uid' => 3,
                'tags.1._localizedUid' => 3,
                'tags.2.name' => 'Tag4',
                'tags.2.uid' => 4,
                'tags.2._localizedUid' => 4,
            ],
            [
                'title' => 'Post 7',
                'uid' => 7,
                '_localizedUid' => 7,
                'content' => 'C - content',
                'blog.title' => 'Blog 1',
                'blog.uid' => 1,
                'blog._localizedUid' => 1,
                'author.firstname' => 'John',
                'author.uid' => 1,
                'author._localizedUid' => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.uid' => 1,
                'secondAuthor._localizedUid' => 1,
                'tags' => [],
            ],
            [
                'title' => 'Post 6',
                'uid' => 6,
                '_localizedUid' => 6,
                'content' => 'F - content',
                'blog.title' => 'Blog 1',
                'blog.uid' => 1,
                'blog._localizedUid' => 1,
                'author.firstname' => 'John',
                'author.uid' => 1,
                'author._localizedUid' => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.uid' => 1,
                'secondAuthor._localizedUid' => 1,
                'tags' => [],
            ],
            [
                'title' => 'Post 1 - not translated',
                'uid' => 1,
                '_localizedUid' => 1,
                'content' => 'G - content',
                'blog.title' => 'Blog 1',
                'blog.uid' => 1,
                'blog._localizedUid' => 1,
                'author.firstname' => 'John',
                'author.uid' => 1,
                'author._localizedUid' => 1,
                'secondAuthor.firstname' => 'Never translate me henry',
                'secondAuthor.uid' => 3,
                'secondAuthor._localizedUid' => 3,
                'tags.0.name' => 'Tag1',
                'tags.0.uid' => 1,
                'tags.0._localizedUid' => 1,
                'tags.1.name' => 'Tag2',
                'tags.1.uid' => 2,
                'tags.1._localizedUid' => 2,
                'tags.2.name' => 'Tag3',
                'tags.2.uid' => 3,
                'tags.2._localizedUid' => 3,
            ],
        ];
        return [
            [
                'language' => 0,
                'overlay' => true,
                'expected' => $lang0Expected
            ],
            [
                'language' => 0,
                'overlay' => false,
                'expected' => $lang0Expected
            ],
            [
                'language' => 1,
                'overlay' => true,
                'expected' => [
                    [
                        'title' => 'Post 5 - DK',
                        'uid' => 5,
                        '_localizedUid' => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 2 - DK',
                        'uid' => 2,
                        '_localizedUid' => 11,
                        'content' => 'C - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags.0.name' => 'Tag 3 DK',
                        'tags.0.uid' => 3,
                        'tags.0._localizedUid' => 18,
                        'tags.1.name' => 'Tag4',
                        'tags.1.uid' => 4,
                        'tags.1._localizedUid' => 4,
                        'tags.2.name' => 'Tag5',
                        'tags.2.uid' => 5,
                        'tags.2._localizedUid' => 5,
                        'tags.3.name' => 'Tag 6 DK',
                        'tags.3.uid' => 6,
                        'tags.3._localizedUid' => 19,
                        'tags.4.name' => 'Tag7',
                        'tags.4.uid' => 7,
                        'tags.4._localizedUid' => 7,
                    ],
                    [
                        'title' => 'Post 6',
                        'uid' => 6,
                        '_localizedUid' => 6,
                        'content' => 'F - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 1 - not translated',
                        'uid' => 1,
                        '_localizedUid' => 1,
                        'content' => 'G - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Never translate me henry',
                        'secondAuthor.uid' => 3,
                        'secondAuthor._localizedUid' => 3,
                        'tags.0.name' => 'Tag 1 DK',
                        'tags.0.uid' => 1,
                        'tags.0._localizedUid' => 16,
                        'tags.1.name' => 'Tag 2 DK',
                        'tags.1.uid' => 2,
                        'tags.1._localizedUid' => 17,
                        'tags.2.name' => 'Tag 3 DK',
                        'tags.2.uid' => 3,
                        'tags.2._localizedUid' => 18,

                    ],
                    [
                        'title' => 'Post 3',
                        'uid' => 3,
                        '_localizedUid' => 3,
                        'content' => 'I - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags' => [],
                    ],
                ],
            ],
            [
                'language' => 1,
                'overlay' => 'hideNonTranslated',
                // here we have only 4 items instead of 5 as post "Post DK only" uid:15 has no language 0 parent,
                // so with overlay enabled it's not shown
                'expected' => [
                    [
                        'title' => 'Post 5 - DK',
                        'uid' => 5,
                        '_localizedUid' => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 2 - DK',
                        'uid' => 2,
                        '_localizedUid' => 11,
                        'content' => 'C - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags.0.name' => 'Tag 3 DK',
                        'tags.0.uid' => 3,
                        'tags.0._localizedUid' => 18,
                        'tags.1.name' => 'Tag4',
                        'tags.1.uid' => 4,
                        'tags.1._localizedUid' => 4,
                        'tags.2.name' => 'Tag5',
                        'tags.2.uid' => 5,
                        'tags.2._localizedUid' => 5,
                        'tags.3.name' => 'Tag 6 DK',
                        'tags.3.uid' => 6,
                        'tags.3._localizedUid' => 19,
                        'tags.4.name' => 'Tag7',
                        'tags.4.uid' => 7,
                        'tags.4._localizedUid' => 7,
                    ],
                    [
                        'title' => 'Post 7 - DK',
                        'uid' => 7,
                        '_localizedUid' => 14,
                        'content' => 'S - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 4 - DK',
                        'uid' => 4,
                        '_localizedUid' => 12,
                        'content' => 'U - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags' => [],
                    ],
                ],
            ],
            [
                'language' => 1,
                'overlay' => false,
                'expected' => [
                    [
                        'title' => 'Post 5 - DK',
                        'uid' => 13,
                        '_localizedUid' => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post DK only',
                        'uid' => 15,
                        '_localizedUid' => 15,
                        'content' => 'B - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 2 - DK',
                        'uid' => 11,
                        '_localizedUid' => 11,
                        'content' => 'C - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags.0.name' => 'Tag 3 DK',
                        'tags.0.uid' => 3,
                        'tags.0._localizedUid' => 18,
                        'tags.1.name' => 'Tag4',
                        'tags.1.uid' => 4,
                        'tags.1._localizedUid' => 4,
                        'tags.2.name' => 'Tag5',
                        'tags.2.uid' => 5,
                        'tags.2._localizedUid' => 5,
                        'tags.3.name' => 'Tag 6 DK',
                        'tags.3.uid' => 6,
                        'tags.3._localizedUid' => 19,
                        'tags.4.name' => 'Tag7',
                        'tags.4.uid' => 7,
                        'tags.4._localizedUid' => 7,
                    ],
                    [
                        'title' => 'Post 7 - DK',
                        'uid' => 14,
                        '_localizedUid' => 14,
                        'content' => 'S - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 4 - DK',
                        'uid' => 12,
                        '_localizedUid' => 12,
                        'content' => 'U - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                        'tags' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * This test check posts returned by repository, when changing language and languageOverlayMode
     * It also sets limit, offset to validate there are no "gaps" in pagination
     * and sorting (on a posts property)
     *
     * @test
     * @dataProvider queryFirst5PostsDataProvider
     *
     * @param int $languageUid
     * @param bool $overlay
     * @param array $expected
     */
    public function queryFirst5Posts($languageUid, $overlay, $expected)
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid($languageUid);
        $querySettings->setLanguageOverlayMode($overlay);

        $query->setOrderings([
            'content' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING
        ]);
        $query->setLimit(5);
        $query->setOffset(0);
        $posts = $query->execute()->toArray();

        self::assertCount(count($expected), $posts);
        $this->assertObjectsProperties($posts, $expected);
    }

    public function queryPostsByPropertyDataProvider()
    {
        $lang0Expected = [
            [
                'title' => 'Post 5',
                'uid' => 5,
                '_localizedUid' => 5,
                'content' => 'Z - content',
                'blog.title' => 'Blog 1',
                'blog.uid' => 1,
                'blog._localizedUid' => 1,
                'author.firstname' => 'John',
                'author.uid' => 1,
                'author._localizedUid' => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.uid' => 1,
                'secondAuthor._localizedUid' => 1,
            ],
            [
                'title' => 'Post 6',
                'uid' => 6,
                '_localizedUid' => 6,
                'content' => 'F - content',
                'blog.title' => 'Blog 1',
                'blog.uid' => 1,
                'blog._localizedUid' => 1,
                'author.firstname' => 'John',
                'author.uid' => 1,
                'author._localizedUid' => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.uid' => 1,
                'secondAuthor._localizedUid' => 1,
                'tags' => [],
            ]
        ];

        return [
            [
                'language' => 0,
                'overlay' => true,
                'expected' => $lang0Expected
            ],
            [
                'language' => 0,
                'overlay' => 'hideNonTranslated',
                'expected' => $lang0Expected
            ],
            [
                'language' => 0,
                'overlay' => false,
                'expected' => $lang0Expected
            ],
            [
                'language' => 1,
                'overlay' => true,
                'expected' => [
                    [
                        'title' => 'Post 6',
                        'uid' => 6,
                        '_localizedUid' => 6,
                        'content' => 'F - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                    ],
                    [
                        'title' => 'Post 5 - DK',
                        'uid' => 5,
                        '_localizedUid' => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                    ],
                ],
            ],
            [
                'language' => 1,
                'overlay' => 'hideNonTranslated',
                'expected' => [
                    [
                        'title' => 'Post 5 - DK',
                        'uid' => 5,
                        '_localizedUid' => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                    ],
                ],
            ],
            [
                'language' => 1,
                'overlay' => false,
                'expected' => [
                    [
                        'title' => 'Post 5 - DK',
                        'uid' => 13,
                        '_localizedUid' => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                    ],
                    [
                        'title' => 'Post DK only',
                        'uid' => 15,
                        '_localizedUid' => 15,
                        'content' => 'B - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.uid' => 1,
                        'blog._localizedUid' => 2,
                        'author.firstname' => 'Translated John',
                        'author.uid' => 1,
                        'author._localizedUid' => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.uid' => 1,
                        'secondAuthor._localizedUid' => 2,
                    ],
                ],
            ],
        ];
    }

    /**
     * This test check posts returned by repository, when filtering by property
     *
     * "Post 6" is not translated
     * "Post 5" is translated as "Post 5 - DK"
     * "Post DK only" has no translation parent
     *
     * @test
     * @dataProvider queryPostsByPropertyDataProvider
     *
     * @param int $languageUid
     * @param bool $overlay
     * @param array $expected
     */
    public function queryPostsByProperty($languageUid, $overlay, $expected)
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageUid($languageUid);
        $querySettings->setLanguageOverlayMode($overlay);

        $query->matching(
            $query->logicalOr(
                $query->like('title', 'Post 5%'),
                $query->like('title', 'Post 6%'),
                $query->like('title', 'Post DK only')
            )
        );
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = $query->execute()->toArray();

        self::assertCount(count($expected), $posts);
        $this->assertObjectsProperties($posts, $expected);
    }

    public function postsWithoutRespectingSysLanguageDataProvider()
    {
        $lang0Expected = [
             [
                 'title' => 'Blog 1',
                 'uid' => 1,
                 '_localizedUid' => 1,
             ],
             [
                 'title' => 'Blog 1',
                 'uid' => 1,
                 '_localizedUid' => 1,
             ],
         ];
        $mixed = [
             [
                 'title' => 'Blog 1',
                 'uid' => 1,
                 '_localizedUid' => 1,
             ],
             [
                 'title' => 'Blog 1 DK',
                 'uid' => 2,
                 '_localizedUid' => 2,
             ],
         ];
        return [
             [
                 'language' => 0,
                 'overlay' => LanguageAspect::OVERLAYS_ON,
                 'expected' => $lang0Expected
             ],
             [
                 'language' => 0,
                 'overlay' => LanguageAspect::OVERLAYS_ON,
                 'expected' => $lang0Expected
             ],
             [
                 'language' => 0,
                 'overlay' => LanguageAspect::OVERLAYS_OFF,
                 'expected' => $mixed
             ],
             [
                 'language' => 0,
                 'overlay' => LanguageAspect::OVERLAYS_OFF,
                 'expected' => $mixed
             ],
             [
                 'language' => 1,
                 'overlay' => LanguageAspect::OVERLAYS_ON,
                 'expected' => [
                     [
                         'title' => 'Blog 1 DK',
                         'uid' => 1,
                         '_localizedUid' => 2,
                     ],
                     [
                         'title' => 'Blog 1 DK',
                         'uid' => 1,
                         '_localizedUid' => 2,
                     ],
                 ]
             ],
             [
                 'language' => 1,
                 'overlay' => LanguageAspect::OVERLAYS_ON,
                 'expected' => [
                     [
                         'title' => 'Blog 1 DK',
                         'uid' => 1,
                         '_localizedUid' => 2,
                     ],
                     [
                         'title' => 'Blog 1 DK',
                         'uid' => 1,
                         '_localizedUid' => 2,
                     ],
                 ]
             ],
             [
                 'language' => 1,
                 'overlay' => LanguageAspect::OVERLAYS_OFF,
                 'expected' => $mixed
             ],
         ];
    }

    /**
     * This test demonstrates how query behaves when setRespectSysLanguage is set to false.
     * The test now documents the WRONG behaviour described in https://forge.typo3.org/issues/45873
     * and is connected with https://forge.typo3.org/issues/59992
     *
     * The expected state is that when setRespectSysLanguage is false, then both: default language record,
     * and translated language record should be returned. Now we're getting same record twice.
     *
     * @test
     * @dataProvider postsWithoutRespectingSysLanguageDataProvider
     * @param int $languageUid
     * @param string|bool $overlay
     * @param array $expected
     */
    public function postsWithoutRespectingSysLanguage($languageUid, $overlay, $expected)
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect($languageUid, $languageUid, $overlay));

        $blogRepository = $this->objectManager->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectSysLanguage(false);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        $posts = $query->execute()->toArray();

        self::assertCount(count($expected), $posts);
        $this->assertObjectsProperties($posts, $expected);
    }

    /**
     * Compares array of domain objects with array containing properties values
     *
     * @param array $objects
     * @param array $expected array of expected property values [ ['property' => 'value'], ['property' => 'value2']]
     */
    protected function assertObjectsProperties($objects, $expected)
    {
        $actual = [];
        foreach ($objects as $key => $post) {
            $actualPost = [];
            $propertiesToCheck = array_keys($expected[$key]);
            foreach ($propertiesToCheck as $propertyPath) {
                $actualPost[$propertyPath] = self::getPropertyPath($post, $propertyPath);
            }
            $actual[] = $actualPost;
            self::assertEquals($expected[$key], $actual[$key], 'Assertion of the $expected[' . $key . '] failed');
        }
        self::assertEquals($expected, $actual);
    }

    /**
     * This is a copy of the ObjectAccess::getPropertyPath, but with third argument of getPropertyInternal set as true,
     * to access protected properties, and iterator_to_array added.
     *
     * @param mixed $subject Object or array to get the property path from
     * @param string $propertyPath
     *
     * @return mixed Value of the property
     */
    protected static function getPropertyPath($subject, $propertyPath)
    {
        $propertyPathSegments = explode('.', $propertyPath);
        try {
            foreach ($propertyPathSegments as $pathSegment) {
                $subject = ObjectAccess::getPropertyInternal($subject, $pathSegment, true);
                if ($subject instanceof \SplObjectStorage || $subject instanceof ObjectStorage) {
                    $subject = iterator_to_array(clone $subject, false);
                }
            }
        } catch (PropertyNotAccessibleException $error) {
            return null;
        }
        return $subject;
    }
}
