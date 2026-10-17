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
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Comment;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Changes a child record of an inline relation configured with "l10n_mode" => "exclude" after the
 * parent has been localized (Forge #107082).
 *
 * "additional_info" of a post is an excluded inline relation to the language aware table
 * tx_blogexample_domain_model_info. Localizing the post gives the translation its own copy of the
 * info. The field "name" of the info is a plain field: it neither has "l10n_mode" nor
 * "allowLanguageSynchronization".
 *
 * Fixture WriteDefaultChild.csv: post 1 [en] with info 1 and no translation yet.
 */
final class ExcludedFromTranslationWriteDefaultChildTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de-DE'],
        'FR' => ['id' => 2, 'title' => 'French', 'locale' => 'fr-FR'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN']),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
            ]
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild.csv');
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
     * The English post is localized to German, which copies the info "Extra details" for the
     * translation. The name of the info of the English post is then changed to "Changed details"
     * through DataHandler, the way the backend saves a child record edited inline. The name is a
     * translatable field of the child table, so DataHandler leaves the copy of the translation
     * untouched.
     *
     * @see nameChangedOnTheDefaultInfoStaysInTheCopyOfTheTranslationByExtbase()
     */
    #[Test]
    public function nameChangedOnTheDefaultInfoStaysInTheCopyOfTheTranslationByCore(): void
    {
        // Given: the English post is localized to German, the translation owns a copy of the info
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);

        // When: the name of the English info is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_info' => [1 => ['name' => 'Changed details']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the copy of the German translation keeps its name
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/ChildChanged.csv');
    }

    /**
     * Same as above, but the datamap also carries the inline field of the English post, the way
     * FormEngine submits it when the parent form is saved together with the edited child.
     *
     * @see nameChangedOnTheDefaultInfoStaysInTheCopyOfTheTranslationByCore()
     */
    #[Test]
    public function nameChangedOnTheDefaultInfoWithParentInTheDatamapStaysInTheCopyOfTheTranslationByCore(): void
    {
        // Given: the English post is localized to German, the translation owns a copy of the info
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);

        // When: the name of the English info is changed and the English post is saved with it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_blogexample_domain_model_post' => [1 => ['additional_info' => '1']],
                'tx_blogexample_domain_model_info' => [1 => ['name' => 'Changed details']],
            ],
            []
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the copy of the German translation keeps its name
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/ChildChanged.csv');
    }

    /**
     * The name of the info is configured with "l10n_mode" => "exclude" as well. Changing the name of
     * the English info is then written to the copy of the German translation.
     *
     * @see nameChangedOnTheDefaultInfoWithExcludedNameIsWrittenToTheCopyOfTheTranslationByExtbase()
     */
    #[Test]
    public function nameChangedOnTheDefaultInfoWithExcludedNameIsWrittenToTheCopyOfTheTranslationByCore(): void
    {
        // Given: the name of the info is excluded and the English post is localized to German
        $GLOBALS['TCA']['tx_blogexample_domain_model_info']['columns']['name']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);

        // When: the name of the English info is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_info' => [1 => ['name' => 'Changed details']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the copy of the German translation has the new name
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/ChildChangedExcluded.csv');
    }

    /**
     * Extbase writes the new name to the English info only, as DataHandler does for a name that is
     * not excluded.
     *
     * @see nameChangedOnTheDefaultInfoStaysInTheCopyOfTheTranslationByCore()
     */
    #[Test]
    public function nameChangedOnTheDefaultInfoStaysInTheCopyOfTheTranslationByExtbase(): void
    {
        // Given: the English post is localized to German and read with overlays mixed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the name of the info of the English post is changed and persisted
        $post->getAdditionalInfo()->setName('Changed details');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the copy of the German translation keeps its name
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/ChildChanged.csv');
    }

    /**
     * Extbase writes the new name to the English info only; the copy of the German translation keeps
     * the old name.
     *
     * @see nameChangedOnTheDefaultInfoWithExcludedNameIsWrittenToTheCopyOfTheTranslationByCore()
     */
    #[Test]
    public function nameChangedOnTheDefaultInfoWithExcludedNameIsWrittenToTheCopyOfTheTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111126');

        // Given: the name of the info is excluded, the English post is localized to German and read with overlays mixed
        // @phpstan-ignore deadCode.unreachable
        $GLOBALS['TCA']['tx_blogexample_domain_model_info']['columns']['name']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the name of the info of the English post is changed and persisted
        $post->getAdditionalInfo()->setName('Changed details');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the copy of the German translation has the new name
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/ChildChangedExcluded.csv');
    }

    /**
     * The comment table is not language aware, so the comments of the English post "Third post" are
     * copied for the German translation without any pointer to their origin. The content of the
     * comment "Nice read" is then changed through DataHandler; the copy of the translation keeps
     * its content.
     *
     * @see contentChangedOnTheDefaultCommentStaysInTheCopyOfTheTranslationByExtbase()
     */
    #[Test]
    public function contentChangedOnTheDefaultCommentStaysInTheCopyOfTheTranslationByCore(): void
    {
        // Given: the English post with two comments is localized to German
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultMonoglotChild.csv');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [7 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);

        // When: the content of the comment "Nice read" is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_comment' => [1 => ['content' => 'Changed']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the copy of the German translation keeps its content
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/MonoglotChildChanged.csv');
    }

    /**
     * Extbase writes the new content to the English comment only, as DataHandler does.
     *
     * @see contentChangedOnTheDefaultCommentStaysInTheCopyOfTheTranslationByCore()
     */
    #[Test]
    public function contentChangedOnTheDefaultCommentStaysInTheCopyOfTheTranslationByExtbase(): void
    {
        // Given: the English post with two comments is localized to German and read with overlays mixed
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultMonoglotChild.csv');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [7 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(7);
        $niceRead = array_find($post->getComments()->toArray(), static fn(Comment $comment): bool => $comment->getContent() === 'Nice read');

        // When: the content of the comment "Nice read" is changed and persisted
        $niceRead->setContent('Changed');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the copy of the German translation keeps its content
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/MonoglotChildChanged.csv');
    }

    /**
     * The English post "Third post" has two comments, a 1:n relation configured with "l10n_mode" =>
     * "exclude" to a table that is not language aware. Localizing it to German gives the German
     * translation its own copies of both comments. Localizing the same post to French afterwards
     * should give the French translation copies of its own and leave the German ones alone.
     */
    #[Test]
    public function localizingToASecondLanguageKeepsTheCopiesOfTheFirstLanguageByCore(): void
    {
        self::markTestSkipped('DataHandler mixes up the copies of monoglot inline children with several translations, see https://forge.typo3.org/issues/111069');

        // Given: the English post with two comments is localized to German
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultMonoglotChild.csv');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [7 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);

        // When: the English post is localized to French as well
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [7 => ['localize' => 2]]]);
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and German and French each have copies of both comments
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/MonoglotTwoTranslations.csv');
    }

    /**
     * The name of the info is excluded and the English post is localized to German and French. Changing
     * the name of the English info is written to the copies of both translations.
     */
    #[Test]
    public function nameChangedOnTheDefaultInfoWithExcludedNameIsWrittenToTheCopiesOfTwoTranslationsByCore(): void
    {
        // Given: the name of the info is excluded and the English post is localized to German and French
        $GLOBALS['TCA']['tx_blogexample_domain_model_info']['columns']['name']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 2]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);

        // When: the name of the English info is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_info' => [1 => ['name' => 'Changed details']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the copies of German and French have the new name
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/ChildChangedExcludedTwoTranslations.csv');
    }

    /**
     * The English post is localized to German in live. In workspace 1 the name of the English info is
     * changed through DataHandler. The name is not excluded, so the live copy of the German
     * translation is neither changed nor versioned.
     *
     * @see nameChangedOnTheDefaultInfoInAWorkspaceStaysInTheCopyOfTheTranslationByExtbase()
     */
    #[Test]
    public function nameChangedOnTheDefaultInfoInAWorkspaceStaysInTheCopyOfTheTranslationByCore(): void
    {
        // Given: the English post is localized to German in live, then the backend user works in workspace 1
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);
        $GLOBALS['BE_USER']->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));

        // When: the name of the English info is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_info' => [1 => ['name' => 'Changed details']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error, the English info has a workspace version and the German copy is untouched
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/ChildChangedInWorkspace.csv');
    }

    /**
     * Extbase writes the new name into the live English info, creates no workspace version and leaves
     * the copy of the German translation untouched.
     *
     * @see nameChangedOnTheDefaultInfoInAWorkspaceStaysInTheCopyOfTheTranslationByCore()
     */
    #[Test]
    public function nameChangedOnTheDefaultInfoInAWorkspaceStaysInTheCopyOfTheTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core, see https://forge.typo3.org/issues/111126');

        // Given: the English post is localized to German in live, the backend user works in workspace 1 and the post is read with overlays mixed
        // @phpstan-ignore deadCode.unreachable
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);
        $GLOBALS['BE_USER']->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED));
        $post = $this->get(PostRepository::class)->findByUid(1);

        // When: the name of the info of the English post is changed and persisted
        $post->getAdditionalInfo()->setName('Changed details');
        $this->get(PostRepository::class)->update($post);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the English info has a workspace version and the German copy is untouched
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/ChildChangedInWorkspace.csv');
    }

    /**
     * The name of the info is excluded and the English post is localized to German in live. In
     * workspace 1 the name of the English info is changed through DataHandler.
     */
    #[Test]
    public function nameChangedOnTheDefaultInfoWithExcludedNameInAWorkspaceIsWrittenToTheCopyOfTheTranslationByCore(): void
    {
        // Given: the name of the info is excluded, the English post is localized to German in live, then the backend user works in workspace 1
        $GLOBALS['TCA']['tx_blogexample_domain_model_info']['columns']['name']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);
        $GLOBALS['BE_USER']->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));

        // When: the name of the English info is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_info' => [1 => ['name' => 'Changed details']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the workspace has versions of the English info and the German copy
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/ChildChangedExcludedInWorkspace.csv');
    }

    /**
     * The post "Hello world" has two infos, treated as 1:n relation, and is localized to German and
     * French. The name of the first English info is changed through DataHandler. The name is not
     * excluded, so none of the four copies changes.
     */
    #[Test]
    public function nameChangedOnOneOfTwoDefaultInfosStaysInTheCopiesOfTwoTranslationsByCore(): void
    {
        // Given: the post has a second info and is localized to German and French
        $GLOBALS['TCA']['tx_blogexample_domain_model_post']['columns']['additional_info']['config']['relationship'] = 'oneToMany';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChildMultiple.csv');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 2]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);

        // When: the name of the first English info is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_info' => [1 => ['name' => 'Changed details']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the copies of German and French keep their names
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/MultipleChildrenChanged.csv');
    }

    /**
     * Same as above, but the name of the info is configured with "l10n_mode" => "exclude". The name
     * of the first English info is written to its copies in German and French, the copies of the
     * second info keep their name.
     */
    #[Test]
    public function nameChangedOnOneOfTwoDefaultInfosWithExcludedNameIsWrittenToTheCopiesOfTwoTranslationsByCore(): void
    {
        // Given: the name is excluded, the post has a second info and is localized to German and French
        $GLOBALS['TCA']['tx_blogexample_domain_model_post']['columns']['additional_info']['config']['relationship'] = 'oneToMany';
        $GLOBALS['TCA']['tx_blogexample_domain_model_info']['columns']['name']['l10n_mode'] = 'exclude';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChildMultiple.csv');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 1]]]);
        $dataHandler->process_cmdmap();
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_post' => [1 => ['localize' => 2]]]);
        $dataHandler->process_cmdmap();
        self::assertSame([], $dataHandler->errorLog);

        // When: the name of the first English info is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_info' => [1 => ['name' => 'Changed details']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the copies of the first info have the new name
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExcludedFromTranslation/WriteDefaultChild/MultipleChildrenChangedExcluded.csv');
    }
}
