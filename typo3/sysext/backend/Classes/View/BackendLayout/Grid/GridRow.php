<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\View\BackendLayout\Grid;

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

/**
 * Grid Row
 *
 * Object representation of a single row of a grid defined in a BackendLayout.
 * Is solely responsible for grouping GridColumns.
 *
 * Accessed in Fluid templates.
 */
class GridRow extends AbstractGridObject
{
    /**
     * @var GridColumn[]
     */
    protected $columns = [];

    public function addColumn(GridColumn $column): void
    {
        $this->columns[$column->getColumnNumber()] = $column;
    }

    /**
     * @return GridColumn[]
     */
    public function getColumns(): iterable
    {
        return $this->columns;
    }
}
