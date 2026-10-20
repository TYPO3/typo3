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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Info;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

/**
 * Writing translations: creating a translation, its link to the default record, copied and prefixed values,
 * synchronized fields, records for all languages, hidden new translations and moving a record with its translations.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (DataHandler, suffix
 * "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair start from the same fixture in
 * Fixtures/TranslationWrite/Given/ and assert the database against the same result CSV in Fixtures/TranslationWrite/.
 * An Extbase test that differs from core is skipped.
 *
 * Blogs and infos are translatable. The `subtitle` of a blog allows language synchronization and the `description` of
 * a blog has the language prefix (`prefixLangTitle`); both are set in a TCA override of blog_example. The table of the
 * infos has the setting `hideAtCopy`, also set in a TCA override. The site has the languages English (0) and German
 * (1).
 */
final class TranslationWriteTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/Base.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
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
     * The English blog 1 "Tech blog" is localized to German through DataHandler, the backend action "localize".
     * DataHandler creates the German blog 2 and links it: `l18n_parent` points to blog 1, `l18n_diffsource` holds the
     * values of blog 1 at the time of the translation (the test asserts its `title`), and `l10n_state` marks the
     * synchronized field `subtitle` as following its parent.
     *
     * @see creatingATranslationLinksItToTheDefaultRecordByExtbase()
     */
    #[Test]
    public function creatingATranslationLinksItToTheDefaultRecordByCore(): void
    {
        // Given: blog 1 exists without translation
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/Blog.csv');

        // When: blog 1 is localized to German
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_blog' => [1 => ['localize' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the translation is linked to blog 1 with its diff source
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/CreatedTranslation.csv');
        $diffSource = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_blogexample_domain_model_blog')
            ->select(['l18n_diffsource'], 'tx_blogexample_domain_model_blog', ['uid' => 2])
            ->fetchOne();
        $diffSource = is_resource($diffSource) ? stream_get_contents($diffSource) : $diffSource;
        self::assertNotSame('', $diffSource);
        self::assertSame('Tech blog', json_decode($diffSource, true, 512, JSON_THROW_ON_ERROR)['title']);
    }

    /**
     * Extbase has no localize action. The twin creates a new object, sets its language to German through the property
     * `"_languageUid"` and persists it with `add()` and `persistAll()`. Extbase writes the German blog 2 with the
     * language 1, but `l18n_parent` stays 0, `l18n_diffsource` stays empty
     * (core: it holds the `title` of blog 1, which the skipped test asserts) and `l10n_state` stays NULL: the new row
     * is not linked to blog 1.
     *
     * @see creatingATranslationLinksItToTheDefaultRecordByCore()
     */
    #[Test]
    public function creatingATranslationLinksItToTheDefaultRecordByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes `l18n_parent` as 0 and `l10n_state` as NULL, so the new German record is not linked to the default record.');

        // Given: blog 1 exists without translation
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/Blog.csv');
        $default = $this->get(BlogRepository::class)->findByUid(1);

        // When: a new German blog with the values of blog 1 is added and persisted
        $translation = new Blog();
        $translation->_setProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID, 1);
        $translation->setTitle($default->getTitle());
        $translation->setSubtitle($default->getSubtitle());
        $translation->setDescription($default->getDescription());
        $this->get(BlogRepository::class)->add($translation);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the translation is linked to blog 1 with its diff source
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/CreatedTranslation.csv');
        $diffSource = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_blogexample_domain_model_blog')
            ->select(['l18n_diffsource'], 'tx_blogexample_domain_model_blog', ['uid' => 2])
            ->fetchOne();
        $diffSource = is_resource($diffSource) ? stream_get_contents($diffSource) : $diffSource;
        self::assertNotSame('', $diffSource);
        self::assertSame('Tech blog', json_decode($diffSource, true, 512, JSON_THROW_ON_ERROR)['title']);
    }

    /**
     * The English blog 1 "Tech blog" is localized to German through DataHandler, the backend action "localize".
     * DataHandler copies the `title` and the `subtitle` from blog 1 to the new German blog 2.
     *
     * @see aTranslationCopiesTheValuesOfTheDefaultRecordByExtbase()
     */
    #[Test]
    public function aTranslationCopiesTheValuesOfTheDefaultRecordByCore(): void
    {
        // Given: blog 1 exists without translation
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/Blog.csv');

        // When: blog 1 is localized to German
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_blog' => [1 => ['localize' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the translation has the values of blog 1
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/CopiedValues.csv');
    }

    /**
     * Extbase has no localize action and copies nothing by itself. The twin reads blog 1 through the repository,
     * creates a new object with the language German (property `"_languageUid"`), assigns the `title` and the `subtitle`
     * of blog 1 in code and persists it with `add()` and `persistAll()`. The German blog 2 has the same `title` and
     * `subtitle` as with DataHandler, because the code assigned them; the row is not linked to blog 1, which this
     * scenario does not compare.
     *
     * @see aTranslationCopiesTheValuesOfTheDefaultRecordByCore()
     */
    #[Test]
    public function aTranslationCopiesTheValuesOfTheDefaultRecordByExtbase(): void
    {
        // Given: blog 1 exists without translation
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/Blog.csv');
        $default = $this->get(BlogRepository::class)->findByUid(1);

        // When: a new German blog with the values of blog 1 is added and persisted
        $translation = new Blog();
        $translation->_setProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID, 1);
        $translation->setTitle($default->getTitle());
        $translation->setSubtitle($default->getSubtitle());
        $translation->setDescription($default->getDescription());
        $this->get(BlogRepository::class)->add($translation);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the translation has the values of blog 1
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/CopiedValues.csv');
    }

    /**
     * The English blog 1 "Tech blog" is localized to German through DataHandler, the backend action "localize". The
     * `description` has the language prefix (`prefixLangTitle`), so DataHandler writes the `description` of the German
     * blog 2 with the prefix "[Translate to German:]" in front of the value of blog 1.
     *
     * @see prefixLangTitlePrefixesTheTranslatedValueByExtbase()
     */
    #[Test]
    public function prefixLangTitlePrefixesTheTranslatedValueByCore(): void
    {
        // Given: blog 1 exists without translation
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/Blog.csv');

        // When: blog 1 is localized to German
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_blog' => [1 => ['localize' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the description of the translation is prefixed
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/PrefixedDescription.csv');
    }

    /**
     * Extbase has no localize action. The twin creates a new German object (property `"_languageUid"`) with the
     * `description` of blog 1 and persists it with `add()` and `persistAll()`. Extbase stores the `description` exactly
     * as the code assigned it, without the language prefix; the TCA setting `prefixLangTitle` is ignored.
     *
     * @see prefixLangTitlePrefixesTheTranslatedValueByCore()
     */
    #[Test]
    public function prefixLangTitlePrefixesTheTranslatedValueByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the description without the language prefix, because it ignores `prefixLangTitle`.');

        // Given: blog 1 exists without translation
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/Blog.csv');
        $default = $this->get(BlogRepository::class)->findByUid(1);

        // When: a new German blog with the values of blog 1 is added and persisted
        $translation = new Blog();
        $translation->_setProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID, 1);
        $translation->setTitle($default->getTitle());
        $translation->setSubtitle($default->getSubtitle());
        $translation->setDescription($default->getDescription());
        $this->get(BlogRepository::class)->add($translation);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the description of the translation is prefixed
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/PrefixedDescription.csv');
    }

    /**
     * The `title` of the German translation 2 is changed to "Neuer Blog" through DataHandler. DataHandler keeps
     * `l18n_parent` 1, writes the new title, updates `l18n_diffsource` with the values of the default record 1 and
     * writes `l10n_state` with the synchronized field `subtitle` marked as following its parent.
     *
     * @see changingATranslationKeepsItsLinkByExtbase()
     */
    #[Test]
    public function changingATranslationKeepsItsLinkByCore(): void
    {
        // Given: blog 1 exists with the German translation 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/TranslatedBlog.csv');

        // When: the title of the translation 2 is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_blog' => [2 => ['title' => 'Neuer Blog']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the translation keeps its link
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/ChangedTranslation.csv');
    }

    /**
     * Extbase reads the German translation through the repository (language aspect German), changes the `title` to
     * "Neuer Blog" and persists it with `update()` and `persistAll()`. Extbase writes the new title into the
     * translation 2 and keeps `l18n_parent` 1, but leaves `l18n_diffsource` empty and `l10n_state` NULL.
     *
     * @see changingATranslationKeepsItsLinkByCore()
     */
    #[Test]
    public function changingATranslationKeepsItsLinkByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it keeps `l18n_parent` but writes neither `l18n_diffsource` nor `l10n_state` when it changes a translation.');

        // Given: blog 1 exists with the German translation 2, which is read
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/TranslatedBlog.csv');
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: the title is changed and persisted
        $blog->setTitle('Neuer Blog');
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the translation keeps its link
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/ChangedTranslation.csv');
    }

    /**
     * The `subtitle` of the English blog 1 is changed to "Changed subtitle" through DataHandler. The field allows
     * language synchronization and the translation 2 follows its parent, so DataHandler writes the new subtitle into
     * the translation as well and records the state "parent" for the field in `l10n_state`.
     *
     * @see aSynchronizedFieldFollowsTheDefaultRecordByExtbase()
     */
    #[Test]
    public function aSynchronizedFieldFollowsTheDefaultRecordByCore(): void
    {
        // Given: blog 1 exists with the German translation 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/TranslatedBlog.csv');

        // When: the subtitle of blog 1 is changed
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_blog' => [1 => ['subtitle' => 'Changed subtitle']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the translation follows
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/SynchronizedSubtitle.csv');
    }

    /**
     * Extbase reads blog 1 through the repository, changes the `subtitle` to "Changed subtitle" and persists it with
     * `update()` and `persistAll()`. Extbase writes the new subtitle into blog 1 only; the translation 2 keeps its old
     * subtitle and `l10n_state` stays NULL, because Extbase does not know language synchronization.
     *
     * @see aSynchronizedFieldFollowsTheDefaultRecordByCore()
     */
    #[Test]
    public function aSynchronizedFieldFollowsTheDefaultRecordByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it changes only the default record and does not synchronize the subtitle into the translation.');

        // Given: blog 1 exists with the German translation 2
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/TranslatedBlog.csv');
        $blog = $this->get(BlogRepository::class)->findByUid(1);

        // When: the subtitle is changed and persisted
        $blog->setSubtitle('Changed subtitle');
        $this->get(BlogRepository::class)->update($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the translation follows
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/SynchronizedSubtitle.csv');
    }

    /**
     * A new blog "All languages" with the language "all languages" (-1) is created through DataHandler. DataHandler
     * writes the row with `sys_language_uid` -1 and `l18n_parent` 0.
     *
     * @see aRecordForAllLanguagesByExtbase()
     */
    #[Test]
    public function aRecordForAllLanguagesByCore(): void
    {
        // When: a blog for all languages is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_blog' => ['NEW1' => ['pid' => 2, 'sys_language_uid' => -1, 'title' => 'All languages']]], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the row has the language -1
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/AllLanguages.csv');
    }

    /**
     * Extbase creates a new blog "All languages", sets its language to -1 through the property `"_languageUid"` and
     * persists it with `add()` and `persistAll()`. Extbase writes `sys_language_uid` -1 and `l18n_parent` 0, like
     * DataHandler.
     *
     * @see aRecordForAllLanguagesByCore()
     */
    #[Test]
    public function aRecordForAllLanguagesByExtbase(): void
    {
        // When: a blog for all languages is added and persisted
        $blog = new Blog();
        $blog->_setProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID, -1);
        $blog->setTitle('All languages');
        $this->get(BlogRepository::class)->add($blog);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the row has the language -1
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/AllLanguages.csv');
    }

    /**
     * The table of the infos has the setting `hideAtCopy`. Info 1 "Extra details" is localized to German through
     * DataHandler, the backend action "localize". DataHandler creates the German info 2 with `hidden` 1, so the new
     * translation stays invisible until an editor enables it, while info 1 keeps `hidden` 0.
     *
     * @see aTranslationOfATableThatHidesNewCopiesIsHiddenByExtbase()
     */
    #[Test]
    public function aTranslationOfATableThatHidesNewCopiesIsHiddenByCore(): void
    {
        // Given: info 1 exists without translation
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/Info.csv');

        // When: info 1 is localized to German
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_info' => [1 => ['localize' => 1]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and the translation is hidden
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/HiddenTranslation.csv');
    }

    /**
     * Extbase has no localize action. The twin reads info 1, creates a new German object (property `"_languageUid"`)
     * with its `name` and persists it with `add()` and `persistAll()` of the persistence manager. Extbase writes the
     * German info 2 with `hidden` 0: it ignores the table setting `hideAtCopy`, so the new translation is visible at
     * once.
     *
     * @see aTranslationOfATableThatHidesNewCopiesIsHiddenByCore()
     */
    #[Test]
    public function aTranslationOfATableThatHidesNewCopiesIsHiddenByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it writes the translation with `hidden` 0 and ignores `hideAtCopy` of the table.');

        // Given: info 1 exists without translation
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/Info.csv');
        $default = $this->get(PersistenceManager::class)->getObjectByIdentifier(1, Info::class);

        // When: a new German info with the name of info 1 is added and persisted
        $translation = new Info();
        $translation->_setProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID, 1);
        $translation->setName($default->getName());
        $this->get(PersistenceManager::class)->add($translation);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the translation is hidden
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/HiddenTranslation.csv');
    }

    /**
     * Info 1 "Extra details" and its German translation 2 are on the storage page 2. Info 1 is moved to page 3 through
     * DataHandler, the backend command "move". DataHandler writes `pid` 3 into info 1 and into its translation 2, so
     * the translation follows the default record to the new page.
     *
     * @see movingARecordWithATranslationMovesTheTranslationByExtbase()
     */
    #[Test]
    public function movingARecordWithATranslationMovesTheTranslationByCore(): void
    {
        // Given: info 1 exists with the German translation 2, both on page 2
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/TranslatedInfo.csv');

        // When: info 1 is moved to page 3
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], ['tx_blogexample_domain_model_info' => [1 => ['move' => 3]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Then: DataHandler reports no error and both rows are on page 3
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/MovedInfo.csv');
    }

    /**
     * Extbase reads info 1, sets its `pid` to 3 with `setPid()` and persists it with `update()` and `persistAll()` of
     * the persistence manager. Extbase writes nothing: info 1 and its translation 2 both keep `pid` 2, because `pid` is
     * not a mapped column and Extbase uses it only to insert new records. A record cannot be moved to another page
     * through Extbase.
     *
     * @see movingARecordWithATranslationMovesTheTranslationByCore()
     */
    #[Test]
    public function movingARecordWithATranslationMovesTheTranslationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: setting the `pid` of an existing object and persisting it writes nothing, info 1 and its translation stay on page 2.');

        // Given: info 1 exists with the German translation 2, both on page 2
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/Given/TranslatedInfo.csv');
        $info = $this->get(PersistenceManager::class)->getObjectByIdentifier(1, Info::class);

        // When: the pid of info 1 is set to 3 and persisted
        $info->setPid(3);
        $this->get(PersistenceManager::class)->update($info);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: both rows are on page 3
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/TranslationWrite/MovedInfo.csv');
    }
}
