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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
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
        self::assertSame(['nullable' => true, 'values' => [1, 2]], json_decode($row['nullable_typed_json_field'], true));
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
        self::assertFalse($reconstituted->_isDirty());
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

    private function fetchRawRow(string $tableName, int $uid): array
    {
        return $this->get(ConnectionPool::class)
            ->getConnectionForTable($tableName)
            ->select(['*'], $tableName, ['uid' => $uid])
            ->fetchAssociative();
    }
}
