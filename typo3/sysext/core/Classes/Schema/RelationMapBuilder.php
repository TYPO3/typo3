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

namespace TYPO3\CMS\Core\Schema;

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Low-level API to parse TCA to find field types which should be processed, as they contain
 * a relation.
 *
 * This also parses ALL flexforms available, that's why it juggles through all FlexForm
 * fields and parses the FlexForms as well.
 *
 * Everything is stored in a simple "RelationMap" object with an internal array structure.
 *
 * @internal not part of TYPO3 API as it should not be exposed, although this is really cool and powerful.
 */
final readonly class RelationMapBuilder
{
    public function buildFromStructure(array $tca): RelationMap
    {
        $relationMap = new RelationMap();
        foreach ($tca as $table => $tableConfig) {
            // What fields can have a relational connection to other tables?
            foreach ($tableConfig['columns'] ?? [] as $fieldName => $fieldConfig) {
                $fieldConfig = $fieldConfig['config'] ?? null;
                if (!in_array($fieldConfig['type'] ?? '', ['select', 'group', 'inline', 'file', 'category', 'flex'], true)) {
                    continue;
                }

                if ($fieldConfig['type'] === 'flex') {
                    $this->addRelationsForFlexFieldToRelationMap($fieldConfig, $table, $fieldName, $relationMap);
                } else {
                    $relationMap->add($table, $fieldName, $fieldConfig);
                }
            }
        }
        return $relationMap;
    }

    /**
     * Adds relations for a flex field to the relation map.
     * Note: Inside a section, it is not possible to add a field with a relation (type 'inline', 'file', 'folder', 'group', 'category').
     * See TcaFlexProcess class for details.
     */
    protected function addRelationsForFlexFieldToRelationMap(array $tcaConfig, string $tableName, string $fieldName, RelationMap $relationMap): array
    {
        // @todo: FlexFormTools should not be used here, as it should only work with real records.
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $resolvedDataStructures = [];
        // It might happen that fields are defined without a data structure "ds" configuration,
        // for example when their handling is based on PSR-14 DataStructureIdentifier* events.
        foreach ($tcaConfig['ds'] ?? [] as $dataStructureKey => $dataStructure) {
            $dataStructureIdentifier = [
                'type' => 'tca',
                'tableName' => $tableName,
                'fieldName' => $fieldName,
                'dataStructureKey' => $dataStructureKey,
            ];
            $flexStructureAsArray = $flexFormTools->parseDataStructureByIdentifier(json_encode($dataStructureIdentifier));
            foreach ($flexStructureAsArray['sheets'] as $sheetIdentifier => $sheet) {
                foreach ($sheet['ROOT']['el'] as $flexFieldName => $flexFieldConfig) {
                    $fieldIdentifier = $sheetIdentifier . '/' . $flexFieldName;
                    $relationMap->add($tableName, $fieldName, $flexFieldConfig['config'] ?? [], $fieldIdentifier);
                }
            }
        }
        return $resolvedDataStructures;
    }
}
