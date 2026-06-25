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

namespace TYPO3\CMS\Core\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Serializer\DenyListDeserializer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RegistryTest extends FunctionalTestCase
{
    private Registry $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->get(Registry::class);
    }

    #[Test]
    public function getReturnsNullIfEntryIsNotInDatabase(): void
    {
        self::assertNull($this->subject->get('myExtension', 'myKey'));
    }

    #[Test]
    public function getReturnsDefaultValueIfEntryIsNotInDatabase(): void
    {
        self::assertSame('myDefault', $this->subject->get('myExtension', 'myKey', 'myDefault'));
    }

    #[Test]
    public function getReturnsEntryFromDatabase(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_registry')
            ->insert(
                'sys_registry',
                [
                    'entry_namespace' => 'myExtension',
                    'entry_key' => 'myKey',
                    'entry_value' => serialize('myValue'),
                ],
                [
                    'entry_value' => Connection::PARAM_LOB,
                ]
            );
        self::assertSame('myValue', $this->subject->get('myExtension', 'myKey'));
    }

    #[Test]
    public function setInsertsEntryInDatabase(): void
    {
        $this->subject->set('myExtension', 'myKey', 'myValue');
        $valueInDatabase = $this->get(ConnectionPool::class)->getConnectionForTable('sys_registry')
            ->select(
                ['entry_value'],
                'sys_registry',
                ['entry_namespace' => 'myExtension', 'entry_key' => 'myKey']
            )
            ->fetchAssociative();
        self::assertSame('myValue', $this->deserialize($valueInDatabase['entry_value']));
    }

    #[Test]
    public function setOverridesExistingEntryInDatabase(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_registry')
            ->insert(
                'sys_registry',
                [
                    'entry_namespace' => 'myExtension',
                    'entry_key' => 'myKey',
                    'entry_value' => serialize('myValue'),
                ],
                [
                    'entry_value' => Connection::PARAM_LOB,
                ]
            );
        $this->subject->set('myExtension', 'myKey', 'myNewValue');
        $valueInDatabase = $this->get(ConnectionPool::class)->getConnectionForTable('sys_registry')
            ->select(
                ['entry_value'],
                'sys_registry',
                ['entry_namespace' => 'myExtension', 'entry_key' => 'myKey']
            )
            ->fetchAssociative();
        self::assertSame('myNewValue', $this->deserialize($valueInDatabase['entry_value']));
    }

    #[Test]
    public function removeDeletesEntryInDatabaseButLeavesOthers(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_registry');
        $connection->bulkInsert(
            'sys_registry',
            [
                ['ns1', 'k1', serialize('v1')],
                ['ns1', 'k2', serialize('v2')],
                ['ns2', 'k1', serialize('v1')],
            ],
            ['entry_namespace', 'entry_key', 'entry_value'],
            [
                'entry_value' => Connection::PARAM_LOB,
            ]
        );

        $this->subject->remove('ns1', 'k1');

        self::assertSame(0, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns1', 'entry_key' => 'k1']));
        self::assertSame(1, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns1', 'entry_key' => 'k2']));
        self::assertSame(1, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns2', 'entry_key' => 'k1']));
    }

    #[Test]
    public function removeAllByNamespaceDeletesEntryInDatabaseAndLeavesOthers(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_registry');
        $connection->bulkInsert(
            'sys_registry',
            [
                ['ns1', 'k1', serialize('v1')],
                ['ns1', 'k2', serialize('v2')],
                ['ns2', 'k1', serialize('v1')],
            ],
            ['entry_namespace', 'entry_key', 'entry_value'],
            [
                'entry_value' => Connection::PARAM_LOB,
            ]
        );

        $this->subject->removeAllByNamespace('ns1');

        self::assertSame(0, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns1', 'entry_key' => 'k1']));
        self::assertSame(0, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns1', 'entry_key' => 'k2']));
        self::assertSame(1, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns2', 'entry_key' => 'k1']));
    }

    #[Test]
    public function canGetSetEntry(): void
    {
        $this->subject->set('ns1', 'key1', 'value1');
        self::assertSame('value1', $this->subject->get('ns1', 'key1'));
    }

    #[Test]
    public function canGetEntryWithClassInstance(): void
    {
        $object = new \stdClass();
        $object->foo = 'bar';

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_registry');
        $connection->bulkInsert(
            'sys_registry',
            [
                ['ns1', 'key1', serialize($object)],
                ['ns2', 'key1', serialize($object)],
            ],
            ['entry_namespace', 'entry_key', 'entry_value'],
            [
                'entry_value' => Connection::PARAM_LOB,
            ]
        );

        // first hit for stdClass (not in DenyListSerializer cache)
        $result = $this->subject->get('ns1', 'key1');
        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame($object->foo, $result->foo);

        // second hit for stdClass (should come from DenyListSerializer cache)
        $result = $this->subject->get('ns2', 'key1');
        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame($object->foo, $result->foo);
    }

    #[Test]
    public function getReturnsNewValueIfValueHasBeenSetMultipleTimes(): void
    {
        $this->subject->set('ns1', 'key1', 'value1');
        $this->subject->set('ns1', 'key1', 'value2');
        self::assertSame('value2', $this->subject->get('ns1', 'key1'));
    }

    #[Test]
    public function canNotGetRemovedEntry(): void
    {
        $this->subject->set('ns1', 'key1', 'value1');
        $this->subject->remove('ns1', 'key1');
        self::assertNull($this->subject->get('ns1', 'key1'));
    }

    #[Test]
    public function canNotGetRemovedAllByNamespaceEntry(): void
    {
        $this->subject->set('ns1', 'key1', 'value1');
        $this->subject->removeAllByNamespace('ns1');
        self::assertNull($this->subject->get('ns1', 'key1'));
    }

    private function deserialize(string $serialized): mixed
    {
        return $this->get(DenyListDeserializer::class)->deserialize($serialized);
    }
}
