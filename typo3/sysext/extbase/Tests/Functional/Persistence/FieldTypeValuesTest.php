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
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\FieldValue;
use TYPO3Tests\BlogExample\Domain\Model\Identified;
use TYPO3Tests\BlogExample\Domain\Model\Stamp;
use TYPO3Tests\BlogExample\Domain\Repository\FieldValueRepository;
use TYPO3Tests\BlogExample\Domain\Repository\IdentifiedRepository;
use TYPO3Tests\BlogExample\Domain\Repository\StampRepository;

/**
 * Field values of further field types: what DataHandler generates, defaults and converts when a record is created or
 * changed.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * start from the same fixture in Fixtures/FieldTypeValues/Given/ and assert the stored row against
 * the same result CSV in Fixtures/FieldTypeValues/ (or against the same values read from the table, where the
 * stored value differs per run). Both run as the same administrator at the same fixed time, 1700000000, in the
 * time zone Europe/Berlin. An Extbase test that differs from core is skipped.
 *
 * The DataHandler is given the datamap that each test names. Where it differs from what the backend form sends for the
 * same record, the test says so. Extbase is given the value through the setter of a model property, typed the way an
 * extension author would type it.
 *
 * The first site has the root page 1 and the storage page 2, the second site has the root page 3 and the storage page
 * 4. Fixture Values.csv: field value 1 "Taken" on page 2 holds the slug "taken" in `slug` and `site_slug` and the note
 * "to be cleared".
 */
