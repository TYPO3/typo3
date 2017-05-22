<?php
namespace TYPO3\CMS\Core\Tests\Functional;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;

/**
 * Test case
 */
class RegistryTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{

    /**
     * @test
     */
    public function getReturnsNullIfEntryIsNotInDatabase()
    {
        $this->assertNull((new Registry())->get('myExtension', 'myKey'));
    }

    /**
     * @test
     */
    public function getReturnsDefaultValueIfEntryIsNotInDatabase()
    {
        $this->assertSame('myDefault', (new Registry())->get('myExtension', 'myKey', 'myDefault'));
    }

    /**
     * @test
     */
    public function getReturnsEntryFromDatabase()
    {
        (new ConnectionPool())->getConnectionForTable('sys_registry')
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
        $this->assertSame('myValue', (new Registry())->get('myExtension', 'myKey'));
    }

    /**
     * @test
     */
    public function setInsertsEntryInDatabase()
    {
        (new Registry())->set('myExtension', 'myKey', 'myValue');
        $valueInDatabase = (new ConnectionPool())->getConnectionForTable('sys_registry')
            ->select(
                ['entry_value'],
                'sys_registry',
                ['entry_namespace' => 'myExtension', 'entry_key' => 'myKey']
            )
            ->fetch();
        $this->assertSame('myValue', unserialize($valueInDatabase['entry_value']));
    }

    /**
     * @test
     */
    public function setOverridesExistingEntryInDatabase()
    {
        (new ConnectionPool())->getConnectionForTable('sys_registry')
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
        (new Registry())->set('myExtension', 'myKey', 'myNewValue');
        $valueInDatabase = (new ConnectionPool())->getConnectionForTable('sys_registry')
            ->select(
                ['entry_value'],
                'sys_registry',
                ['entry_namespace' => 'myExtension', 'entry_key' => 'myKey']
            )
            ->fetch();
        $this->assertSame('myNewValue', unserialize($valueInDatabase['entry_value']));
    }

    /**
     * @test
     */
    public function removeDeletesEntryInDatabaseButLeavesOthers()
    {
        $connection = (new ConnectionPool())->getConnectionForTable('sys_registry');
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

        (new Registry())->remove('ns1', 'k1');

        $this->assertSame(0, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns1', 'entry_key' => 'k1']));
        $this->assertSame(1, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns1', 'entry_key' => 'k2']));
        $this->assertSame(1, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns2', 'entry_key' => 'k1']));
    }

    /**
     * @test
     */
    public function removeAllByNamespaceDeletesEntryInDatabaseAndLeavesOthers()
    {
        $connection = (new ConnectionPool())->getConnectionForTable('sys_registry');
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

        (new Registry())->removeAllByNamespace('ns1');

        $this->assertSame(0, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns1', 'entry_key' => 'k1']));
        $this->assertSame(0, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns1', 'entry_key' => 'k2']));
        $this->assertSame(1, $connection->count('*', 'sys_registry', ['entry_namespace' => 'ns2', 'entry_key' => 'k1']));
    }

    /**
     * @test
     */
    public function canGetSetEntry()
    {
        $registry = new Registry();
        $registry->set('ns1', 'key1', 'value1');
        $this->assertSame('value1', $registry->get('ns1', 'key1'));
    }

    /**
     * @test
     */
    public function getReturnsNewValueIfValueHasBeenSetMultipleTimes()
    {
        $registry = new Registry();
        $registry->set('ns1', 'key1', 'value1');
        $registry->set('ns1', 'key1', 'value2');
        $this->assertSame('value2', $registry->get('ns1', 'key1'));
    }

    /**
     * @test
     */
    public function canNotGetRemovedEntry()
    {
        $registry = new Registry();
        $registry->set('ns1', 'key1', 'value1');
        $registry->remove('ns1', 'key1');
        $this->assertNull($registry->get('ns1', 'key1'));
    }

    /**
     * @test
     */
    public function canNotGetRemovedAllByNamespaceEntry()
    {
        $registry = new Registry();
        $registry->set('ns1', 'key1', 'value1');
        $registry->removeAllByNamespace('ns1');
        $this->assertNull($registry->get('ns1', 'key1'));
    }
}
