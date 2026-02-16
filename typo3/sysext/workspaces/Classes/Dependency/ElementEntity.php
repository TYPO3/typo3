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

namespace TYPO3\CMS\Workspaces\Dependency;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Event\IsReferenceConsideredForDependencyEvent;

/**
 * Object to hold information on a dependent database element in abstract.
 *
 * @internal
 */
class ElementEntity
{
    public const REFERENCES_ChildOf = 'childOf';
    public const REFERENCES_ParentOf = 'parentOf';

    protected bool $invalid = false;
    protected string $table;
    protected int $id;
    protected array $data;
    protected array $record;
    protected DependencyResolver $dependency;
    protected ?array $children;
    protected ?array $parents;
    protected ElementEntity|false|null $outerMostParent;

    protected ?array $nestedChildren;

    public function __construct(string $table, int $id, array $data, DependencyResolver $dependency)
    {
        $this->table = $table;
        $this->id = $id;
        $this->data = $data;
        $this->dependency = $dependency;
        $this->resolveWorkspaceState();
    }

    public function setInvalid(bool $invalid): void
    {
        $this->invalid = $invalid;
    }

    public function isInvalid(): bool
    {
        return $this->invalid;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getDataValue(string $key): mixed
    {
        $result = null;
        if ($this->hasDataValue($key)) {
            $result = $this->data[$key];
        }
        return $result;
    }

    public function setDataValue(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function hasDataValue(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function __toString(): string
    {
        return $this->table . ':' . $this->id;
    }

    public function getDependency(): DependencyResolver
    {
        return $this->dependency;
    }

    /**
     * Gets all child references.
     *
     * @return ReferenceEntity[]
     */
    public function getChildren(): array
    {
        if (!isset($this->children)) {
            $this->children = [];

            if ($this->isInvalid()) {
                return $this->children;
            }

            $action = $this->dependency->getAction();
            $eventDispatcher = $this->dependency->getEventDispatcher();

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_refindex');

            $result = $queryBuilder
                ->select('*')
                ->from('sys_refindex')
                ->where(
                    $queryBuilder->expr()->eq(
                        'tablename',
                        $queryBuilder->createNamedParameter($this->table)
                    ),
                    $queryBuilder->expr()->eq(
                        'recuid',
                        $queryBuilder->createNamedParameter($this->id, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'workspace',
                        $queryBuilder->createNamedParameter($this->dependency->getWorkspace(), Connection::PARAM_INT)
                    )
                )
                ->orderBy('sorting')
                ->executeQuery();

            while ($row = $result->fetchAssociative()) {
                if ($row['ref_table'] === '_STRING') {
                    continue;
                }

                $isDependency = false;
                if ($eventDispatcher !== null && $action !== null) {
                    $event = $eventDispatcher->dispatch(
                        new IsReferenceConsideredForDependencyEvent(
                            $row['tablename'],
                            (int)$row['recuid'],
                            $row['field'],
                            $row['ref_table'],
                            (int)$row['ref_uid'],
                            $action,
                            $this->dependency->getWorkspace(),
                        )
                    );
                    $isDependency = $event->isDependency();
                }

                if (!$isDependency) {
                    continue;
                }

                if ($action === DependencyCollectionAction::Discard) {
                    $record = BackendUtility::getRecord($row['ref_table'], (int)$row['ref_uid']);
                    if (VersionState::tryFrom($record['t3ver_state'] ?? 0) !== VersionState::DELETE_PLACEHOLDER) {
                        continue;
                    }
                }

                $this->children[] = $this->getDependency()->getFactory()->getReferencedElement(
                    $row['ref_table'],
                    (int)$row['ref_uid'],
                    $row['field'],
                    [],
                    $this->getDependency()
                );
            }
        }
        return $this->children;
    }

    /**
     * Gets all parent references.
     *
     * @return ReferenceEntity[]
     */
    public function getParents(): array
    {
        if (!isset($this->parents)) {
            $this->parents = [];

            $action = $this->dependency->getAction();
            $eventDispatcher = $this->dependency->getEventDispatcher();

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_refindex');

            $result = $queryBuilder
                ->select('*')
                ->from('sys_refindex')
                ->where(
                    $queryBuilder->expr()->eq(
                        'ref_table',
                        $queryBuilder->createNamedParameter($this->table)
                    ),
                    $queryBuilder->expr()->eq(
                        'ref_uid',
                        $queryBuilder->createNamedParameter($this->id, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'workspace',
                        $queryBuilder->createNamedParameter($this->dependency->getWorkspace(), Connection::PARAM_INT)
                    )
                )
                ->orderBy('sorting')
                ->executeQuery();

            while ($row = $result->fetchAssociative()) {
                $isDependency = false;
                if ($eventDispatcher !== null && $action !== null) {
                    $event = $eventDispatcher->dispatch(
                        new IsReferenceConsideredForDependencyEvent(
                            $row['tablename'],
                            (int)$row['recuid'],
                            $row['field'],
                            $row['ref_table'],
                            (int)$row['ref_uid'],
                            $action,
                            $this->dependency->getWorkspace(),
                        )
                    );
                    $isDependency = $event->isDependency();
                }

                if (!$isDependency) {
                    continue;
                }

                if ($action === DependencyCollectionAction::Discard) {
                    $record = BackendUtility::getRecord($row['tablename'], (int)$row['recuid']);
                    if (VersionState::tryFrom($record['t3ver_state'] ?? 0) !== VersionState::DELETE_PLACEHOLDER) {
                        continue;
                    }
                }

                $this->parents[] = $this->getDependency()->getFactory()->getReferencedElement(
                    $row['tablename'],
                    (int)$row['recuid'],
                    $row['field'],
                    [],
                    $this->getDependency()
                );
            }
        }
        return $this->parents;
    }

    /**
     * Determines whether there are child or parent references.
     */
    public function hasReferences(): bool
    {
        return !empty($this->getChildren()) || !empty($this->getParents());
    }

    /**
     * Gets the outermost parent element.
     */
    public function getOuterMostParent(): false|ElementEntity
    {
        if (!isset($this->outerMostParent)) {
            $parents = $this->getParents();
            if (empty($parents)) {
                $this->outerMostParent = $this;
            } else {
                $this->outerMostParent = false;
                /** @var ReferenceEntity $parent */
                foreach ($parents as $parent) {
                    $outerMostParent = $parent->getElement()->getOuterMostParent();
                    if ($outerMostParent instanceof ElementEntity) {
                        $this->outerMostParent = $outerMostParent;
                        break;
                    }
                    if ($outerMostParent === false) {
                        break;
                    }
                }
            }
        }
        return $this->outerMostParent;
    }

    /**
     * Gets nested children accumulated.
     *
     * @return ReferenceEntity[]
     */
    public function getNestedChildren(): array
    {
        if (!isset($this->nestedChildren)) {
            $this->nestedChildren = [];
            $children = $this->getChildren();
            /** @var ReferenceEntity $child */
            foreach ($children as $child) {
                $this->nestedChildren = array_merge($this->nestedChildren, [$child->getElement()->__toString() => $child->getElement()], $child->getElement()->getNestedChildren());
            }
        }
        return $this->nestedChildren;
    }

    /**
     * Gets the database record of this element.
     */
    public function getRecord(): array
    {
        if (empty($this->record['uid']) || (int)$this->record['uid'] !== $this->getId()) {
            $this->record = [];

            $fieldNames = ['uid', 'pid', 't3ver_wsid', 't3ver_state', 't3ver_oid'];
            $schemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
            $schema = $schemaFactory->get($this->getTable());
            if ($schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                $fieldNames[] = $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($this->getTable());
            $queryBuilder->getRestrictions()->removeAll();

            $row = $queryBuilder
                ->select(...$fieldNames)
                ->from($this->getTable())
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($this->getId(), Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAssociative();

            if (is_array($row)) {
                $this->record = $row;
            }
        }

        return $this->record;
    }

    /**
     * Resolves workspace state for this element: validates workspace awareness,
     * resolves liveId, and marks invalid records.
     *
     * Only executed for Publish and Display actions (matching original behavior
     * where Stage/Discard did not register a construct callback).
     */
    private function resolveWorkspaceState(): void
    {
        $action = $this->dependency->getAction();
        if ($action !== DependencyCollectionAction::Publish && $action !== DependencyCollectionAction::Display) {
            return;
        }

        $schemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        $schema = $schemaFactory->get($this->table);
        if (!$schema->isWorkspaceAware()) {
            $this->invalid = true;
            return;
        }

        $versionRecord = $this->getRecord();
        if (empty($versionRecord)) {
            throw new \RuntimeException(
                'Element "' . $this->table . ':' . $this->id . '" does not exist',
                1393960943
            );
        }

        $workspace = $this->dependency->getWorkspace();

        $deleteFieldName = $schema->hasCapability(TcaSchemaCapability::SoftDelete)
            ? $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName()
            : null;

        if (
            (int)$versionRecord['t3ver_oid'] > 0 && (
                (int)$versionRecord['t3ver_wsid'] === 0
                || !empty($deleteFieldName) && (int)$versionRecord['t3ver_wsid'] === $workspace
                    && (int)$versionRecord[$deleteFieldName] > 0
            )
        ) {
            $this->setDataValue('liveId', $this->id);
            $this->invalid = true;
            return;
        }

        if ($this->hasDataValue('liveId') === false) {
            if (!empty($versionRecord['t3ver_oid']) && (int)$versionRecord['t3ver_wsid'] === $workspace) {
                $this->setDataValue('liveId', $versionRecord['t3ver_oid']);
            } elseif ((int)$versionRecord['t3ver_wsid'] === 0 || (int)$versionRecord['t3ver_oid'] === 0) {
                $this->setDataValue('liveId', $this->id);
                $versionRecord = BackendUtility::getWorkspaceVersionOfRecord(
                    $workspace,
                    $this->table,
                    $this->id,
                    ['uid', 't3ver_state']
                );
                if (!empty($versionRecord['uid'])) {
                    $this->setId($versionRecord['uid']);
                } else {
                    $this->invalid = true;
                }
            } else {
                $this->invalid = true;
            }
        }
    }
}
