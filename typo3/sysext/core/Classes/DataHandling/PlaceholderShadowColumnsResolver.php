<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\DataHandling;

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

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolver for placeholder shadow columns to be used in workspace aware environments.
 *
 * Certain fields need to be "shadowed" - NEW and MOVE placeholder need to have values kept in sync
 * that are modified, like the "hidden" field (enable fields, slug fields etc).
 *
 * This class resolves the columns for a TCA table record that should be kept in sync.
 *
 * @see \TYPO3\CMS\Core\DataHandling\DataHandler::placeholderShadowing()
 * @see \TYPO3\CMS\Workspaces\Hook\DataHandlerHook::moveRecord_wsPlaceholders()
 */
class PlaceholderShadowColumnsResolver
{
    protected const CONTROL_COLUMNS = [
        'languageField',
        'transOrigPointerField',
        'translationSource',
        'type',
        'label'
    ];

    protected const FLAG_NONE = 0;
    protected const FLAG_APPLY_SYSTEM_COLUMNS = 1;
    protected const FLAG_APPLY_SLUG_COLUMNS = 2;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var array
     */
    protected $tableConfiguration;

    /**
     * @var int|null
     */
    protected $flags;

    /**
     * @param string $tableName Name of the database table
     * @param array $tableConfiguration TCA configuration for $tableName
     * @throws Exception
     */
    public function __construct(string $tableName, array $tableConfiguration)
    {
        $this->tableName = $tableName;
        $this->tableConfiguration = $tableConfiguration;

        if (empty($this->tableName) || empty($this->tableConfiguration)) {
            throw new Exception('No table name and TCA given', 1574174231);
        }
    }

    /**
     * @param int|null $flags Custom flags to adjust resolving behavior
     * @return string[] Placeholder shadow column names
     */
    public function forNewPlaceholder(int $flags = null): array
    {
        $flags = $flags ?? self::FLAG_APPLY_SYSTEM_COLUMNS | self::FLAG_APPLY_SLUG_COLUMNS;
        $shadowColumnsList = $this->tableConfiguration['ctrl']['shadowColumnsForNewPlaceholders'] ?? '';
        return $this->forTable($shadowColumnsList, $flags);
    }

    /**
     * @param int|null $flags Custom flags to adjust resolving behavior
     * @return string[] Placeholder shadow column names
     */
    public function forMovePlaceholder(int $flags = null): array
    {
        $shadowColumnsList = $this->tableConfiguration['ctrl']['shadowColumnsForMovePlaceholders']
            ?? $this->tableConfiguration['ctrl']['shadowColumnsForNewPlaceholders'] ?? '';
        // @todo Applying same flags as for new-placeholders would streamline database integrity
        return $this->forTable($shadowColumnsList, $flags);
    }

    protected function forTable(string $shadowColumnsList, int $flags = null): array
    {
        $shadowColumns = explode(',', $shadowColumnsList);
        $flags = $flags ?? self::FLAG_NONE;

        if ($flags & self::FLAG_APPLY_SYSTEM_COLUMNS) {
            foreach (self::CONTROL_COLUMNS as $controlColumn) {
                if (isset($this->tableConfiguration['ctrl'][$controlColumn])) {
                    $shadowColumns[] = $this->tableConfiguration['ctrl'][$controlColumn];
                }
            }
        }
        if ($flags & self::FLAG_APPLY_SLUG_COLUMNS) {
            $shadowColumns = array_merge(
                $shadowColumns,
                GeneralUtility::makeInstance(SlugEnricher::class)->resolveSlugFieldNames($this->tableName)
            );
        }
        foreach ($this->tableConfiguration['ctrl']['enablecolumns'] ?? [] as $enableColumn) {
            $shadowColumns[] = $enableColumn;
        }

        $shadowColumns = array_filter(
            array_map('trim', $shadowColumns),
            function (string $shadowColumn) {
                return !empty($shadowColumn) && $shadowColumn !== 'uid' && $shadowColumn !== 'pid'
                    && isset($this->tableConfiguration['columns'][$shadowColumn]);
            }
        );
        $shadowColumns = array_unique($shadowColumns);
        return $shadowColumns;
    }
}
