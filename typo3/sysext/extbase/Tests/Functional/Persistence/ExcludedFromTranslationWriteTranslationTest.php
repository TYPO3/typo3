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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Comment;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Writes through a translation: excluded fields are dropped, translatable fields and the
 * translation's own children are kept.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * assert the database against the same result CSV below
 * Fixtures/ExcludedFromTranslation/WriteTranslation/; NothingChanged.csv is the unchanged state of
 * the fixture and shared by every scenario whose change is dropped. The oracle tests describe
 * correct behaviour and must be green; an Extbase test that differs from core is skipped with a
 * reference to the issue.
 *
 * Fixture WriteTranslation.csv: post 1 [en] with 2 [de] and 3 [fr] (tags and info), post 7 [en]
 * with 8 [de] (comments).
 */
final class ExcludedFromTranslationWriteTranslationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation.csv');
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
     * The author of the German translation is changed from Anna to Ben through DataHandler. The
     * author is excluded from translation, so DataHandler drops the value and nothing changes.
     *
     * @see authorChangedThroughATranslationIsDroppedByExtbase()
     */
    #[Test]
    public function authorChangedThroughATranslationIsDroppedByCore(): void
    {
        // When: the author of the German translation is set to Ben (uid 2)
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['author' => 2]]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and nothing has changed
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/NothingChanged.csv');
    }

    /**
     * Extbase writes the new author to the German translation.
     *
     * @see authorChangedThroughATranslationIsDroppedByCore()
     */
    #[Test]
    public function authorChangedThroughATranslationIsDroppedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the German translation of the post is read with overlays mixed, together with the person Ben
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);
        $ben = $this->get(PersonRepository::class)->findOneBy(['firstname' => 'Ben']);

        // When: the author of the German translation is set to Ben and persisted
        $post->setAuthor($ben);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: nothing has changed
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/NothingChanged.csv');
    }

    /**
     * The date of the German translation is changed from 2026-01-15 to 2026-02-20 through
     * DataHandler. The date is excluded from translation, so DataHandler drops the value and
     * nothing changes.
     *
     * @see dateChangedThroughATranslationIsDroppedByExtbase()
     */
    #[Test]
    public function dateChangedThroughATranslationIsDroppedByCore(): void
    {
        // When: the date of the German translation is set to 2026-02-20
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['date' => new \DateTimeImmutable('2026-02-20 12:00:00', new \DateTimeZone('UTC'))->getTimestamp()]]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and nothing has changed
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/NothingChanged.csv');
    }

    /**
     * Extbase writes the new date to the German translation.
     *
     * @see dateChangedThroughATranslationIsDroppedByCore()
     */
    #[Test]
    public function dateChangedThroughATranslationIsDroppedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the German translation of the post is read with overlays mixed
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the date of the German translation is set to 2026-02-20 and persisted
        $post->setDate(new \DateTime('2026-02-20 12:00:00', new \DateTimeZone('UTC')));
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: nothing has changed
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/NothingChanged.csv');
    }

    /**
     * The title of the German translation is changed through DataHandler. The title is not
     * excluded from translation, so only the German translation gets the new title; the English
     * post and the French translation keep theirs.
     *
     * @see translatableValueChangedThroughATranslationChangesOnlyThatTranslationByExtbase()
     */
    #[Test]
    public function translatableValueChangedThroughATranslationChangesOnlyThatTranslationByCore(): void
    {
        // When: the title of the German translation is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['title' => 'Guten Tag Welt']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and only the German translation has the new title
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/TitleChanged.csv');
    }

    /**
     * @see translatableValueChangedThroughATranslationChangesOnlyThatTranslationByCore()
     */
    #[Test]
    public function translatableValueChangedThroughATranslationChangesOnlyThatTranslationByExtbase(): void
    {
        // Given: the German translation of the post is read with overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the title of the German translation is changed and persisted
        $post->setTitle('Guten Tag Welt');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: only the German translation has the new title
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/TitleChanged.csv');
    }

    /**
     * The English post "Third post" has the comments "Nice read" and "Well written", and its German
     * translation owns a copy of each. A comment "Thanks" is added through the German translation
     * with DataHandler. The comments are excluded from translation, so DataHandler drops the new
     * comment and nothing changes.
     *
     * @see commentAddedThroughTheTranslationIsDroppedByExtbase()
     */
    #[Test]
    public function commentAddedThroughTheTranslationIsDroppedByCore(): void
    {
        // When: the comment "Thanks" is added to the German translation
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_post' => [8 => ['comments' => '3,4,NEW1']],
                'tx_blogexample_domain_model_comment' => [
                    'NEW1' => ['pid' => 2, 'author' => 'Max', 'email' => 'max@example.com', 'content' => 'Thanks'],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the new comment is not in the database
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/CommentAdded.csv');
    }

    /**
     * Extbase adds the comment to the German translation.
     *
     * @see commentAddedThroughTheTranslationIsDroppedByCore()
     */
    #[Test]
    public function commentAddedThroughTheTranslationIsDroppedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the German translation of the post is read with overlays mixed
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(7);

        // When: the comment "Thanks" is added to the German translation and persisted
        $comment = new Comment();
        $comment->setAuthor('Max');
        $comment->setEmail('max@example.com');
        $comment->setContent('Thanks');
        $post->addComment($comment);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the new comment is not in the database
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/CommentAdded.csv');
    }

    /**
     * The German translation of "Third post" owns copies of the comments "Nice read" and "Well
     * written". The copy of "Nice read" is deleted through the German translation with DataHandler.
     * Only that copy is deleted; the comments of the English post stay.
     *
     * @see commentRemovedThroughTheTranslationRemovesOnlyTheCopyOfTheTranslationByExtbase()
     */
    #[Test]
    public function commentRemovedThroughTheTranslationRemovesOnlyTheCopyOfTheTranslationByCore(): void
    {
        // When: the copy of the comment "Nice read" is deleted from the German translation
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [8 => ['comments' => '4']]], ['tx_blogexample_domain_model_comment' => [3 => ['delete' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and only the copy of the German translation is deleted
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/CommentRemoved.csv');
    }

    /**
     * @see commentRemovedThroughTheTranslationRemovesOnlyTheCopyOfTheTranslationByCore()
     */
    #[Test]
    public function commentRemovedThroughTheTranslationRemovesOnlyTheCopyOfTheTranslationByExtbase(): void
    {
        // Given: the German translation of the post is read with overlays mixed, together with the comment "Nice read"
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(7);
        $niceRead = array_find($post->getComments()->toArray(), static fn(Comment $comment): bool => $comment->getContent() === 'Nice read');

        // When: the comment "Nice read" is removed from the German translation and persisted
        $post->removeComment($niceRead);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: only the copy of the German translation is deleted
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/CommentRemoved.csv');
    }

    /**
     * The English post has the tags PHP and TYPO3, and so have both translations. The tag Fluid is
     * added through the German translation with DataHandler. The tags are excluded from
     * translation, so DataHandler drops the change and nothing changes.
     *
     * @see tagAddedThroughATranslationIsDroppedByExtbase()
     */
    #[Test]
    public function tagAddedThroughATranslationIsDroppedByCore(): void
    {
        // When: the tag Fluid (uid 3) is added to the German translation
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['tags' => '1,2,3']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and nothing has changed
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/NothingChanged.csv');
    }

    /**
     * Extbase adds the tag Fluid to the German translation.
     *
     * @see tagAddedThroughATranslationIsDroppedByCore()
     */
    #[Test]
    public function tagAddedThroughATranslationIsDroppedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the German translation of the post is read with overlays mixed, together with the tag Fluid
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);
        $query = $this->get(PersistenceManager::class)->createQueryForType(Tag::class);
        $fluid = $query->matching($query->equals('name', 'Fluid'))->execute()->getFirst();

        // When: the tag Fluid is added to the German translation and persisted
        $post->addTag($fluid);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: nothing has changed
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/NothingChanged.csv');
    }

    /**
     * The English post has the tags PHP and TYPO3, and so have both translations. The tag TYPO3 is
     * removed through the German translation with DataHandler. The tags are excluded from
     * translation, so DataHandler drops the change and nothing changes.
     *
     * @see tagRemovedThroughATranslationIsDroppedByExtbase()
     */
    #[Test]
    public function tagRemovedThroughATranslationIsDroppedByCore(): void
    {
        // When: the tag TYPO3 (uid 1) is removed from the German translation
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_post' => [2 => ['tags' => '2']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and nothing has changed
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/NothingChanged.csv');
    }

    /**
     * Extbase removes the tag TYPO3 from the English post instead; the German translation keeps it.
     *
     * @see tagRemovedThroughATranslationIsDroppedByCore()
     */
    #[Test]
    public function tagRemovedThroughATranslationIsDroppedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111055');

        // Given: the German translation of the post is read with overlays mixed, together with the tag TYPO3
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);
        $typo3 = array_find($post->getTags()->toArray(), static fn(Tag $tag): bool => $tag->getName() === 'TYPO3');

        // When: the tag TYPO3 is removed from the German translation and persisted
        $post->removeTag($typo3);
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: nothing has changed
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteTranslation/NothingChanged.csv');
    }
}
