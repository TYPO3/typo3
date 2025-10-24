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

namespace TYPO3\CMS\Reports\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Pagination\QueryBuilderPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Schema\Exception\InvalidSchemaTypeException;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for collecting content element statistics
 *
 * @internal This is not part of the public API and may change at any time
 */
final readonly class ContentStatisticsService
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
        private ConnectionPool $connectionPool,
    ) {}

    public function collectStatistic(): array
    {
        $defaultFields = ['colPos', 'CType', 'starttime', 'endtime', 'editlock', 'sys_language_uid', 'l18n_parent', 'fe_group', 'rowDescription', 'hidden'];
        $countInformation = $this->getCountInformation();

        $schema = $this->tcaSchemaFactory->get('tt_content');
        try {
            $typeField = $schema->getSubSchemaTypeInformation()->getFieldName();
        } catch (InvalidSchemaTypeException) {
            return ['error' => true];
        }
        $fieldConfig = $schema->hasField($typeField) ? $schema->getField($typeField)->getConfiguration() : [];
        $itemGroups = $fieldConfig['itemGroups'] ?? [];
        $groupedWizardItems = [];
        foreach (array_keys($itemGroups) as $groupIdentifier) {
            $groupedWizardItems['exists'][$groupIdentifier]['header'] = $itemGroups[$groupIdentifier];
            $groupedWizardItems['unused'][$groupIdentifier]['header'] = $itemGroups[$groupIdentifier];
        }

        foreach ($fieldConfig['items'] ?? [] as $item) {
            $selectItem = SelectItem::fromTcaItemArray($item);
            if ($selectItem->isDivider()) {
                continue;
            }
            $recordType = $selectItem->getValue();
            $groupIdentifier = $selectItem->getGroup();
            if (!isset($countInformation[$recordType])
                || ($countInformation[$recordType]['visible'] === 0 && $countInformation[$recordType]['hidden'] === 0)
            ) {
                $usageIdentifier = 'unused';
            } else {
                $usageIdentifier = 'exists';
            }

            $groupedWizardItems[$usageIdentifier][$groupIdentifier]['elements'] ??= [];

            // In case this group is not defined in itemGroups, use the group identifier as label.
            $groupedWizardItems[$usageIdentifier][$groupIdentifier]['header'] ??= $groupIdentifier;
            $itemDescription = $selectItem->getDescription();
            $wizardEntry = [
                'iconIdentifier' => $selectItem->getIcon(),
                'iconOverlay' => $selectItem->getIconOverlay(),
                'title' => $selectItem->getLabel(),
                'description' => $itemDescription['description'] ?? ($itemDescription ?? ''),
            ];
            $groupedWizardItems[$usageIdentifier][$groupIdentifier]['elements'][$recordType] = $wizardEntry;
            $groupedWizardItems[$usageIdentifier][$groupIdentifier]['elements'][$recordType]['count'] = $countInformation[$recordType] ?? [];
            try {
                $subschema = $schema->getSubSchema($recordType);
                $groupedWizardItems[$usageIdentifier][$groupIdentifier]['elements'][$recordType]['fields'] =
                    $subschema->getFields(fn($field) => !in_array($field->getName(), $defaultFields, true));
            } catch (UndefinedSchemaException) {
            }
        }
        return $groupedWizardItems;
    }

    public function collectStatisticForCtype(string $cType, int $currentPage): array
    {
        $paginator = new QueryBuilderPaginator($this->buildQueryBuilderForCtype($cType), $currentPage, 10);
        $pagination = new SimplePagination($paginator);

        $rows = $paginator->getPaginatedItems();
        foreach ($rows as &$row) {
            $row['path'] = BackendUtility::getRecordPath($row['pid'], '', 0);
            $row['tstamp_formatted'] = BackendUtility::datetime($row['tstamp']);
        }

        return [
            'ctype' => $cType,
            'label' => BackendUtility::getLabelFromItemlist('tt_content', 'CType', $cType),
            'count' => $this->getCountInformation($cType)[$cType] ?? [],
            'rows' => $rows,
            'paginator' => $paginator,
            'pagination' => $pagination,
        ];
    }

    public function isValidCtype(string $cType): bool
    {
        if ($cType === '') {
            return false;
        }
        return $this->tcaSchemaFactory->get('tt_content')->hasSubSchema($cType);
    }

    private function buildQueryBuilderForCtype(string $cType): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder
            ->select('uid', 'pid', 'header', 'tstamp', 'crdate', 'hidden', 'fe_group')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter($cType)),
            )
            ->orderBy('uid', 'desc');
    }

    private function getCountInformation(string $cType = ''): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder = $queryBuilder
            ->select('CType', 'deleted', 'hidden')
            ->addSelectLiteral('COUNT(*) as count')
            ->from('tt_content')
            ->groupBy('CType')
            ->addGroupBy('hidden')
            ->addGroupBy('deleted');

        if ($cType !== '') {
            $queryBuilder = $queryBuilder->where($queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter($cType)));
        }

        $counts = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
            $cType = $row['CType'];
            $deleted = (int)$row['deleted'];
            $hidden = (int)$row['hidden'];
            $count = (int)$row['count'];
            if (!isset($counts[$cType])) {
                $counts[$cType] = ['deleted' => 0, 'hidden' => 0, 'visible' => 0];
            }

            $key = match (true) {
                $deleted === 1 => 'deleted',
                $hidden === 1 => 'hidden',
                default => 'visible',
            };

            $counts[$cType][$key] += $count;
        }

        return $counts;
    }
}
