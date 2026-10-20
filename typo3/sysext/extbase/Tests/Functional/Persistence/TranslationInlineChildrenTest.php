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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Inline children of translations: the copies of excluded children that DataHandler gives a translation, and the
 * language of new children. Since the bugfix https://forge.typo3.org/issues/103970 (Gerrit change 87595), DataHandler
 * aligns the language of inline and file children with the language stored for their parent, so a new child gets the
 * language of its parent, even when the submitted data has no language for it.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (DataHandler, suffix
 * "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair start from the same fixture in
 * Fixtures/TranslationInlineChildren/Given/ and assert the database against the same result CSV in
 * Fixtures/TranslationInlineChildren/. An Extbase test that differs from core is skipped.
 *
 * The DataHandler side sends what the backend form sends: the uids of the inline children as a comma separated list in
 * the inline field of the parent, and new children as NEW... records with their `pid` and title, listed in that field
 * but without a language.
 *
 * A post is translatable and has comments (inline, `comments`, excluded from translation, `origUid` `t3_origuid`); the
 * table of the comments has no language. A blog is translatable and has posts (inline, `posts`, with the foreign field
 * `blog`) and a file field `logo`. The site has the languages English (0), German (1) and French (2).
 */
final class TranslationInlineChildrenTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
        'FR' => ['id' => 2, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/Given/Base.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
                $this->buildLanguageConfiguration('FR', '/fr/'),
            ],
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
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
     * The title of the English post 1 "Hello world" is changed to "Changed post" through DataHandler and its comments
     * are sent as the backend form sends them: the field `comments` holds "1,2". The German translation 2 has no copies
     * yet. DataHandler creates the comments 3 and 4 as copies of the comments 1 and 2: they belong to the German post 2
     * and carry `t3_origuid` 1 and 2, and DataHandler writes the number of comments, 2, into the field `comments` of
     * post 2.
     *
     * @see savingTheDefaultRecordGivesTheTranslationItsOwnCopiesByExtbase()
     */
    #[Test]
    public function savingTheDefaultRecordGivesTheTranslationItsOwnCopiesByCore(): void
    {
        // Given: post 1 has the comments 1 and 2 and the German translation 2 without copies
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/Given/OneTranslation.csv');

        // When: the title of post 1 is changed and its comments are sent as the form sends them
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['title' => 'Changed post', 'comments' => '1,2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the German translation has its own copies of both comments
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/CopiedForOneTranslation.csv');
    }

    /**
     * Extbase reads post 1 through the repository, changes the `title` to "Changed post" and persists it with
     * `update()` and `persistAll()`. Extbase writes the new title into post 1, but does not touch the German
     * translation: it creates no copies of the comments, the comments 1 and 2 stay the only comments and the field
     * `comments` of post 2 stays 0.
     *
     * @see savingTheDefaultRecordGivesTheTranslationItsOwnCopiesByCore()
     */
    #[Test]
    public function savingTheDefaultRecordGivesTheTranslationItsOwnCopiesByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it creates no copies of the comments for the German translation, so the translation keeps no comments.');

        // Given: post 1 has the comments 1 and 2 and the German translation 2 without copies
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/Given/OneTranslation.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the title of post 1 is changed and the post is persisted
        $post->setTitle('Changed post');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the German translation has its own copies of both comments
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/CopiedForOneTranslation.csv');
    }

    /**
     * The title of the English post 1 "Hello world" is changed to "Changed post" through DataHandler and its comments
     * are sent as the backend form sends them: the field `comments` holds "1,2". The German translation 2 and the
     * French translation 3 have no copies yet. DataHandler creates only one set of copies, the comments 3 and 4 with
     * `t3_origuid` 1 and 2, and attaches it to the French post 3. The German post 2 gets no copy, although the field
     * `comments` of post 2 holds 2, like that of post 3. This pins a bug in DataHandler, see
     * https://forge.typo3.org/issues/111069.
     *
     * @see savingTheDefaultRecordWithTwoTranslationsCopiesOnlyForTheLastOneByExtbase()
     */
    #[Test]
    public function savingTheDefaultRecordWithTwoTranslationsCopiesOnlyForTheLastOneByCore(): void
    {
        // Given: post 1 has the comments 1 and 2 and the translations 2 (German) and 3 (French) without copies
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/Given/TwoTranslations.csv');

        // When: the title of post 1 is changed and its comments are sent as the form sends them
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [1 => ['title' => 'Changed post', 'comments' => '1,2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and only the French translation has copies of the comments
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/CopiedForLastTranslation.csv');
    }

    /**
     * Extbase reads post 1 through the repository, changes the `title` to "Changed post" and persists it with
     * `update()` and `persistAll()`. Extbase writes the new title into post 1, but does not touch the translations: it
     * creates no copies of the comments for the German post 2 or the French post 3, the comments 1 and 2 stay the only
     * comments and the field `comments` of both translations stays 0.
     *
     * @see savingTheDefaultRecordWithTwoTranslationsCopiesOnlyForTheLastOneByCore()
     */
    #[Test]
    public function savingTheDefaultRecordWithTwoTranslationsCopiesOnlyForTheLastOneByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it creates no copies of the comments for either translation, while core creates one set for the last translation.');

        // Given: post 1 has the comments 1 and 2 and the translations 2 (German) and 3 (French) without copies
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/Given/TwoTranslations.csv');
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the title of post 1 is changed and the post is persisted
        $post->setTitle('Changed post');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: only the French translation has copies of the comments
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/CopiedForLastTranslation.csv');
    }

    /**
     * A new post "Beitrag" is sent as a child of the German blog 2 through DataHandler, as the backend form sends it:
     * the field `posts` of blog 2 holds `NEW1` and the post is a NEW record with its `pid` 2 and its title, without a
     * language and without a value in its field `blog`. DataHandler creates post 1 with the language 1 of blog 2,
     * writes `l18n_parent` 0 and the number of posts, 1, into the field `posts` of blog 2.
     *
     * @see aNewChildOfATranslationGetsTheLanguageOfTheTranslationByExtbase()
     */
    #[Test]
    public function aNewChildOfATranslationGetsTheLanguageOfTheTranslationByCore(): void
    {
        // Given: blog 1 is English, its German translation 2 has no posts
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/Given/TranslatedBlog.csv');

        // When: a new post is sent as a child of blog 2 without a language
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_blog' => [2 => ['posts' => 'NEW1']],
                'tx_blogexample_domain_model_post' => ['NEW1' => ['pid' => 2, 'title' => 'Beitrag']],
            ],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the post has the language of blog 2
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/NewChildOfTranslation.csv');
    }

    /**
     * Extbase reads the German blog through the repository (language aspect German, so `findByUid(1)` returns the
     * translation 2), adds a new post "Beitrag" and persists it with `update()` and `persistAll()`. Extbase creates
     * post 1 with blog 2, the title "Beitrag" and the number of posts, 1, in the field `posts` of blog 2, but with the
     * language 0: the new post does not take the language 1 of the translation.
     *
     * @see aNewChildOfATranslationGetsTheLanguageOfTheTranslationByCore()
     */
    #[Test]
    public function aNewChildOfATranslationGetsTheLanguageOfTheTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it creates the new post with the language 0 instead of the language 1 of the German blog.');

        // Given: blog 1 is English, its German translation 2 has no posts
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/Given/TranslatedBlog.csv');
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: a new post is added to the German blog and persisted
        $post = new Post();
        $post->setTitle('Beitrag');
        $blog->addPost($post);
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the post has the language of blog 2
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/NewChildOfTranslation.csv');
    }

    /**
     * A new file reference is sent as a child of the German blog 2 through DataHandler, as the backend form sends it:
     * the field `logo` of blog 2 holds `NEW1` and the reference is a NEW `sys_file_reference` with its `pid` 2,
     * `uid_local` 1 and the uid 2 of the blog as `uid_foreign`, without a language. DataHandler creates file reference
     * 1 with `tablenames`, `fieldname` logo, `sorting_foreign` 1 and the language 1 of blog 2, and writes the number of
     * references, 1, into the field `logo` of blog 2.
     *
     * @see aNewFileReferenceOfATranslationGetsTheLanguageOfTheTranslationByExtbase()
     */
    #[Test]
    public function aNewFileReferenceOfATranslationGetsTheLanguageOfTheTranslationByCore(): void
    {
        // Given: blog 1 is English, its German translation 2 has no file references
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/Given/TranslatedBlogWithFile.csv');

        // When: a new file reference is sent as a child of blog 2 without a language
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_blog' => [2 => ['logo' => 'NEW1']],
                'sys_file_reference' => ['NEW1' => ['pid' => 2, 'uid_local' => 1, 'uid_foreign' => 2]],
            ],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the file reference has the language of blog 2
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/NewFileReferenceOfTranslation.csv');
    }

    /**
     * Extbase reads the German blog through the repository (language aspect German, so `findByUid(1)` returns the
     * translation 2), attaches a new Extbase file reference for the file 1 to the field `logo` and persists it with
     * `update()` and `persistAll()`. Extbase creates file reference 1 with `tablenames`, `fieldname` logo,
     * `uid_foreign` 2 and `sorting_foreign` 1 and writes the number of references, 1, into the field `logo` of blog 2,
     * but with the language 0: the new file reference does not take the language 1 of the translation.
     *
     * @see aNewFileReferenceOfATranslationGetsTheLanguageOfTheTranslationByCore()
     */
    #[Test]
    public function aNewFileReferenceOfATranslationGetsTheLanguageOfTheTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it creates the new file reference with the language 0 instead of the language 1 of the German blog.');

        // Given: blog 1 is English, its German translation 2 has no file references
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/Given/TranslatedBlogWithFile.csv');
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: a new file reference is attached to the German blog and persisted
        $reference = new FileReference();
        $reference->setOriginalResource(
            $this->get(ResourceFactory::class)->createFileReferenceObject(['uid_local' => 1, 'uid_foreign' => 2, 'uid' => uniqid('NEW_')])
        );
        $blog->getLogo()->attach($reference);
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the file reference has the language of blog 2
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/NewFileReferenceOfTranslation.csv');
    }

    /**
     * A new German blog "Mein Blog" (`sys_language_uid` 1, no `l18n_parent`) and a new post "Beitrag" are sent in one
     * data map through DataHandler: the field `posts` of the blog holds `NEW2` and the post is a NEW record with its
     * `pid` 2 and its title, without a language and without a value in its field `blog`. DataHandler creates blog 1 and
     * post 1, gives the post the language 1 of the blog and writes the number of posts, 1, into the field `posts` of
     * the blog.
     *
     * @see aNewRecordInALanguageWithANewChildCreatesTheChildInThatLanguageByExtbase()
     */
    #[Test]
    public function aNewRecordInALanguageWithANewChildCreatesTheChildInThatLanguageByCore(): void
    {
        // When: a new German blog and a new post are sent together
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_blog' => ['NEW1' => ['pid' => 2, 'sys_language_uid' => 1, 'title' => 'Mein Blog', 'posts' => 'NEW2']],
                'tx_blogexample_domain_model_post' => ['NEW2' => ['pid' => 2, 'title' => 'Beitrag']],
            ],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and blog and post have the language 1
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/NewRecordWithNewChild.csv');
    }

    /**
     * Extbase creates a new blog "Mein Blog", sets its language to German through the property `"_languageUid"`, adds a
     * new post "Beitrag" without a language and persists the blog with `add()` and `persistAll()`. Extbase creates blog
     * 1 with the language 1 and the number of posts, 1, and post 1 for blog 1, but with the language 0: the new post
     * does not take the language of its new parent.
     *
     * @see aNewRecordInALanguageWithANewChildCreatesTheChildInThatLanguageByCore()
     */
    #[Test]
    public function aNewRecordInALanguageWithANewChildCreatesTheChildInThatLanguageByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it creates the new post with the language 0 instead of the language 1 of the new German blog.');

        // When: a new German blog with a new post is added and persisted
        // @phpstan-ignore deadCode.unreachable
        $blog = new Blog();
        $blog->_setProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID, 1);
        $blog->setTitle('Mein Blog');
        $post = new Post();
        $post->setTitle('Beitrag');
        $blog->addPost($post);
        $this->get(BlogRepository::class)->add($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: blog and post have the language 1
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationInlineChildren/NewRecordWithNewChild.csv');
    }
}
