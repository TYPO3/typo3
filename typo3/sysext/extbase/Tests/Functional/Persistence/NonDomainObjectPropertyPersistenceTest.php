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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\CleanStateTracker;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Enum\AgeGroup;
use TYPO3Tests\BlogExample\Domain\Model\Person;
use TYPO3Tests\TestJsonFields\Domain\Model\Example;
use TYPO3Tests\TestJsonFields\Domain\Repository\ExampleRepository;
use TYPO3Tests\TestJsonFields\Domain\Type\JsonValue;

/**
 * Model properties typed with a class that is not a domain object (backed enums,
 * TypeInterface value objects) are persisted inline and must not be handled as
 * a relation: Otherwise a property left as null is written as 0 on INSERT.
 */
final class NonDomainObjectPropertyPersistenceTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/test_json_fields',
    ];

    private PersistenceManager $persistenceManager;

    protected function setUp(): void
    {
        parent::setUp();
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $this->persistenceManager = $this->get(PersistenceManager::class);
    }

    #[Test]
    public function nullNullableEnumIsPersistedAsNull(): void
    {
        $person = new Person('John', 'Doe', 'john@example.com');
        self::assertNull($person->getAgeGroup());

        $this->persistenceManager->add($person);
        self::assertNull($person->getAgeGroup());

        $this->persistenceManager->persistAll();
        self::assertNull($person->getAgeGroup());

        $row = $this->fetchRawRow('tx_blogexample_domain_model_person', 1);
        self::assertNull($row['age_group'], 'Nullable backed enum was persisted as ' . var_export($row['age_group'], true) . ' instead of NULL');
    }

    #[Test]
    public function nullNullableScalarIsPersistedAsNull(): void
    {
        $person = new Person('John', 'Doe', 'john@example.com');
        self::assertNull($person->getAge());

        $this->persistenceManager->add($person);
        $this->persistenceManager->persistAll();

        $row = $this->fetchRawRow('tx_blogexample_domain_model_person', 1);
        self::assertNull($row['age'], 'Nullable int was persisted as ' . var_export($row['age'], true) . ' instead of NULL');
    }

    #[Test]
    public function setNullableEnumIsPersistedAsBackingValue(): void
    {
        $person = new Person('John', 'Doe', 'john@example.com');
        $person->setAgeGroup(AgeGroup::Baby);

        $this->persistenceManager->add($person);
        $this->persistenceManager->persistAll();

        $row = $this->fetchRawRow('tx_blogexample_domain_model_person', 1);
        self::assertSame(0, (int)$row['age_group']);
    }

    #[Test]
    public function setNullableEnumIsPersistedAsBackingValueLargerThanZero(): void
    {
        $person = new Person('Sr. John', 'Doe', 'john@example.com');
        $person->setAgeGroup(AgeGroup::Senior);

        $this->persistenceManager->add($person);
        $this->persistenceManager->persistAll();

        $row = $this->fetchRawRow('tx_blogexample_domain_model_person', 1);
        self::assertSame(3, (int)$row['age_group']);
    }

    #[Test]
    public function nullNullableCoreTypeIsPersistedAsNull(): void
    {
        $example = new Example();
        $example->setTitle('test');
        self::assertNull($example->getNullableTypedJsonField());

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        self::assertNull($example->getNullableTypedJsonField());

        $row = $this->fetchRawRow('tx_testjsonfields_domain_model_example', 1);
        self::assertNull($row['nullable_typed_json_field'], 'Nullable core type was persisted as ' . var_export($row['nullable_typed_json_field'], true) . ' instead of NULL');
    }

    #[Test]
    public function setNullableCoreTypeIsPersistedAsStringRepresentation(): void
    {
        $example = new Example();
        $example->setTitle('test');
        $example->setNullableTypedJsonField(new JsonValue(['nullable' => true, 'values' => [1, 2]]));

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();

        $row = $this->fetchRawRow('tx_testjsonfields_domain_model_example', 1);
        self::assertIsString($row['nullable_typed_json_field']);
        // MySQL normalizes the key order of native JSON columns, therefore the keys are compared order-insensitive.
        self::assertEquals(['nullable' => true, 'values' => [1, 2]], json_decode($row['nullable_typed_json_field'], true));
    }

    #[Test]
    public function coreTypeIsPersistedAsStringRepresentation(): void
    {
        $example = new Example();
        $example->setTitle('test');
        $example->setTypedJsonField(new JsonValue(['foo' => 'bar']));

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();

        $row = $this->fetchRawRow('tx_testjsonfields_domain_model_example', 1);
        self::assertIsString($row['typed_json_field']);
        self::assertSame(['foo' => 'bar'], json_decode($row['typed_json_field'], true));
    }

    #[Test]
    public function coreTypePropertiesAreReconstitutedFromPersistedValues(): void
    {
        $example = new Example();
        $example->setTitle('test');
        $example->setTypedJsonField(new JsonValue(['foo' => 'bar']));
        $example->setNullableTypedJsonField(new JsonValue(['nullable' => true]));

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $reconstituted = $this->get(ExampleRepository::class)->findByUid(1);
        self::assertInstanceOf(Example::class, $reconstituted);
        self::assertNotSame($example, $reconstituted);
        self::assertSame(['foo' => 'bar'], $reconstituted->getTypedJsonField()->toArray());
        self::assertInstanceOf(JsonValue::class, $reconstituted->getNullableTypedJsonField());
        self::assertSame(['nullable' => true], $reconstituted->getNullableTypedJsonField()->toArray());
        self::assertFalse($this->get(CleanStateTracker::class)->isEntityDirty($reconstituted));
    }

    #[Test]
    public function nullNullableCoreTypeIsReconstitutedAsNull(): void
    {
        $example = new Example();
        $example->setTitle('test');

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $reconstituted = $this->get(ExampleRepository::class)->findByUid(1);
        self::assertInstanceOf(Example::class, $reconstituted);
        self::assertNull($reconstituted->getNullableTypedJsonField());
        self::assertSame([], $reconstituted->getTypedJsonField()->toArray());
    }

    /**
     * An array property mapped to a TCA json column is stored as a JSON string.
     */
    #[Test]
    public function arrayIsPersistedAsJson(): void
    {
        // Given an example with an array value
        $example = new Example();
        $example->setTitle('test');
        $example->setArrayJsonField(['foo' => 'bar', 'list' => [1, 2, 3]]);

        // When the example is persisted
        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();

        // Then the column contains the array as JSON (MySQL normalizes the key order of native JSON)
        $row = $this->fetchRawRow('tx_testjsonfields_domain_model_example', 1);
        self::assertIsString($row['array_json_field']);
        self::assertEquals(['foo' => 'bar', 'list' => [1, 2, 3]], json_decode($row['array_json_field'], true));
    }

    /**
     * A JSON string stored in a TCA json column is decoded into an array property.
     */
    #[Test]
    public function jsonIsReconstitutedAsArray(): void
    {
        // Given a persisted example with array values
        $example = new Example();
        $example->setTitle('test');
        $example->setArrayJsonField(['foo' => 'bar', 'list' => [1, 2, 3]]);
        $example->setNullableArrayJsonField(['nullable' => true]);
        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        // When the example is read again
        $reconstituted = $this->get(ExampleRepository::class)->findByUid(1);

        // Then the properties hold the decoded arrays and the object is clean
        self::assertInstanceOf(Example::class, $reconstituted);
        self::assertNotSame($example, $reconstituted);
        self::assertEquals(['foo' => 'bar', 'list' => [1, 2, 3]], $reconstituted->getArrayJsonField());
        self::assertSame(['nullable' => true], $reconstituted->getNullableArrayJsonField());
        self::assertFalse($this->get(CleanStateTracker::class)->isEntityDirty($reconstituted));
    }

    /**
     * A changed array property marks the entity dirty and the new value is written as JSON.
     */
    #[Test]
    public function updatedArrayIsPersistedAsJson(): void
    {
        // Given a persisted example that is read again
        $example = new Example();
        $example->setTitle('test');
        $example->setArrayJsonField(['foo' => 'bar']);
        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $reconstituted = $this->get(ExampleRepository::class)->findByUid(1);
        self::assertInstanceOf(Example::class, $reconstituted);

        // When the array is changed and persisted
        $reconstituted->setArrayJsonField(['foo' => 'baz', 'added' => [true]]);
        self::assertTrue($this->get(CleanStateTracker::class)->isEntityDirty($reconstituted));
        $this->get(ExampleRepository::class)->update($reconstituted);
        $this->persistenceManager->persistAll();

        // Then the column contains the new array
        $row = $this->fetchRawRow('tx_testjsonfields_domain_model_example', 1);
        self::assertEquals(['foo' => 'baz', 'added' => [true]], json_decode($row['array_json_field'], true));
    }

    /**
     * A nullable array property left at null is stored as SQL NULL and read back as null.
     */
    #[Test]
    public function nullNullableArrayIsPersistedAsNullAndReconstitutedAsNull(): void
    {
        // Given an example with a nullable array that is null
        $example = new Example();
        $example->setTitle('test');
        self::assertNull($example->getNullableArrayJsonField());

        // When the example is persisted and read again
        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $row = $this->fetchRawRow('tx_testjsonfields_domain_model_example', 1);
        $reconstituted = $this->get(ExampleRepository::class)->findByUid(1);

        // Then the column is NULL and the property is null
        self::assertNull($row['nullable_array_json_field'], 'Nullable array was persisted as ' . var_export($row['nullable_array_json_field'], true) . ' instead of NULL');
        self::assertInstanceOf(Example::class, $reconstituted);
        self::assertNull($reconstituted->getNullableArrayJsonField());
        self::assertSame([], $reconstituted->getArrayJsonField());
    }

    /**
     * A nullable array that is set to null after it had a value is stored as SQL NULL.
     */
    #[Test]
    public function nullableArraySetToNullIsPersistedAsNull(): void
    {
        // Given a persisted example with a nullable array value
        $example = new Example();
        $example->setTitle('test');
        $example->setNullableArrayJsonField(['foo' => 'bar']);
        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $reconstituted = $this->get(ExampleRepository::class)->findByUid(1);
        self::assertInstanceOf(Example::class, $reconstituted);

        // When the array is set to null and persisted
        $reconstituted->setNullableArrayJsonField(null);
        $this->get(ExampleRepository::class)->update($reconstituted);
        $this->persistenceManager->persistAll();

        // Then the column is NULL
        $row = $this->fetchRawRow('tx_testjsonfields_domain_model_example', 1);
        self::assertNull($row['nullable_array_json_field']);
    }

    /**
     * Nested arrays and floats with a zero fraction survive a round trip.
     */
    #[Test]
    public function nestedArrayAndFloatSurviveRoundTrip(): void
    {
        // Given an example with a nested array containing a float with a zero fraction
        $value = ['level1' => ['level2' => ['float' => 1.0, 'list' => ['a', 'b']], 'flag' => false], 'empty' => []];
        $example = new Example();
        $example->setTitle('test');
        $example->setArrayJsonField($value);

        // When the example is persisted and read again
        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $reconstituted = $this->get(ExampleRepository::class)->findByUid(1);

        // Then the structure is identical, the float stays a float
        self::assertInstanceOf(Example::class, $reconstituted);
        self::assertEquals($value, $reconstituted->getArrayJsonField());
        self::assertIsFloat($reconstituted->getArrayJsonField()['level1']['level2']['float']);
    }

    /**
     * JSON that does not decode to an array leaves a non-nullable array property at
     * its default value and sets a nullable one to null.
     */
    #[Test]
    public function scalarJsonIsReconstitutedAsDefaultOrNull(): void
    {
        // Given a persisted example whose JSON columns contain scalar JSON values
        $example = new Example();
        $example->setTitle('test');
        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();
        $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_testjsonfields_domain_model_example')
            ->update(
                'tx_testjsonfields_domain_model_example',
                ['array_json_field' => '"text"', 'nullable_array_json_field' => '42'],
                ['uid' => 1],
                ['array_json_field' => Connection::PARAM_STR, 'nullable_array_json_field' => Connection::PARAM_STR]
            );

        // When the example is read again
        $reconstituted = $this->get(ExampleRepository::class)->findByUid(1);

        // Then the non-nullable property keeps its default and the nullable one is null
        self::assertInstanceOf(Example::class, $reconstituted);
        self::assertSame([], $reconstituted->getArrayJsonField());
        self::assertNull($reconstituted->getNullableArrayJsonField());
    }

    private function fetchRawRow(string $tableName, int $uid): array
    {
        return $this->get(ConnectionPool::class)
            ->getConnectionForTable($tableName)
            ->select(['*'], $tableName, ['uid' => $uid])
            ->fetchAssociative();
    }
}
