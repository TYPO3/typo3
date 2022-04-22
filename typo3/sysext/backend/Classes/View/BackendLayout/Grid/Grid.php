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

namespace TYPO3\CMS\Backend\View\BackendLayout\Grid;

/**
 * Grid
 *
 * Main rows-and-columns structure representing the rows and columns of
 * a BackendLayout in object form. Contains getter methods to return rows
 * and sum of "colspan" values assigned to columns in rows.
 *
 * Contains a tree of grid-related objects:
 *
 * - Grid
 *   - GridRow
 *     - GridColumn
 *       - GridColumnItem (one per record)
 *
 * Accessed in Fluid templates.
 *
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
class Grid extends AbstractGridObject
{
    /**
     * @var GridRow[]
     */
    protected $rows = [];

    /**
     * @var bool
     */
    protected $allowNewContent = true;

    public function addRow(GridRow $row): void
    {
        $this->rows[] = $row;
    }

    /**
     * @return GridRow[]
     */
    public function getRows(): iterable
    {
        return $this->rows;
    }

    public function getColumns(): iterable
    {
        $columns = [];
        foreach ($this->rows as $gridRow) {
            $columns += $gridRow->getColumns();
        }
        return $columns;
    }

    public function getSpan(): int
    {
        if (!isset($this->rows[0]) || $this->context->getDrawingConfiguration()->getLanguageMode()) {
            return 1;
        }
        $span = 0;
        foreach ($this->rows[0]->getColumns() as $column) {
            $span += $column->getColSpan();
        }
        return $span ?: 1;
    }
}
