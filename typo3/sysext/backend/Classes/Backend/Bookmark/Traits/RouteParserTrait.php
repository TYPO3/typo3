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

namespace TYPO3\CMS\Backend\Backend\Bookmark\Traits;

use TYPO3\CMS\Backend\Routing\Router;

/**
 * Shared route parsing functionality for bookmark components.
 *
 * @internal
 */
trait RouteParserTrait
{
    abstract protected function getRouter(): Router;

    private function getModuleNameFromRouteIdentifier(string $routeIdentifier): string
    {
        if (in_array($routeIdentifier, ['record_edit', 'file_edit'], true)) {
            return $routeIdentifier;
        }
        return (string)($this->getRouter()->getRoute($routeIdentifier)?->getOption('module')?->getIdentifier() ?? '');
    }

    /**
     * @return array{table?: string, recordid?: string, action?: string}
     */
    private function parseRecordEditData(string $routeIdentifier, array $arguments): array
    {
        if ($routeIdentifier !== 'record_edit' || !is_array($arguments['edit'] ?? null)) {
            return [];
        }

        $table = key($arguments['edit']);
        $tableData = current($arguments['edit']);
        $recordId = is_array($tableData) ? key($tableData) : null;

        if (!is_string($table) || (!is_string($recordId) && !is_int($recordId))) {
            return [];
        }

        $recordId = (string)$recordId;
        if (str_ends_with($recordId, ',')) {
            $recordId = substr($recordId, 0, -1);
        }

        return [
            'table' => $table,
            'recordid' => $recordId,
            'action' => $arguments['edit'][$table][$recordId] ?? '',
        ];
    }
}
