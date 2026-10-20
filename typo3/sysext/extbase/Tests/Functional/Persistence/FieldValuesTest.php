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

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\FieldValueRepository;

/**
 * Field values: what TCA normalises and validates when a value is written.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle
 * (DataHandler, suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair
 * start from the same fixture in Fixtures/FieldValues/Given/ and assert the field value table against
 * the same result CSV in Fixtures/FieldValues/. Both run as the same administrator at the same
 * fixed time in the time zone UTC. An Extbase test that differs from core is skipped.
 *
 * The DataHandler is given the raw string, as the backend form sends it. Extbase is given the
 * value through the setter of a model property, typed the way an extension author would type it.
 *
 * The table `tx_blogexample_domain_model_fieldvalue` has one column per check: an input with `eval` `trim`, an input
 * with `max` 10, a text field with `max` 10, two number fields (one with `range.lower` 1), a required input, an input
 * with `eval` `unique`, an input with `eval` `uniqueInPid`, an email field and a datetime field. Fixture Entries.csv:
 * field value 1 holds the values that field value 2 later collides with, field value 2 is the record that is changed.
 */
final class FieldValuesTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    private string $timezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Base.csv');
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
     * The value "  Title  " (two spaces on either side) is sent for the input field `trimmed` of field value 2 "Second
     * value", which has `eval` `trim`. DataHandler stores "Title".
     *
     * @see aValueWithSurroundingSpacesIsTrimmedByExtbase()
     */
    #[Test]
    public function aValueWithSurroundingSpacesIsTrimmedByCore(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');

        // When: the value is sent for field value 2 as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [2 => ['trimmed' => '  Title  ']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the value it stored
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Trimmed.csv');
    }

    /**
     * Extbase stores "  Title  " with both spaces on either side, `eval` `trim` is not applied. The value comes from a
     * string property.
     *
     * @see aValueWithSurroundingSpacesIsTrimmedByCore()
     */
    #[Test]
    public function aValueWithSurroundingSpacesIsTrimmedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the value untrimmed, the `eval` `trim` of the field is not applied.');

        // Given: field values 1 "First value" and 2 "Second value" exist
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');
        $fieldValue = $this->get(FieldValueRepository::class)->findByUid(2);

        // When: the value is set on field value 2 and persisted
        $fieldValue->setTrimmed('  Title  ');
        $this->get(FieldValueRepository::class)->update($fieldValue);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Trimmed.csv');
    }

    /**
     * A value of 20 characters ("01234567890123456789") is sent for the input field `limited` of field value 2 "Second
     * value", which has `max` 10. DataHandler cuts the value to its first 10 characters and stores "0123456789".
     *
     * @see aValueLongerThanMaxIsCutOffByExtbase()
     */
    #[Test]
    public function aValueLongerThanMaxIsCutOffByCore(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');

        // When: the value is sent for field value 2 as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [2 => ['limited' => '01234567890123456789']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the value it stored
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/CutOff.csv');
    }

    /**
     * Extbase stores all 20 characters, `max` of the field is not applied. The value comes from a string property.
     *
     * @see aValueLongerThanMaxIsCutOffByCore()
     */
    #[Test]
    public function aValueLongerThanMaxIsCutOffByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores all 20 characters, the `max` of the field is not applied.');

        // Given: field values 1 "First value" and 2 "Second value" exist
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');
        $fieldValue = $this->get(FieldValueRepository::class)->findByUid(2);

        // When: the value is set on field value 2 and persisted
        $fieldValue->setLimited('01234567890123456789');
        $this->get(FieldValueRepository::class)->update($fieldValue);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/CutOff.csv');
    }

    /**
     * A value of 20 characters ("01234567890123456789") is sent for the text field `summary` of field value 2 "Second
     * value", which has `max` 10. DataHandler cuts only input fields to `max`, so it stores all 20 characters.
     *
     * @see aTextLongerThanMaxIsNotCutOffByExtbase()
     */
    #[Test]
    public function aTextLongerThanMaxIsNotCutOffByCore(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');

        // When: the value is sent for field value 2 as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [2 => ['summary' => '01234567890123456789']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds all 20 characters
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/NotCutOffText.csv');
    }

    /**
     * Extbase stores all 20 characters of the text field `summary`, which has `max` 10, like DataHandler. The value
     * comes from a string property.
     *
     * @see aTextLongerThanMaxIsNotCutOffByCore()
     */
    #[Test]
    public function aTextLongerThanMaxIsNotCutOffByExtbase(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');
        $fieldValue = $this->get(FieldValueRepository::class)->findByUid(2);

        // When: the value is set on field value 2 and persisted
        $fieldValue->setSummary('01234567890123456789');
        $this->get(FieldValueRepository::class)->update($fieldValue);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds all 20 characters
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/NotCutOffText.csv');
    }

    /**
     * The value "abc" is sent for the number field `serial` of field value 2 "Second value", which holds 5. DataHandler
     * casts the value to an integer and stores 0.
     *
     * @see aNonNumericValueInANumberFieldIsStoredAsZeroByExtbase()
     */
    #[Test]
    public function aNonNumericValueInANumberFieldIsStoredAsZeroByCore(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');

        // When: the value is sent for field value 2 as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [2 => ['serial' => 'abc']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the value it stored
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/NonNumeric.csv');
    }

    /**
     * Extbase passes "abc" on unchanged to the database. An int property cannot hold "abc", so the model maps the
     * number field to a string property. MariaDB and SQLite cast the string to 0 and store it, like DataHandler;
     * PostgreSQL rejects it as invalid input syntax for an integer and Extbase throws an `SqlErrorException`.
     *
     * @see aNonNumericValueInANumberFieldIsStoredAsZeroByCore()
     */
    #[Test]
    public function aNonNumericValueInANumberFieldIsStoredAsZeroByExtbase(): void
    {
        if ($this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_fieldvalue')->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            self::markTestSkipped('Extbase differs from core: on PostgreSQL the database rejects the string "abc" for an integer column.');
        }

        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');
        $fieldValue = $this->get(FieldValueRepository::class)->findByUid(2);

        // When: the value is set on field value 2 and persisted
        $fieldValue->setSerial('abc');
        $this->get(FieldValueRepository::class)->update($fieldValue);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/NonNumeric.csv');
    }

    /**
     * The value 0 is sent for the number field `quantity` of field value 2 "Second value", which has `range.lower` 1.
     * DataHandler raises the value to the lower bound and stores 1.
     *
     * @see aNumberBelowTheLowerRangeIsRaisedToItByExtbase()
     */
    #[Test]
    public function aNumberBelowTheLowerRangeIsRaisedToItByCore(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');

        // When: the value is sent for field value 2 as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [2 => ['quantity' => '0']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the value it stored
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/BelowRange.csv');
    }

    /**
     * Extbase stores 0, the `range` of the field is not applied. The value comes from an int property.
     *
     * @see aNumberBelowTheLowerRangeIsRaisedToItByCore()
     */
    #[Test]
    public function aNumberBelowTheLowerRangeIsRaisedToItByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores 0, the `range.lower` of the field is not applied.');

        // Given: field values 1 "First value" and 2 "Second value" exist
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');
        $fieldValue = $this->get(FieldValueRepository::class)->findByUid(2);

        // When: the value is set on field value 2 and persisted
        $fieldValue->setQuantity(0);
        $this->get(FieldValueRepository::class)->update($fieldValue);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/BelowRange.csv');
    }

    /**
     * An empty string is sent for the required input field `mandatory` of field value 2 "Second value", which holds
     * "Existing". DataHandler discards the value and leaves "Existing" in place.
     *
     * @see anEmptyRequiredValueIsNotStoredByExtbase()
     */
    #[Test]
    public function anEmptyRequiredValueIsNotStoredByCore(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');

        // When: the value is sent for field value 2 as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [2 => ['mandatory' => '']]], []);
        $dataHandler->process_datamap();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/EmptyRequired.csv');
    }

    /**
     * Extbase stores the empty string and overwrites "Existing", `required` of the field is not checked. The value
     * comes from a string property.
     *
     * @see anEmptyRequiredValueIsNotStoredByCore()
     */
    #[Test]
    public function anEmptyRequiredValueIsNotStoredByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the empty string over the existing value, `required` of the field is not checked.');

        // Given: field values 1 "First value" and 2 "Second value" exist
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');
        $fieldValue = $this->get(FieldValueRepository::class)->findByUid(2);

        // When: the value is set on field value 2 and persisted
        $fieldValue->setMandatory('');
        $this->get(FieldValueRepository::class)->update($fieldValue);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/EmptyRequired.csv');
    }

    /**
     * The value "taken", which field value 1 "First value" already holds, is sent for the input field `code` of field
     * value 2 "Second value", which has `eval` `unique`. DataHandler makes the value unique by appending a counter and
     * stores "taken0".
     *
     * @see aDuplicateUniqueValueIsMadeUniqueByExtbase()
     */
    #[Test]
    public function aDuplicateUniqueValueIsMadeUniqueByCore(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');

        // When: the value is sent for field value 2 as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [2 => ['code' => 'taken']]], []);
        $dataHandler->process_datamap();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/DuplicateUnique.csv');
    }

    /**
     * Extbase stores "taken", so field values 1 and 2 hold the same code, `eval` `unique` is not applied. The value
     * comes from a string property.
     *
     * @see aDuplicateUniqueValueIsMadeUniqueByCore()
     */
    #[Test]
    public function aDuplicateUniqueValueIsMadeUniqueByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the duplicate value as it is, the `eval` `unique` of the field is not applied.');

        // Given: field values 1 "First value" and 2 "Second value" exist
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');
        $fieldValue = $this->get(FieldValueRepository::class)->findByUid(2);

        // When: the value is set on field value 2 and persisted
        $fieldValue->setCode('taken');
        $this->get(FieldValueRepository::class)->update($fieldValue);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/DuplicateUnique.csv');
    }

    /**
     * The value "taken", which field value 1 "First value" on the same page already holds, is sent for the input field
     * `reference` of field value 2 "Second value", which has `eval` `uniqueInPid`. DataHandler makes the value unique
     * within the page by appending a counter and stores "taken0".
     *
     * @see aDuplicateValueIsMadeUniqueInThePageByExtbase()
     */
    #[Test]
    public function aDuplicateValueIsMadeUniqueInThePageByCore(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');

        // When: the value is sent for field value 2 as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [2 => ['reference' => 'taken']]], []);
        $dataHandler->process_datamap();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/DuplicateUniqueInPid.csv');
    }

    /**
     * Extbase stores "taken", so field values 1 and 2 on the same page hold the same reference, `eval` `uniqueInPid` is
     * not applied. The value comes from a string property.
     *
     * @see aDuplicateValueIsMadeUniqueInThePageByCore()
     */
    #[Test]
    public function aDuplicateValueIsMadeUniqueInThePageByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the duplicate value as it is, the `eval` `uniqueInPid` of the field is not applied.');

        // Given: field values 1 "First value" and 2 "Second value" exist
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');
        $fieldValue = $this->get(FieldValueRepository::class)->findByUid(2);

        // When: the value is set on field value 2 and persisted
        $fieldValue->setReference('taken');
        $this->get(FieldValueRepository::class)->update($fieldValue);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/DuplicateUniqueInPid.csv');
    }

    /**
     * The value "not-an-email" is sent for the email field `contact` of field value 2 "Second value", which holds
     * "old@example.com". DataHandler rejects the value as no valid email address, stores an empty string in its place
     * and writes an error to `errorLog`.
     *
     * @see anInvalidEmailAddressIsDroppedByExtbase()
     */
    #[Test]
    public function anInvalidEmailAddressIsDroppedByCore(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');

        // When: the value is sent for field value 2 as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [2 => ['contact' => 'not-an-email']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports an error and the table holds the empty value it stored
        self::assertNotSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/InvalidEmail.csv');
    }

    /**
     * Extbase stores "not-an-email" as it is, the email field is not validated. The value comes from a string property.
     *
     * @see anInvalidEmailAddressIsDroppedByCore()
     */
    #[Test]
    public function anInvalidEmailAddressIsDroppedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it stores the invalid address as it is, the email field is not validated.');

        // Given: field values 1 "First value" and 2 "Second value" exist
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');
        $fieldValue = $this->get(FieldValueRepository::class)->findByUid(2);

        // When: the value is set on field value 2 and persisted
        $fieldValue->setContact('not-an-email');
        $this->get(FieldValueRepository::class)->update($fieldValue);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/InvalidEmail.csv');
    }

    /**
     * The ISO 8601 string "2023-11-14T12:00:00+00:00" is sent for the datetime field `published` of field value 2
     * "Second value". The backend form sends the time of the server without an offset, "2023-11-14T13:00:00" in the
     * server time zone Europe/Berlin; the test sends the same instant with an offset, which DataHandler also accepts.
     * DataHandler stores the Unix timestamp 1699963200.
     *
     * @see aDateIsStoredAsTimestampByExtbase()
     */
    #[Test]
    public function aDateIsStoredAsTimestampByCore(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');

        // When: the value is sent for field value 2 as the backend form sends it
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['tx_blogexample_domain_model_fieldvalue' => [2 => ['published' => '2023-11-14T12:00:00+00:00']]], []);
        $dataHandler->process_datamap();

        // Then: DataHandler reports no error and the table holds the value it stored
        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/DateAsTimestamp.csv');
    }

    /**
     * Extbase also stores 1699963200. The value comes from a DateTimeImmutable property, which Extbase converts to a
     * Unix timestamp for the datetime field.
     *
     * @see aDateIsStoredAsTimestampByCore()
     */
    #[Test]
    public function aDateIsStoredAsTimestampByExtbase(): void
    {
        // Given: field values 1 "First value" and 2 "Second value" exist
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FieldValues/Given/Entries.csv');
        $fieldValue = $this->get(FieldValueRepository::class)->findByUid(2);

        // When: the value is set on field value 2 and persisted
        $fieldValue->setPublished(new \DateTimeImmutable('2023-11-14T12:00:00+00:00'));
        $this->get(FieldValueRepository::class)->update($fieldValue);
        $this->get(PersistenceManager::class)->persistAll();

        // Then: the table holds the value DataHandler stored
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FieldValues/DateAsTimestamp.csv');
    }
}
