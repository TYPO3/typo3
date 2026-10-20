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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Category;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Reading categories: the categories of a post, also of a translated post.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (PageRepository and
 * RecordFactory, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair read the fixture
 * Fixtures/CategoryRead/Given.csv and compare the titles of the categories in the order that the relation returns
 * them. An Extbase test that differs from core is skipped.
 *
 * The storage page 2 holds the categories News (1), Tech (2) and Sports (3) with the German translations
 * "Nachrichten" (4) of News and "Technik" (5) of Tech; Sports has no translation. Post 1 "Hello world" has the
 * categories Tech, News and Sports in this order, its German translation, post 2 "Hallo Welt", has the same relation
 * rows to the default categories, as DataHandler writes them when it translates a post.
 */
final class CategoryReadTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CategoryRead/Given.csv');
        $this->writeSiteConfiguration(
            'blog',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ]
        );
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => 2],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
    }

    /**
     * Post 1 "Hello world" is read in English. The core returns its categories in the order of the relation rows, by
     * `sorting_foreign`: "Tech", "News" and "Sports".
     *
     * @see readingTheCategoriesOfARecordByExtbase()
     */
    #[Test]
    public function readingTheCategoriesOfARecordByCore(): void
    {
        // Given: post 1 is read in English
        $languageAspect = new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON);
        $this->get(Context::class)->setAspect('language', $languageAspect);
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $row = $this->get(PageRepository::class)->getLanguageOverlay('tx_blogexample_domain_model_post', $row, $languageAspect);
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);

        // When: its categories are read
        $titles = array_map(
            static fn(Record $category): string => $category->get('title'),
            iterator_to_array($record->get('categories'), false)
        );

        // Then: they come in the order of the relation rows
        self::assertSame(['Tech', 'News', 'Sports'], $titles);
    }

    /**
     * Post 1 "Hello world" is read in English. Extbase returns the same categories in the same order as the core:
     * "Tech", "News" and "Sports".
     *
     * @see readingTheCategoriesOfARecordByCore()
     */
    #[Test]
    public function readingTheCategoriesOfARecordByExtbase(): void
    {
        // Given: post 1 is read in English
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: its categories are read
        $titles = array_map(static fn(Category $category): string => $category->getTitle(), $post->getCategories()->toArray());

        // Then: they come in the order of the relation rows
        self::assertSame(['Tech', 'News', 'Sports'], $titles);
    }

    /**
     * Post 1 "Hello world" is read in German with overlays on. The core overlays the post with its German translation,
     * post 2 "Hallo Welt", and finds the relation rows of the translation, which point to the default categories. Each
     * category is overlaid with its German translation, so it returns "Technik" and "Nachrichten"; the category Sports
     * has no German translation and is dropped.
     *
     * @see readingTheCategoriesOfATranslationWithOverlaysOnByExtbase()
     */
    #[Test]
    public function readingTheCategoriesOfATranslationWithOverlaysOnByCore(): void
    {
        // Given: post 1 is read in German with overlays on
        $languageAspect = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON);
        $this->get(Context::class)->setAspect('language', $languageAspect);
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $row = $this->get(PageRepository::class)->getLanguageOverlay('tx_blogexample_domain_model_post', $row, $languageAspect);
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);

        // When: its categories are read
        $titles = array_map(
            static fn(Record $category): string => $category->get('title'),
            iterator_to_array($record->get('categories'), false)
        );

        // Then: the translated categories are returned
        self::assertSame(['Technik', 'Nachrichten'], $titles);
    }

    /**
     * Post 1 "Hello world" is read in German with overlays on. Extbase returns the same categories as the core:
     * "Technik" and "Nachrichten".
     *
     * @see readingTheCategoriesOfATranslationWithOverlaysOnByCore()
     */
    #[Test]
    public function readingTheCategoriesOfATranslationWithOverlaysOnByExtbase(): void
    {
        // Given: post 1 is read in German with overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: its categories are read
        $titles = array_map(static fn(Category $category): string => $category->getTitle(), $post->getCategories()->toArray());

        // Then: the translated categories are returned
        self::assertSame(['Technik', 'Nachrichten'], $titles);
    }

    /**
     * Post 1 "Hello world" is read in German with overlays mixed. The core overlays the post with its German
     * translation, post 2 "Hallo Welt", overlays each category with its German translation and keeps the category
     * without translation in English: "Technik", "Nachrichten" and "Sports".
     *
     * @see readingTheCategoriesOfATranslationWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function readingTheCategoriesOfATranslationWithOverlaysMixedByCore(): void
    {
        // Given: post 1 is read in German with overlays mixed
        $languageAspect = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED);
        $this->get(Context::class)->setAspect('language', $languageAspect);
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $row = $this->get(PageRepository::class)->getLanguageOverlay('tx_blogexample_domain_model_post', $row, $languageAspect);
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);

        // When: its categories are read
        $titles = array_map(
            static fn(Record $category): string => $category->get('title'),
            iterator_to_array($record->get('categories'), false)
        );

        // Then: the translated categories are returned, Sports stays English
        self::assertSame(['Technik', 'Nachrichten', 'Sports'], $titles);
    }

    /**
     * Post 1 "Hello world" is read in German with overlays mixed. Extbase returns the same categories as the core:
     * "Technik", "Nachrichten" and "Sports".
     *
     * @see readingTheCategoriesOfATranslationWithOverlaysMixedByCore()
     */
    #[Test]
    public function readingTheCategoriesOfATranslationWithOverlaysMixedByExtbase(): void
    {
        // Given: post 1 is read in German with overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: its categories are read
        $titles = array_map(static fn(Category $category): string => $category->getTitle(), $post->getCategories()->toArray());

        // Then: the translated categories are returned, Sports stays English
        self::assertSame(['Technik', 'Nachrichten', 'Sports'], $titles);
    }
}