final class FieldTypeValuesTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    private string $timezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Given/Base.csv');
        $this->writeSiteConfiguration(
            'first',
            $this->buildSiteConfiguration(1, '/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );
        $this->writeSiteConfiguration(
            'second',
            $this->buildSiteConfiguration(3, 'https://second.example.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $GLOBALS['EXEC_TIME'] = 1700000000;
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@1700000000')));
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => 2],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);
        parent::tearDown();
    }

    /**
     * The password field `secret` of a new field value 1 holds the text "Str0ng!Passw0rd#2023" in the datamap, as the
     * backend form sends it. DataHandler hashes the text with the configured password hash of the backend and stores
     * the hash, which differs per run. The stored value is not the typed text, and the configured password hash
     * verifies the typed text against it.
     *
     * @see aPasswordIsHashedByExtbase()
     */
    #[Test]
    public function aPasswordIsHashedByCore(): void
    {
        // When: a new field value is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => ['NEW1' => ['pid' => 2, 'title' => 'Hashed', 'secret' => 'Str0ng!Passw0rd#2023']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds a hash of the text
        self::assertSame([], $dataHandler->errorLog);
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_fieldvalue')
            ->select(['title', 'secret'], 'tx_blogexample_domain_model_fieldvalue', ['uid' => 1])->fetchAssociative();
        self::assertIsArray($row);
        $hashInstance = $this->get(PasswordHashFactory::class)->getDefaultHashInstance('BE');
        self::assertSame(
            ['title' => 'Hashed', 'secret is the typed text' => false, 'typed text matches the stored secret' => true],
            ['title' => $row['title'], 'secret is the typed text' => $row['secret'] === 'Str0ng!Passw0rd#2023', 'typed text matches the stored secret' => $hashInstance->checkPassword('Str0ng!Passw0rd#2023', $row['secret'])]
        );
    }

    /**
     * Extbase stores the text "Str0ng!Passw0rd#2023" as it is, the hashed setting of the password field is not applied.
     * The stored value is the typed text and no password hash verifies it.
     *
     * @see aPasswordIsHashedByCore()
     */
    #[Test]
    public function aPasswordIsHashedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the typed password as plain text, the password field is not hashed.');

        // When: a field value with the secret is created and persisted
        // @phpstan-ignore deadCode.unreachable
        $value = new FieldValue();
        $value->setTitle('Hashed');
        $value->setSecret('Str0ng!Passw0rd#2023');
        $this->get(FieldValueRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds a hash of the text, as DataHandler stored
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_fieldvalue')
            ->select(['title', 'secret'], 'tx_blogexample_domain_model_fieldvalue', ['uid' => 1])->fetchAssociative();
        self::assertIsArray($row);
        $hashInstance = $this->get(PasswordHashFactory::class)->getDefaultHashInstance('BE');
        self::assertSame(
            ['title' => 'Hashed', 'secret is the typed text' => false, 'typed text matches the stored secret' => true],
            ['title' => $row['title'], 'secret is the typed text' => $row['secret'] === 'Str0ng!Passw0rd#2023', 'typed text matches the stored secret' => $hashInstance->checkPassword('Str0ng!Passw0rd#2023', $row['secret'])]
        );
    }

    /**
     * The uuid field `identifier` of a new identified record 1 "Identified" is sent empty. When DataHandler gets the
     * field empty, it generates a version 4 UUID and stores it. The value differs per run, so the test checks that the
     * stored value is a valid UUID. The backend form already fills a new UUID when the record is opened, so a record
     * saved in the backend always has one.
     *
     * @see aUuidIsGeneratedOnCreationByExtbase()
     */
    #[Test]
    public function aUuidIsGeneratedOnCreationByCore(): void
    {
        // When: a new identified record is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_identified' => ['NEW1' => ['pid' => 2, 'title' => 'Identified', 'identifier' => '']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds a valid UUID
        self::assertSame([], $dataHandler->errorLog);
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_identified')
            ->select(['title', 'identifier'], 'tx_blogexample_domain_model_identified', ['uid' => 1])->fetchAssociative();
        self::assertIsArray($row);
        self::assertSame(
            ['title' => 'Identified', 'identifier is a valid UUID' => true],
            ['title' => $row['title'], 'identifier is a valid UUID' => Uuid::isValid($row['identifier'])]
        );
    }

    /**
     * Extbase stores the empty string the `identifier` property holds, no UUID is generated for the uuid field.
     *
     * @see aUuidIsGeneratedOnCreationByCore()
     */
    #[Test]
    public function aUuidIsGeneratedOnCreationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the empty string of the identifier property, no UUID is generated for the uuid field.');

        // When: an identified record is created and persisted without setting the identifier
        // @phpstan-ignore deadCode.unreachable
        $value = new Identified();
        $value->setTitle('Identified');
        $this->get(IdentifiedRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds a valid UUID, as DataHandler stored one
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_identified')
            ->select(['title', 'identifier'], 'tx_blogexample_domain_model_identified', ['uid' => 1])->fetchAssociative();
        self::assertIsArray($row);
        self::assertSame(
            ['title' => 'Identified', 'identifier is a valid UUID' => true],
            ['title' => $row['title'], 'identifier is a valid UUID' => Uuid::isValid($row['identifier'])]
        );
    }

    /**
     * The slug field `slug` of a new field value with the title "My first title" is sent empty. When DataHandler gets
     * the slug field empty, the generator of the field takes the title and DataHandler stores "my-first-title". The
     * slug element of the backend form suggests a slug while the title is typed, so the form usually sends a slug
     * already.
     *
     * @see aSlugIsGeneratedFromTheTitleOnCreationByExtbase()
     */
    #[Test]
    public function aSlugIsGeneratedFromTheTitleOnCreationByCore(): void
    {
        // When: a new field value is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => ['NEW1' => ['pid' => 2, 'title' => 'My first title', 'slug' => '']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the generated slug
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Slug.csv');
    }

    /**
     * Extbase stores the empty string the `slug` property holds, the generator of the slug field does not run and the
     * title is not used.
     *
     * @see aSlugIsGeneratedFromTheTitleOnCreationByCore()
     */
    #[Test]
    public function aSlugIsGeneratedFromTheTitleOnCreationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the empty string of the slug property, the slug is not generated from the title.');

        // When: a field value with the title is created and persisted
        // @phpstan-ignore deadCode.unreachable
        $value = new FieldValue();
        $value->setTitle('My first title');
        $this->get(FieldValueRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the slug DataHandler generated
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Slug.csv');
    }

    /**
     * Field value 1 "Taken" on page 2 holds the slug "taken". A new field value on page 2 with the title "Taken" is
     * sent with the slug "taken" for the slug field `slug`, which has `eval` `uniqueInPid`. DataHandler appends a
     * counter and stores "taken-1". The slug element of the backend form asks for a free slug while the editor types
     * and would already send "taken-1", so the test sends the slug of a request that skips this suggestion.
     *
     * @see aDuplicateSlugIsMadeUniqueInThePageByExtbase()
     */
    #[Test]
    public function aDuplicateSlugIsMadeUniqueInThePageByCore(): void
    {
        // Given: field value 1 "Taken" exists
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Given/Values.csv');

        // When: a new field value with the slug "taken" is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => ['NEW1' => ['pid' => 2, 'title' => 'Taken', 'slug' => 'taken']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the counted slug
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/DuplicateSlugInPid.csv');
    }

    /**
     * Extbase stores "taken" for the new field value 2, so field values 1 and 2 on page 2 hold the same slug, `eval`
     * `uniqueInPid` of the slug field is not applied.
     *
     * @see aDuplicateSlugIsMadeUniqueInThePageByCore()
     */
    #[Test]
    public function aDuplicateSlugIsMadeUniqueInThePageByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the duplicate slug taken as it is, `eval` `uniqueInPid` of the slug field is not applied.');

        // Given: field value 1 "Taken" exists
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Given/Values.csv');

        // When: a field value with the slug is created and persisted
        $value = new FieldValue();
        $value->setTitle('Taken');
        $value->setSlug('taken');
        $this->get(FieldValueRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the slug DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/DuplicateSlugInPid.csv');
    }

    /**
     * Field value 1 "Taken" on page 2 holds the slug "taken" in the field `site_slug`. A new field value on page 2, in
     * the same site, with the title "Taken" is sent with the slug "taken" for the slug field `site_slug`, which has
     * `eval` `uniqueInSite`. DataHandler appends a counter and stores "taken-1". The slug element of the backend form
     * asks for a free slug while the editor types and would already send "taken-1", so the test sends the slug of a
     * request that skips this suggestion.
     *
     * @see aDuplicateSlugIsMadeUniqueInTheSiteByExtbase()
     */
    #[Test]
    public function aDuplicateSlugIsMadeUniqueInTheSiteByCore(): void
    {
        // Given: field value 1 "Taken" exists
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Given/Values.csv');

        // When: a new field value with the slug "taken" is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => ['NEW1' => ['pid' => 2, 'title' => 'Taken', 'site_slug' => 'taken']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the counted slug
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/DuplicateSlugInSite.csv');
    }

    /**
     * Extbase stores "taken" for the new field value 2, so both field values in the site hold the same slug, `eval`
     * `uniqueInSite` of the slug field is not applied.
     *
     * @see aDuplicateSlugIsMadeUniqueInTheSiteByCore()
     */
    #[Test]
    public function aDuplicateSlugIsMadeUniqueInTheSiteByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the duplicate slug taken as it is, `eval` `uniqueInSite` of the slug field is not applied.');

        // Given: field value 1 "Taken" exists
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Given/Values.csv');

        // When: a field value with the slug is created and persisted
        $value = new FieldValue();
        $value->setTitle('Taken');
        $value->setSiteSlug('taken');
        $this->get(FieldValueRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the slug DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/DuplicateSlugInSite.csv');
    }

    /**
     * Field value 1 "Taken" on page 2, in the first site, holds the slug "taken" in the field `site_slug`. A new field
     * value on page 4, in the second site, is sent with the same slug "taken" for the field, which has `eval`
     * `uniqueInSite`. DataHandler stores "taken" unchanged, as the slug is free in the second site. The slug element of
     * the backend form would send the same slug, as its suggestion finds it free in the second site.
     *
     * @see aSlugOfAnotherSiteIsNotChangedByExtbase()
     */
    #[Test]
    public function aSlugOfAnotherSiteIsNotChangedByCore(): void
    {
        // Given: field value 1 "Taken" exists
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Given/Values.csv');

        // When: a new field value with the slug "taken" is created on page 4
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => ['NEW1' => ['pid' => 4, 'title' => 'Taken', 'site_slug' => 'taken']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the slug unchanged
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/SameSlugInOtherSite.csv');
    }

    /**
     * Extbase also stores "taken" for the new field value on page 4, in the second site. The page of the field value is
     * set with `setPid()`.
     *
     * @see aSlugOfAnotherSiteIsNotChangedByCore()
     */
    #[Test]
    public function aSlugOfAnotherSiteIsNotChangedByExtbase(): void
    {
        // Given: field value 1 "Taken" exists
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Given/Values.csv');

        // When: a field value with the slug is created for page 4 and persisted
        $value = new FieldValue();
        $value->setPid(4);
        $value->setTitle('Taken');
        $value->setSiteSlug('taken');
        $this->get(FieldValueRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the slug DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/SameSlugInOtherSite.csv');
    }

    /**
     * A new field value is sent with a `pid` and a title only. The fields `label`, `amount` and `flag` have the TCA
     * defaults "unnamed", 7 and 1. When a new record comes without a value for a field, DataHandler stores the TCA
     * default of the field. The backend form already fills the TCA defaults into a new record when it is opened, so it
     * sends the three fields with these values.
     *
     * @see tcaDefaultsAreAppliedOnCreationByExtbase()
     */
    #[Test]
    public function tcaDefaultsAreAppliedOnCreationByCore(): void
    {
        // When: a new field value without label, amount and flag is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => ['NEW1' => ['pid' => 2, 'title' => 'With defaults']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the TCA defaults
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/TcaDefaults.csv');
    }

    /**
     * Extbase stores the values the properties hold, an empty string, 0 and 0, in `label`, `amount` and `flag`. The TCA
     * defaults are not applied, although the object does not set the three properties.
     *
     * @see tcaDefaultsAreAppliedOnCreationByCore()
     */
    #[Test]
    public function tcaDefaultsAreAppliedOnCreationByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores an empty string, 0 and 0 for `label`, `amount` and `flag`, the TCA defaults unnamed, 7 and 1 are not applied.');

        // When: a field value is created and persisted, only the title is set
        // @phpstan-ignore deadCode.unreachable
        $value = new FieldValue();
        $value->setTitle('With defaults');
        $this->get(FieldValueRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the TCA defaults DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/TcaDefaults.csv');
    }

    /**
     * A new field value is sent with the value null for the nullable input field `note`. DataHandler stores NULL. The
     * backend form sends the text of the field together with the null checkbox of the field in its deactivated state,
     * and DataHandler turns that into null before it processes the field, so the test hands DataHandler the resulting
     * null directly.
     *
     * @see aNullableFieldStoresNullOnCreationByExtbase()
     */
    #[Test]
    public function aNullableFieldStoresNullOnCreationByCore(): void
    {
        // When: a new field value with a null note is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => ['NEW1' => ['pid' => 2, 'title' => 'Nullable', 'note' => null]]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds NULL
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/NullOnCreate.csv');
    }

    /**
     * Extbase also stores NULL, the property is typed `?string` and holds null.
     *
     * @see aNullableFieldStoresNullOnCreationByCore()
     */
    #[Test]
    public function aNullableFieldStoresNullOnCreationByExtbase(): void
    {
        // When: a field value with a null note is created and persisted
        $value = new FieldValue();
        $value->setTitle('Nullable');
        $value->setNote(null);
        $this->get(FieldValueRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds NULL
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/NullOnCreate.csv');
    }

    /**
     * Field value 1 "Taken" holds the note "to be cleared". The value null is sent for the nullable input field `note`.
     * DataHandler stores NULL. The backend form sends the text of the field together with the null checkbox of the
     * field in its deactivated state, and DataHandler turns that into null before it processes the field, so the test
     * hands DataHandler the resulting null directly.
     *
     * @see aNullableFieldIsClearedToNullByExtbase()
     */
    #[Test]
    public function aNullableFieldIsClearedToNullByCore(): void
    {
        // Given: field value 1 "Taken" exists
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Given/Values.csv');

        // When: the note of field value 1 is set to null
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [1 => ['note' => null]]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds NULL
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/NullOnUpdate.csv');
    }

    /**
     * Extbase also stores NULL, the property is set to null.
     *
     * @see aNullableFieldIsClearedToNullByCore()
     */
    #[Test]
    public function aNullableFieldIsClearedToNullByExtbase(): void
    {
        // Given: field value 1 "Taken" exists
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/Given/Values.csv');

        // When: the note is set to null on field value 1 and persisted
        $value = $this->get(FieldValueRepository::class)->findByUid(1);
        $value->setNote(null);
        $this->get(FieldValueRepository::class)->update($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds NULL
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/NullOnUpdate.csv');
    }

    /**
     * The ISO 8601 string "2023-11-14T22:58:00+00:00", 22:58 UTC and 23:58 in the server time zone Europe/Berlin, is
     * sent for the datetime field `due_date`, which has `dbType` `date`. DataHandler stores the date "2023-11-14" and
     * drops the time. The backend form sends the time of the server without an offset, "2023-11-14T23:58:00"; the test
     * sends the same instant with an offset, which DataHandler also accepts.
     *
     * @see aDateTimeIsStoredInANativeDateColumnByExtbase()
     */
    #[Test]
    public function aDateTimeIsStoredInANativeDateColumnByCore(): void
    {
        // When: a new field value is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => ['NEW1' => ['pid' => 2, 'title' => 'Native date', 'due_date' => '2023-11-14T22:58:00+00:00']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the date
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/NativeDate.csv');
    }

    /**
     * Extbase also stores "2023-11-14". The value comes from a DateTimeImmutable property.
     *
     * @see aDateTimeIsStoredInANativeDateColumnByCore()
     */
    #[Test]
    public function aDateTimeIsStoredInANativeDateColumnByExtbase(): void
    {
        // When: a field value is created and persisted
        $value = new FieldValue();
        $value->setTitle('Native date');
        $value->setDueDate(new \DateTimeImmutable('2023-11-14T22:58:00+00:00'));
        $this->get(FieldValueRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the date DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/NativeDate.csv');
    }

    /**
     * The ISO 8601 string "2023-11-14T22:58:00+00:00", 22:58 UTC and 23:58 in the server time zone Europe/Berlin, is
     * sent for the datetime field `due_moment`, which has `dbType` `datetime`. DataHandler converts the value to the
     * time zone of the server and stores "2023-11-14 23:58:00". The backend form sends the time of the server without
     * an offset, "2023-11-14T23:58:00"; the test sends the same instant with an offset, which DataHandler also accepts.
     *
     * @see aDateTimeIsStoredInANativeDatetimeColumnByExtbase()
     */
    #[Test]
    public function aDateTimeIsStoredInANativeDatetimeColumnByCore(): void
    {
        // When: a new field value is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => ['NEW1' => ['pid' => 2, 'title' => 'Native datetime', 'due_moment' => '2023-11-14T22:58:00+00:00']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the local time
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/NativeDatetime.csv');
    }

    /**
     * Extbase also stores "2023-11-14 23:58:00". The value comes from a DateTimeImmutable property in UTC.
     *
     * @see aDateTimeIsStoredInANativeDatetimeColumnByCore()
     */
    #[Test]
    public function aDateTimeIsStoredInANativeDatetimeColumnByExtbase(): void
    {
        // When: a field value is created and persisted
        $value = new FieldValue();
        $value->setTitle('Native datetime');
        $value->setDueMoment(new \DateTimeImmutable('2023-11-14T22:58:00+00:00'));
        $this->get(FieldValueRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the local time DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/NativeDatetime.csv');
    }

    /**
     * The ISO 8601 string "2023-11-14T22:58:00+00:00" is sent for the datetime field `due_day`, which has `format`
     * `date` and stores a Unix timestamp. DataHandler sets the time to midnight in the time zone of the value, UTC, and
     * stores 1699920000, the timestamp of 2023-11-14T00:00:00+00:00. The backend form sends the time of the server
     * without an offset, which DataHandler would read as Europe/Berlin and store as 1699916400, midnight in Berlin; the
     * test sends an offset to keep the expected value free of the time zone of the server.
     *
     * @see aDateTimeInADateFormatFieldLosesItsTimeByExtbase()
     */
    #[Test]
    public function aDateTimeInADateFormatFieldLosesItsTimeByCore(): void
    {
        // When: a new field value is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => ['NEW1' => ['pid' => 2, 'title' => 'Date format', 'due_day' => '2023-11-14T22:58:00+00:00']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the timestamp of midnight
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/DateFormat.csv');
    }

    /**
     * Extbase also stores 1699920000. The value comes from a DateTimeImmutable property in UTC.
     *
     * @see aDateTimeInADateFormatFieldLosesItsTimeByCore()
     */
    #[Test]
    public function aDateTimeInADateFormatFieldLosesItsTimeByExtbase(): void
    {
        // When: a field value is created and persisted
        $value = new FieldValue();
        $value->setTitle('Date format');
        $value->setDueDay(new \DateTimeImmutable('2023-11-14T22:58:00+00:00'));
        $this->get(FieldValueRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the timestamp DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/DateFormat.csv');
    }

    /**
     * A new stamp is created with a title. Its table has `crdate` as creation time in `ctrl` and, in addition, a column
     * `crdate` of type `passthrough`. DataHandler stores the current time, 1700000000, in `crdate` and in `tstamp`.
     *
     * @see theCreationTimeIsKeptWhenTheModelHasAPropertyByExtbase()
     */
    #[Test]
    public function theCreationTimeIsKeptWhenTheModelHasAPropertyByCore(): void
    {
        // When: a new stamp is created
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_stamp' => ['NEW1' => ['pid' => 2, 'title' => 'Stamped']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the current time
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/CreationTime.csv');
    }

    /**
     * Extbase stores 0 in `crdate`. It first inserts the row with the current time as `crdate`, then writes the
     * property, which still holds its initial value 0, over it within the same `persistAll()` call.
     *
     * @see theCreationTimeIsKeptWhenTheModelHasAPropertyByCore()
     */
    #[Test]
    public function theCreationTimeIsKeptWhenTheModelHasAPropertyByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it overwrites the creation time `crdate` with 0, the value of the int property, right after the insert, see Forge #94961.');

        // When: a stamp is created and persisted
        // @phpstan-ignore deadCode.unreachable
        $value = new Stamp();
        $value->setTitle('Stamped');
        $this->get(StampRepository::class)->add($value);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the current time DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldTypeValues/CreationTime.csv');
    }
}
