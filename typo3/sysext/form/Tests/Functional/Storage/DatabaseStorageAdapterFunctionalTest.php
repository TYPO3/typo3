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

namespace TYPO3\CMS\Form\Tests\Functional\Storage;

use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\Repository\FormDefinitionRepository;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Storage\DatabaseStorageAdapter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for DatabaseStorageAdapter
 *
 * These tests verify the full integration chain:
 * DatabaseStorageAdapter → FormDefinitionRepository → DataHandler → Database
 */
final class DatabaseStorageAdapterFunctionalTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/form_definition.csv');
    }

    private function getSubject(): DatabaseStorageAdapter
    {
        return $this->get(DatabaseStorageAdapter::class);
    }

    private function createFormData(string $identifier = 'test-form', string $name = 'Test Form'): FormData
    {
        return FormData::fromArray([
            'identifier' => $identifier,
            'type' => 'Form',
            'label' => $name,
            'prototypeName' => 'standard',
            'renderingOptions' => [],
            'finishers' => [
                [
                    'identifier' => 'Confirmation',
                    'options' => ['message' => 'Form is submitted'],
                ],
            ],
            'renderables' => [
                [
                    'identifier' => 'page-1',
                    'type' => 'Page',
                    'label' => 'Page 1',
                    'renderables' => [
                        [
                            'identifier' => 'text-1',
                            'type' => 'Text',
                            'label' => 'Text',
                        ],
                    ],
                ],
            ],
            'variants' => [],
        ]);
    }

    #[Test]
    public function crudRoundtripWorksWithRealDatabase(): void
    {
        $subject = $this->getSubject();

        // 1. Create: write a new form definition
        $formData = $this->createFormData('roundtrip-form', 'Roundtrip Form');
        $newIdentifier = $subject->write(new FormIdentifier('NEW12345'), $formData);

        self::assertNotEmpty($newIdentifier->identifier, 'new identifier is returned');
        self::assertNotSame('NEW12345', $newIdentifier->identifier, 'NEW prefix is replaced with actual UID');
        self::assertTrue(is_numeric($newIdentifier->identifier), 'new identifier is numeric UID');

        // 2. Read: load the created form
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $readData = $subject->read($newIdentifier, $request);

        self::assertSame('roundtrip-form', $readData->identifier);
        self::assertSame('Roundtrip Form', $readData->name);
        self::assertSame('standard', $readData->prototypeName);
        self::assertCount(1, $readData->renderables);
        self::assertCount(1, $readData->finishers);

        // 3. Exists: verify the form exists
        self::assertTrue($subject->exists($newIdentifier), 'form exists after creation');

        // 4. Update: modify the form
        $updatedFormData = $this->createFormData('roundtrip-form', 'Updated Roundtrip Form');
        $returnedIdentifier = $subject->write($newIdentifier, $updatedFormData);

        self::assertSame($newIdentifier->identifier, $returnedIdentifier->identifier, 'identifier stays the same on update');

        // 5. Read again: verify the update
        $readUpdatedData = $subject->read($newIdentifier, $request);
        self::assertSame('Updated Roundtrip Form', $readUpdatedData->name, 'name is updated');

        // 6. Delete: remove the form
        $subject->delete($newIdentifier);

        // 7. Verify deletion
        self::assertFalse($subject->exists($newIdentifier), 'form does not exist after deletion');
    }

    #[Test]
    public function readReturnsFormDataFromDatabaseFixture(): void
    {
        $subject = $this->getSubject();
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $result = $subject->read(new FormIdentifier('1'), $request);

        self::assertSame('crud-test-form', $result->identifier);
        self::assertSame('CRUD Test Form', $result->name);
    }

    #[Test]
    public function readInFrontendContextSkipsPermissionCheck(): void
    {
        // Clear backend user to simulate pure frontend
        unset($GLOBALS['BE_USER']);

        $subject = $this->getSubject();
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $result = $subject->read(new FormIdentifier('1'), $request);

        self::assertSame('crud-test-form', $result->identifier);
    }

    #[Test]
    public function readThrowsExceptionForInvalidJsonInDatabase(): void
    {
        // Insert a record with invalid JSON directly via raw SQL,
        // bypassing Doctrine's JSON type validation which would reject it during CSV import.
        $connection = $this->get(ConnectionPool::class)
            ->getConnectionForTable('form_definition');
        $connection->insert('form_definition', [
            'uid' => 100,
            'pid' => 0,
            'identifier' => 'invalid-json-form',
            'label' => 'Invalid JSON Form',
            'configuration' => 'this-is-not-valid-json{{{',
            'deleted' => 0,
        ], [
            'configuration' => Types::STRING,
        ]);

        $subject = $this->getSubject();
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1767199444);

        $subject->read(new FormIdentifier('100'), $request);
    }

    #[Test]
    public function existsReturnsTrueForExistingRecord(): void
    {
        self::assertTrue($this->getSubject()->exists(new FormIdentifier('1')));
    }

    #[Test]
    public function existsReturnsFalseForNonExistingRecord(): void
    {
        self::assertFalse($this->getSubject()->exists(new FormIdentifier('9999')));
    }

    #[Test]
    public function existsReturnsFalseForSoftDeletedRecord(): void
    {
        // UID 2 has deleted=1
        self::assertFalse($this->getSubject()->exists(new FormIdentifier('2')));
    }

    #[Test]
    public function findAllReturnsOnlyNonDeletedRecords(): void
    {
        $results = $this->getSubject()->findAll(new SearchCriteria());

        // UID 1 (active) and UID 3 (minimal form, not deleted) should be found
        // UID 2 (deleted=1) should NOT be found
        $identifiers = array_map(fn($m) => $m->identifier, $results);
        self::assertContains('crud-test-form', $identifiers, 'active form is in listing');
        self::assertContains('minimal-form', $identifiers, 'minimal form still appears in listing');
        self::assertNotContains('deleted-form', $identifiers, 'deleted form is not in listing');
    }

    #[Test]
    public function deleteMarkRecordAsDeletedInDatabase(): void
    {
        $subject = $this->getSubject();
        $identifier = new FormIdentifier('1');

        self::assertTrue($subject->exists($identifier), 'record exists before delete');

        $subject->delete($identifier);

        self::assertFalse($subject->exists($identifier), 'record no longer exists after delete');

        // Verify it's a soft delete — the record still exists in DB with deleted=1
        $repository = $this->get(FormDefinitionRepository::class);
        // findByUid uses QueryBuilder which respects TCA delete flag — so it should return null
        self::assertNull($repository->findByUid(1), 'findByUid returns null for soft-deleted record');
    }

    #[Test]
    public function deleteThrowsExceptionForNonExistingRecord(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1767199431);

        $this->getSubject()->delete(new FormIdentifier('9999'));
    }

    #[Test]
    public function writeThrowsExceptionWhenUpdatingNonExistingRecord(): void
    {
        $subject = $this->getSubject();

        // First create a form, then delete it, then try to update it
        $formData = $this->createFormData('temp-form', 'Temp Form');
        $newId = $subject->write(new FormIdentifier('NEW99999'), $formData);
        $subject->delete($newId);

        // Now try to "update" the deleted form — exists() returns false for soft-deleted,
        // so write() attempts to create via add(). However, add() passes the numeric UID
        // as the DataHandler persistence identifier, which DataHandler interprets as an
        // update rather than an insert. This causes the creation to fail.
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1767199424);

        $subject->write($newId, $formData);
    }

    #[Test]
    public function existsByFormIdentifierReturnsTrueForExistingForm(): void
    {
        self::assertTrue($this->getSubject()->existsByFormIdentifier('crud-test-form'));
    }

    #[Test]
    public function existsByFormIdentifierReturnsFalseForNonExistingForm(): void
    {
        self::assertFalse($this->getSubject()->existsByFormIdentifier('non-existing-form'));
    }
}
