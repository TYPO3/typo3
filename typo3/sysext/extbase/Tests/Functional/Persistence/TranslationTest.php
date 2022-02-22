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

use ExtbaseTeam\BlogExample\Domain\Model\Post;
use ExtbaseTeam\BlogExample\Domain\Repository\PostRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TranslationTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var PostRepository
     */
    protected $postRepository;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
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
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/blogs.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/translated-posts.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/post-tag-mm.csv');
        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/tags.csv');

        $this->setUpBasicFrontendEnvironment();

        $this->postRepository = $this->get(PostRepository::class);
        // ConfigurationManager is used by PersistenceManager to retrieve configuration.
        // We set a proper extensionName and pluginName for the ConfigurationManager singleton
        // here, to not run into warnings due to incomplete test setup.
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration([
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
    }

    /**
     * Minimal frontend environment to satisfy Extbase Typo3DbBackend
     */
    protected function setUpBasicFrontendEnvironment(): void
    {
        // in v9 overlay and language mode has different default values, so we need to set them here explicitly
        // to match v8 behaviour
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF, ['off']));

        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $pageRepositoryFixture = new PageRepository();
        $frontendControllerMock = $this->createMock(TypoScriptFrontendController::class);
        $frontendControllerMock->sys_page = $pageRepositoryFixture;
        $GLOBALS['TSFE'] = $frontendControllerMock;
    }

    /**
     * Tests if repository returns correct number of posts in the default language
     *
     * @test
     */
    public function countReturnsCorrectNumberOfPosts(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(0);

        self::assertFalse($querySettings->getLanguageOverlayMode());

        $postCount = $query->execute()->count();
        self::assertSame(4, $postCount);
    }

    /**
     * This test shows the difference between old and new rendering
     * languageMode is now ignored, overlay is `false`, so this test is the same
     * as countReturnsCorrectNumberOfPostsInEnglishLanguage
     *
     * @test
     */
    public function countReturnsCorrectNumberOfPostsInEnglishLanguageForStrictMode(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);
        self::assertFalse($querySettings->getLanguageOverlayMode());

        $postCount = $query->execute()->count();
        self::assertSame(2, $postCount);
    }

    /**
     * Test for fetching records with disabled overlay
     *
     * @test
     */
    public function countReturnsCorrectNumberOfPostsInEnglishLanguage(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);

        self::assertFalse($querySettings->getLanguageOverlayMode());

        $postCount = $query->execute()->count();
        self::assertSame(2, $postCount);
    }

    /**
     * @test
     */
    public function countReturnsCorrectNumberOfPostsInGreekLanguage(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        self::assertFalse($querySettings->getLanguageOverlayMode());

        $postCount = $query->execute()->count();

        self::assertSame(2, $postCount);
    }

    /**
     * @test
     */
    public function fetchingPostsReturnsEnglishPostsWithFallback(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);
        self::assertFalse($querySettings->getLanguageOverlayMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        self::assertCount(2, $posts);
        self::assertSame('A EN:Post2', $posts[0]->getTitle());
        self::assertSame('B EN:Post1', $posts[1]->getTitle());
    }

    /**
     * @test
     */
    public function fetchingPostsByInClauseReturnsDefaultPostsWithFallback(): void
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(false);
        $querySettings->setLanguageOverlayMode(true);
        $querySettings->setLanguageUid(2);

        $query->matching($query->in('uid', [4]));
        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        self::assertCount(1, $posts);
        self::assertSame('Post2', $posts[0]->getTitle());
    }

    /**
     * This tests shows overlays in action
     *
     * @test
     */
    public function fetchingPostsReturnsGreekPostsWithFallback(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        self::assertFalse($querySettings->getLanguageOverlayMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        self::assertCount(2, $posts);
        self::assertSame('GR:Post1', $posts[0]->getTitle());
        self::assertSame('GR:Post11', $posts[1]->getTitle());
    }

    /**
     * This tests shows overlay 'hideNonTranslated' in action
     *
     * @test
     */
    public function fetchingPostsReturnsGreekPostsWithHideNonTranslated(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        $querySettings->setLanguageOverlayMode('hideNonTranslated');

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        self::assertCount(2, $posts);
        self::assertSame('GR:Post1', $posts[0]->getTitle());
        self::assertSame('GR:Post11', $posts[1]->getTitle());
    }

    public function fetchingTranslatedPostByUidDataProvider(): array
    {
        return [
            'with one id' => [
                'input' => [12],
                'expectedTitles' => ['GR:Post11'],
            ],
            'with two ids' => [
                'input' => [12, 1],
                'expectedTitles' => ['GR:Post11', 'GR:Post1'],
            ],
        ];
    }

    /**
     * @dataProvider fetchingTranslatedPostByUidDataProvider
     * @test
     */
    public function fetchingTranslatedPostByInClauseWithStrictLanguageSettings(array $input, array $expectedTitles): void
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        $querySettings->setLanguageOverlayMode('hideNonTranslated');

        $query->matching($query->in('uid', $input));
        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        // @todo: wrong assertion
        // We're simulating a strict language configuration where a blog post (uid=12 or uid=1) has been translated to another
        // language. However, Extbase is not able to find the translated record via ->in() and therefore returns an
        // empty result set. This will be fixed with https://review.typo3.org/c/Packages/TYPO3.CMS/+/67893
        self::assertCount(0, $posts);
        // self::assertCount(count($expectedTitles), $posts);
        // self::assertEqualsCanonicalizing($expectedTitles, array_map(static function(Post $post) { return $post->getTitle(); }, $posts));
    }

    /**
     * @dataProvider fetchingTranslatedPostByUidDataProvider
     * @test
     */
    public function fetchingTranslatedPostByEqualsUidClauseWithStrictLanguageSettings(array $input, array $expectedTitles): void
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        $querySettings->setLanguageOverlayMode('hideNonTranslated');

        $constraints = [];
        foreach ($input as $uid) {
            $constraints[] = $query->equals('uid', $uid);
        }
        $query->matching($query->logicalOr(...$constraints));
        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        // @todo: wrong assertion
        // We're simulating a strict language configuration where a blog post (uid=12 or uid=1) has been translated to another
        // language. However, Extbase is not able to find the translated record via ->equals(uid=12 OR uid=1 OR ...) and therefore returns an
        // empty result set. This will be fixed with https://review.typo3.org/c/Packages/TYPO3.CMS/+/67893
        self::assertCount(0, $posts);
        // self::assertCount(count($expectedTitles), $posts);
        // self::assertEqualsCanonicalizing($expectedTitles, array_map(static function(Post $post) { return $post->getTitle(); }, $posts));
    }

    /**
     * @test
     */
    public function orderingByTitleRespectsEnglishTitles(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);
        self::assertFalse($querySettings->getLanguageOverlayMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        self::assertCount(2, $posts);
        self::assertSame('A EN:Post2', $posts[0]->getTitle());
        self::assertSame('B EN:Post1', $posts[1]->getTitle());
    }

    /**
     * This test shows that ordering by blog title works
     * however the default language blog title is used
     *
     * @test
     */
    public function orderingByBlogTitle(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);
        self::assertFalse($querySettings->getLanguageOverlayMode());

        $query->setOrderings([
            'blog.title' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);

        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();

        self::assertCount(2, $posts);
        self::assertSame('B EN:Post1', $posts[0]->getTitle());
        self::assertSame('A EN:Post2', $posts[1]->getTitle());
    }

    /**
     * This test checks whether setIgnoreEnableFields(true) affects the query
     * It's expected that when ignoring enable fields, the hidden record is also returned.
     * This is related to https://forge.typo3.org/issues/68672
     *
     * @test
     */
    public function fetchingHiddenPostsWithIgnoreEnableField(): void
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

        self::assertCount(5, $posts);
        self::assertSame('Post10', $posts[3]->getTitle());
    }

    /**
     * This test checks whether setIgnoreEnableFields(true) affects translated record too.
     * It's expected that when ignoring enable fields, the hidden translated record is shown.
     *
     * @test
     */
    public function fetchingHiddenPostsReturnsHiddenOverlay(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setLanguageUid(2);
        self::assertFalse($querySettings->getLanguageOverlayMode());
        //we need it to have stable results on pgsql
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[] $posts */
        $posts = $query->execute()->toArray();

        self::assertCount(3, $posts);

        self::assertSame('GR:Post1', $posts[0]->getTitle());
        self::assertSame('GR:Post10', $posts[1]->getTitle());
        self::assertSame('GR:Post11', $posts[2]->getTitle());
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
    public function fetchingHiddenPostsReturnsHiddenOverlayOverlayEnabled(): void
    {
        $query = $this->postRepository->createQuery();

        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setLanguageUid(2);
        $querySettings->setLanguageOverlayMode(true);
        //we need it to have stable results on pgsql
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        /** @var Post[] $posts */
        $posts = $query->execute()->toArray();

        self::assertCount(5, $posts);

        self::assertSame('GR:Post1', $posts[0]->getTitle());
        self::assertSame('Post2', $posts[1]->getTitle());
        self::assertSame('Post3', $posts[2]->getTitle());
        // once the issue is fixed this assertions should be GR:Post10
        self::assertSame('Post10', $posts[3]->getTitle());
        self::assertSame('GR:Post11', $posts[4]->getTitle());
    }

    /**
     * Test checking if we can query db records by translated fields
     *
     * @test
     */
    public function fetchingTranslatedPostByTitle(): void
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        self::assertFalse($querySettings->getLanguageOverlayMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('title', 'GR:Post1'));
        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();
        self::assertCount(1, $posts);
        self::assertSame('GR:Post1', $posts[0]->getTitle());
    }

    /**
     * Test checking if we can query db records by value of the child object
     * Note that only child objects from language 0 are taken into account
     *
     * @test
     */
    public function fetchingTranslatedPostByBlogTitle(): void
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setStoragePageIds([1]);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(2);
        self::assertFalse($querySettings->getLanguageOverlayMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('blog.title', 'Blog1'));
        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();
        self::assertCount(2, $posts);
        self::assertSame('GR:Post1', $posts[0]->getTitle());
        self::assertSame('GR:Post11', $posts[1]->getTitle());
    }

    /**
     * @test
     */
    public function fetchingPostByTagName(): void
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(0);
        self::assertFalse($querySettings->getLanguageOverlayMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('tags.name', 'Tag1'));
        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();
        self::assertCount(3, $posts);
        self::assertSame('Post1', $posts[0]->getTitle());
    }

    /**
     * @test
     */
    public function fetchingTranslatedPostByTagName(): void
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $querySettings->setRespectSysLanguage(true);
        $querySettings->setLanguageUid(1);
        self::assertFalse($querySettings->getLanguageOverlayMode());

        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('tags.name', 'Tag1'));
        /** @var Post[]|array $posts */
        $posts = $query->execute()->toArray();
        self::assertCount(2, $posts);
        self::assertSame('A EN:Post2', $posts[0]->getTitle());
        self::assertCount(1, $posts[0]->getTags());
        self::assertSame('B EN:Post1', $posts[1]->getTitle());
        self::assertCount(2, $posts[1]->getTags());
    }
}
