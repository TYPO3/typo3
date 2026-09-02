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

namespace TYPO3\CMS\Extbase\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Event\Persistence\EntityAddedToPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityRemovedFromPersistenceEvent;
use TYPO3\CMS\Extbase\Event\Persistence\EntityUpdatedInPersistenceEvent;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceCorrelationScope;

/**
 * Event listener that automatically tracks history for all Extbase domain entities
 * by listening to Extbase persistence events and storing them in sys_history.
 */
final readonly class ExtbaseHistoryTracker
{
    public function __construct(
        private DataMapFactory $dataMapFactory,
        private Context $context,
        private TcaSchemaFactory $tcaSchemaFactory,
        private Features $features,
        private PersistenceCorrelationScope $correlationScope,
    ) {}

    #[AsEventListener('extbase-history-tracker-persisted')]
    public function onEntityPersisted(EntityAddedToPersistenceEvent $event): void
    {
        $this->trackEntityHistory($event, RecordHistoryStore::ACTION_ADD);
    }

    #[AsEventListener('extbase-history-tracker-updated')]
    public function onEntityUpdated(EntityUpdatedInPersistenceEvent $event): void
    {
        $this->trackEntityHistory($event, RecordHistoryStore::ACTION_MODIFY);
    }

    #[AsEventListener('extbase-history-tracker-removed')]
    public function onEntityRemoved(EntityRemovedFromPersistenceEvent $event): void
    {
        $this->trackEntityHistory($event, RecordHistoryStore::ACTION_DELETE);
    }

    private function trackEntityHistory(
        EntityAddedToPersistenceEvent|EntityUpdatedInPersistenceEvent|EntityRemovedFromPersistenceEvent $event,
        int $action
    ): void {
        // Skip history tracking if feature flag is disabled. TCA does not matter in this case.
        if (!$this->features->isFeatureEnabled('extbase.enableHistoryTracking')) {
            return;
        }

        $object = $event->getObject();

        // Skip if object doesn't have a UID (not persisted yet)
        if ($object->getUid() === null) {
            return;
        }

        $dataMap = $this->dataMapFactory->buildDataMap($object::class);
        $tableName = $dataMap->getTableName();

        // Skip if table doesn't exist in TCA schema
        try {
            $schema = $this->tcaSchemaFactory->get($tableName);
        } catch (UndefinedSchemaException) {
            // Table not found in TCA schema, skip history tracking
            return;
        }

        // Skip history tracking if TCA ctrl setting is disabled (defaults to "enabled")
        if (!$schema->hasCapability(TcaSchemaCapability::ExtbaseHistoryTracking)) {
            return;
        }

        $historyStore = $this->createHistoryStore();

        match ($action) {
            RecordHistoryStore::ACTION_ADD => $historyStore->addRecord(
                $tableName,
                $object->getUid(),
                $this->extractObjectData($object, $dataMap),
                $this->correlationScope->get()
            ),
            RecordHistoryStore::ACTION_MODIFY => $historyStore->modifyRecord(
                $tableName,
                $object->getUid(),
                [
                    'oldRecord' => $this->extractObjectData($object, $dataMap, false, true),
                    'newRecord' => $this->extractObjectData($object, $dataMap, false),
                    '_pid' => $object->getPid(),
                    '_extbase_class' => $object::class,
                ],
                $this->correlationScope->get()
            ),
            RecordHistoryStore::ACTION_DELETE => $historyStore->deleteRecord(
                $tableName,
                $object->getUid(),
                $this->correlationScope->get()
            ),
            default => throw new \InvalidArgumentException(
                sprintf('Unsupported history action: %d', $action),
                1774123762
            ),
        };
    }

    private function createHistoryStore(): RecordHistoryStore
    {
        /** @var DateTimeAspect $dateTimeAspect */
        $dateTimeAspect = $this->context->getAspect('date');
        $currentTimestamp = $dateTimeAspect->get('timestamp');

        $userAspect = $this->context->getAspect('frontend.user');

        if ($userAspect->isLoggedIn()) {
            return new RecordHistoryStore(
                RecordHistoryStore::USER_FRONTEND,
                $userAspect->get('id'),
                null,
                $currentTimestamp
            );
        }

        // Check for backend user context
        $backendUserAspect = $this->context->getAspect('backend.user');
        if ($backendUserAspect->isLoggedIn()) {
            return new RecordHistoryStore(
                RecordHistoryStore::USER_BACKEND,
                $backendUserAspect->get('id'),
                null,
                $currentTimestamp
            );
        }

        // Anonymous user
        return new RecordHistoryStore(
            RecordHistoryStore::USER_ANONYMOUS,
            null,
            null,
            $currentTimestamp
        );
    }

    private function extractObjectData(DomainObjectInterface $object, DataMap $dataMap, bool $appendMetadata = true, bool $fetchPropertiesBeforePersistence = false): array
    {
        $data = [];
        if ($fetchPropertiesBeforePersistence && $object instanceof AbstractDomainObject) {
            $properties = $object->_getCleanProperties();
        } else {
            $properties = $object->_getProperties();
        }

        foreach ($properties as $propertyName => $propertyValue) {
            // Get actual database column name:
            $columnMap = $dataMap->getColumnMap($propertyName);
            if ($columnMap !== null) {
                $propertyName = $columnMap->columnName;
            } else {
                $propertyName = GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName);
            }

            // Convert objects and complex types to string representation
            if (is_object($propertyValue)) {
                if ($propertyValue instanceof DomainObjectInterface) {
                    $data[$propertyName] = $propertyValue->getUid();
                } elseif (method_exists($propertyValue, '__toString')) {
                    $data[$propertyName] = (string)$propertyValue;
                } else {
                    $data[$propertyName] = get_class($propertyValue);
                }
            } elseif (is_array($propertyValue)) {
                $data[$propertyName] = json_encode($propertyValue);
            } else {
                $data[$propertyName] = $propertyValue;
            }
        }

        // Add metadata
        if ($appendMetadata) {
            $data['_extbase_class'] = $object::class;
            $data['_pid'] = $object->getPid();
        }

        return $data;
    }
}
