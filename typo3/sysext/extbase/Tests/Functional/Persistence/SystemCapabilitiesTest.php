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
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestDataMapper\Domain\Model\CapabilitiesValueObject;
use TYPO3Tests\TestDataMapper\Domain\Model\ExplicitSystemCapabilities;
use TYPO3Tests\TestDataMapper\Domain\Model\SystemCapabilities;

final class SystemCapabilitiesTest extends FunctionalTestCase
{
    private const TABLE_NAME = 'tx_testdatamapper_domain_model_system_capabilities';

    private const EXPLICIT_COLUMNS_TABLE_NAME = 'tx_testdatamapper_domain_model_explicit_system_capabilities';

    private const VALUE_OBJECT_TABLE_NAME = 'tx_testdatamapper_domain_model_capabilities_value_object';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/test_data_mapper',
    ];

    private PersistenceManager $persistenceManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->persistenceManager = $this->get(PersistenceManager::class);
        $request = new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
    }

    #[Test]
    public function fieldsOnlyDefinedViaCtrlCapabilitiesAreMappedButNotPersistable(): void
    {
        $dataMap = $this->get(DataMapFactory::class)->buildDataMap(SystemCapabilities::class);

        // Fields that only exist because of a "ctrl" capability are readable but must never
        // be written back by extbase.
        foreach (['deleted', 'crdate', 'tstamp', 'sorting'] as $propertyName) {
            self::assertNotNull($dataMap->getColumnMap($propertyName), $propertyName . ' should be mapped');
            self::assertFalse($dataMap->isPersistableProperty($propertyName), $propertyName . ' must not be persistable');
        }

        // Fields backed by a real TCA "columns" definition keep today's behaviour.
        foreach (['hidden', 'description', 'starttime', 'endtime', 'feGroup', 'editlock'] as $propertyName) {
            self::assertNotNull($dataMap->getColumnMap($propertyName), $propertyName . ' should be mapped');
            self::assertTrue($dataMap->isPersistableProperty($propertyName), $propertyName . ' should stay persistable');
        }
    }

    #[Test]
    public function ctrlOnlyFieldsAreNotWrittenOnInsert(): void
    {
        $subject = new SystemCapabilities(
            description: 'description',
            tstamp: new \DateTime('@100'),
            crdate: new \DateTime('@100'),
            deleted: true,
            sorting: 99,
            hidden: false,
            starttime: null,
            endtime: null,
            feGroup: null,
            editlock: false,
        );

        $this->persistenceManager->add($subject);
        $this->persistenceManager->persistAll();

        $row = $this->getRow(1);
        self::assertSame(0, (int)$row['deleted']);
        self::assertSame(0, (int)$row['sorting']);
        self::assertSame($GLOBALS['EXEC_TIME'], (int)$row['crdate']);
        self::assertSame($GLOBALS['EXEC_TIME'], (int)$row['tstamp']);
    }

    #[Test]
    public function ctrlOnlyFieldsAreNotWrittenOnUpdateWhileColumnBackedFieldsAre(): void
    {
        $this->insertRow();

        $subject = $this->persistenceManager->getObjectByIdentifier(1, SystemCapabilities::class);
        self::assertInstanceOf(SystemCapabilities::class, $subject);

        $subject->deleted = true;
        $subject->crdate = new \DateTime('@999');
        $subject->tstamp = new \DateTime('@999');
        $subject->sorting = 42;
        // These are backed by real TCA columns and must remain writable.
        $subject->hidden = true;
        $subject->description = 'changed';

        $this->persistenceManager->update($subject);
        $this->persistenceManager->persistAll();

        $row = $this->getRow(1);
        self::assertSame(0, (int)$row['deleted']);
        self::assertSame(500, (int)$row['crdate']);
        self::assertSame(7, (int)$row['sorting']);
        self::assertSame($GLOBALS['EXEC_TIME'], (int)$row['tstamp']);
        self::assertSame(1, (int)$row['hidden']);
        self::assertSame('changed', $row['description']);
    }

    #[Test]
    public function ctrlOnlyDeletedFieldDoesNotBlockRemove(): void
    {
        $this->insertRow();

        $subject = $this->persistenceManager->getObjectByIdentifier(1, SystemCapabilities::class);
        self::assertInstanceOf(SystemCapabilities::class, $subject);

        $this->persistenceManager->remove($subject);
        $this->persistenceManager->persistAll();

        self::assertSame(1, (int)$this->getRow(1)['deleted']);
    }

    #[Test]
    public function nonPersistableFieldsDoNotAffectValueObjectIdentity(): void
    {
        $this->getConnectionForTable(self::VALUE_OBJECT_TABLE_NAME)->insert(self::VALUE_OBJECT_TABLE_NAME, [
            'uid' => 1,
            'pid' => 0,
            'name' => 'value',
            'tstamp' => 1000,
            'crdate' => 500,
        ]);

        $subject = new CapabilitiesValueObject('value');
        self::assertNull($subject->tstamp);
        self::assertNull($subject->crdate);

        $uid = $this->get(Typo3DbBackend::class)->getUidOfAlreadyPersistedValueObject($subject);

        self::assertSame(1, $uid);
    }

    #[Test]
    public function explicitlyDeclaredColumnsKeepCtrlFieldsMappedAndPersistable(): void
    {
        $dataMap = $this->get(DataMapFactory::class)->buildDataMap(ExplicitSystemCapabilities::class);

        foreach (['tstamp', 'crdate', 'deleted', 'sorting'] as $propertyName) {
            self::assertNotNull($dataMap->getColumnMap($propertyName), $propertyName . ' should be mapped');
            self::assertTrue($dataMap->isPersistableProperty($propertyName), $propertyName . ' declared in columns must stay persistable');
        }
    }

    #[Test]
    public function explicitlyDeclaredColumnsKeepOldWritableBehaviour(): void
    {
        $this->getConnectionForTable(self::EXPLICIT_COLUMNS_TABLE_NAME)->insert(self::EXPLICIT_COLUMNS_TABLE_NAME, [
            'uid' => 1,
            'pid' => 0,
            'tstamp' => 1000,
            'crdate' => 500,
            'deleted' => 0,
            'sorting' => 7,
        ]);

        $subject = $this->persistenceManager->getObjectByIdentifier(1, ExplicitSystemCapabilities::class);
        self::assertInstanceOf(ExplicitSystemCapabilities::class, $subject);
        self::assertSame('500', $subject->crdate->format('U'));
        self::assertSame('1000', $subject->tstamp->format('U'));
        self::assertSame(7, $subject->sorting);

        $subject->deleted = true;
        $subject->crdate = new \DateTime('@999');
        $subject->sorting = 42;

        $this->persistenceManager->update($subject);
        $this->persistenceManager->persistAll();

        $row = $this->getRowFromTable(self::EXPLICIT_COLUMNS_TABLE_NAME, 1);
        self::assertSame(1, (int)$row['deleted']);
        self::assertSame(999, (int)$row['crdate']);
        self::assertSame(42, (int)$row['sorting']);
        self::assertSame($GLOBALS['EXEC_TIME'], (int)$row['tstamp']);
    }

    private function insertRow(): void
    {
        $this->getConnection()->insert(self::TABLE_NAME, [
            'uid' => 1,
            'pid' => 0,
            'description' => 'description',
            'tstamp' => 1000,
            'crdate' => 500,
            'deleted' => 0,
            'sorting' => 7,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => '-1',
            'editlock' => 0,
        ]);
    }

    private function getConnection(): Connection
    {
        return $this->getConnectionForTable(self::TABLE_NAME);
    }

    private function getConnectionForTable(string $tableName): Connection
    {
        return $this->get(ConnectionPool::class)->getConnectionForTable($tableName);
    }

    /**
     * @return array<string, mixed>
     */
    private function getRow(int $uid): array
    {
        return $this->getRowFromTable(self::TABLE_NAME, $uid);
    }

    /**
     * Reads a row bypassing enable field restrictions.
     *
     * @return array<string, mixed>
     */
    private function getRowFromTable(string $tableName, int $uid): array
    {
        $queryBuilder = $this->getConnectionForTable($tableName)->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)));
        $row = $queryBuilder->executeQuery()->fetchAssociative();
        self::assertIsArray($row);
        return $row;
    }
}
