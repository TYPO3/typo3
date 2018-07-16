<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

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

use ExtbaseTeam\BlogExample\Domain\Model\Post;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Frontend\Page\PageRepository;

class TranslationLegacyTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
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
     * Sets up this test suite.
     */
    protected function setUp()
    {
        parent::setUp();
        /*
         * Posts Dataset for the tests:
         *
         * Post1
         *   -> EN: Post1
         *   -> GR: Post1
         * Post2
         *   -> EN: Post2
         * Post3
         * Post10 [hidden]
         *   -> GR: Post10 [hidden]
         */
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/blogs.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/translated-posts.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/post-tag-mm.xml');
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/tags.xml');

        $this->setUpBasicFrontendEnvironment();

        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $configuration = [
            'features' => ['consistentTranslationOverlayHandling' => 0]
        ];
        $configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        $configurationManager->setConfiguration($configuration);

        $this->postRepository = $this->objectManager->get(\ExtbaseTeam\BlogExample\Domain\Repository\PostRepository::class);
    }

    /**
     * Minimal frontend environment to satisfy Extbase Typo3DbBackend
     */
    protected function setUpBasicFrontendEnvironment()
    {
        // in v9 overlay and language mode has different default values, so we need to set them here explicitely
        // to match v8 behaviour
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF, ['off']));

        /** @var MockObject|EnvironmentService $environmentServiceMock */
        $environmentServiceMock = $this->createMock(EnvironmentService::class);
        $environmentServiceMock
            ->expects($this->atLeast(1))
            ->method('isEnvironmentInFrontendMode')
            ->willReturn(true);
        GeneralUtility::setSingletonInstance(EnvironmentService::class, $environmentServiceMock);

        $pageRepositoryFixture = new PageRepository();
        $frontendControllerMock = $this->createMock(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class);
        $frontendControllerMock->sys_page = $pageRepositoryFixture;
        $GLOBALS['TSFE'] = $frontendControllerMock;
    }

    /**
     * Tests if repository returns correct number of posts in the default language
     *
     * @test
     */
    public function countReturnsCorrectNumberOfPosts()
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(0);

        $this->assertFalse($querySettings->getLanguageOverlayMode());
        $this->assertNull($querySettings->getLanguageMode());

        $postCount = $query->execute()->count();
        $this->assertSame(3, $postCount);
    }

    /**
     * This test shows the difference between old and new rendering
     * languageMode is now ignored, overlay is `false`, so this test is the same
     * as countReturnsCorrectNumberOfPostsInEnglishLanguage
     *
     * @test
     */
    public function countReturnsCorrectNumberOfPostsInEnglishLanguageForStrictMode()
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);
        $querySettings->setLanguageMode('strict');
        $this->assertFalse($querySettings->getLanguageOverlayMode());

        $postCount = $query->execute()->count();
        $this->assertSame(2, $postCount);
    }

    /**
     * Test for fetching records with disabled overlay
     *
     * @test
     */
    public function countReturnsCorrectNumberOfPostsInEnglishLanguage()
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);
        $this->assertFalse($querySettings->getLanguageOverlayMode());
        $this->assertNull($querySettings->getLanguageMode());

        $postCount = $query->execute()->count();
        $this->assertSame(3, $postCount);
    }

    /**
     * @test
     */
    public function countReturnsCorrectNumberOfPostsInGreekLanguage()
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        $this->assertFalse($querySettings->getLanguageOverlayMode());
        $this->assertNull($querySettings->getLanguageMode());

        $postCount = $query->execute()->count();

        $this->assertSame(3, $postCount);
    }

    /**
     * @test
     */
    public function fetchingPostsReturnsEnglishPostsWithFallback()
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);
        $this->assertFalse($querySettings->getLanguageOverlayMode());
        $this->assertNull($querySettings->getLanguageMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        $this->assertCount(3, $posts);
        $this->assertSame('A EN:Post2', $posts[0]->getTitle());
        $this->assertSame('B EN:Post1', $posts[1]->getTitle());
        $this->assertSame('Post3', $posts[2]->getTitle());
    }

    /**
     * This tests shows overlays in action
     *
     * @test
     */
    public function fetchingPostsReturnsGreekPostsWithFallback()
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        $this->assertFalse($querySettings->getLanguageOverlayMode());
        $this->assertNull($querySettings->getLanguageMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        $this->assertCount(3, $posts);
        $this->assertSame('GR:Post1', $posts[0]->getTitle());
        $this->assertSame('Post2', $posts[1]->getTitle());
        $this->assertSame('Post3', $posts[2]->getTitle());
    }

    /**
     * This tests shows overlay 'hideNonTranslated' in action
     *
     * @test
     */
    public function fetchingPostsReturnsGreekPostsWithHideNonTranslated()
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        $querySettings->setLanguageOverlayMode('hideNonTranslated');
        $this->assertNull($querySettings->getLanguageMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        $this->assertCount(3, $posts);
        $this->assertSame('GR:Post1', $posts[0]->getTitle());
        $this->assertSame('Post2', $posts[1]->getTitle());
        $this->assertSame('Post3', $posts[2]->getTitle());
    }

    /**
     * @test
     */
    public function orderingByTitleRespectsEnglishTitles()
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);
        $this->assertFalse($querySettings->getLanguageOverlayMode());
        $this->assertNull($querySettings->getLanguageMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        $this->assertCount(3, $posts);
        $this->assertSame('A EN:Post2', $posts[0]->getTitle());
        $this->assertSame('B EN:Post1', $posts[1]->getTitle());
        $this->assertSame('Post3', $posts[2]->getTitle());
    }

    /**
     * This test checks whether setIgnoreEnableFields(true) affects the query
     * It's expected that when ignoring enable fields, the hidden record is also returned.
     * This is related to https://forge.typo3.org/issues/68672
     *
     * @test
     */
    public function fetchingHiddenPostsWithIgnoreEnableField()
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setLanguageUid(0);
        //we need it to have stable results on pgsql
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[] $posts */
        $posts = $query->execute()->toArray();

        $this->assertCount(4, $posts);
        $this->assertSame('Post10', $posts[3]->getTitle());
    }

    /**
     * This test checks whether setIgnoreEnableFields(true) affects translated record too.
     * It's expected that when ignoring enable fields, the hidden translated record is shown.
     * This is related to https://forge.typo3.org/issues/68672
     *
     * This tests documents current, buggy behaviour!
     *
     * @test
     */
    public function fetchingHiddenPostsReturnsHiddenOverlay()
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setLanguageUid(2);
        $this->assertFalse($querySettings->getLanguageOverlayMode());
        //we need it to have stable results on pgsql
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[] $posts */
        $posts = $query->execute()->toArray();

        $this->assertCount(4, $posts);
        //This assertion is wrong, once the issue https://forge.typo3.org/issues/68672
        //is fixed, the following assertion should be used.
        $this->assertSame('Post10', $posts[3]->getTitle());
        //$this->assertSame('GR:Post10', $posts[3]->getTitle());
    }

    /**
     * Test checking if we can query db records by translated fields
     *
     * @test
     */
    public function fetchingTranslatedPostByTitle()
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        $this->assertFalse($querySettings->getLanguageOverlayMode());
        $this->assertNull($querySettings->getLanguageMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('title', 'GR:Post1'));
        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();
        $this->assertCount(1, $posts);
        $this->assertSame('GR:Post1', $posts[0]->getTitle());
    }

    /**
     * @test
     */
    public function fetchingPostByTagName()
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(0);
        $this->assertFalse($querySettings->getLanguageOverlayMode());
        $this->assertNull($querySettings->getLanguageMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('tags.name', 'Tag1'));
        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();
        $this->assertCount(3, $posts);
        $this->assertSame('Post1', $posts[0]->getTitle());
    }

    /**
     * @test
     */
    public function fetchingTranslatedPostByTagName()
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);
        $this->assertFalse($querySettings->getLanguageOverlayMode());
        $this->assertNull($querySettings->getLanguageMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('tags.name', 'Tag1'));
        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();
        $this->assertCount(3, $posts);
        $this->assertSame('A EN:Post2', $posts[0]->getTitle());
        $this->assertSame('Post3', $posts[2]->getTitle());
    }
}
