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

namespace TYPO3\CMS\Backend\Controller\Event;

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;

/**
 * This event triggers after the LocalizationController (AJAX) has
 * selected page columns to be translated. Allows third parties to
 * add to or change the columns and content elements withing those
 * columns which will be available for localization through the
 * "translate" modal in the page module.
 */
final class AfterPageColumnsSelectedForLocalizationEvent
{
    /**
     * List of columns, indexed by column position number, value is label (either LLL: or hardcoded).
     *
     * @var array
     */
    private $columns;

    /**
     * Array of records which were used when building the original column
     * manifest and column position numbers list.
     *
     * @var array
     */
    private $records;

    /**
     * Request parameters passed to LocalizationController.
     *
     * @var array
     */
    private $parameters;

    /**
     * @var BackendLayout
     */
    private $backendLayout;

    /**
     * List of integer column position numbers used in the BackendLayout.
     *
     * @var array
     */
    private $columnList;

    public function __construct(array $columns, array $columnList, BackendLayout $backendLayout, array $records, array $parameters)
    {
        $this->columns = $columns;
        $this->columnList = $columnList;
        $this->backendLayout = $backendLayout;
        $this->records = $records;
        $this->parameters = $parameters;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    public function getColumnList(): array
    {
        return $this->columnList;
    }

    public function setColumnList(array $columnList): void
    {
        $this->columnList = $columnList;
    }

    public function getBackendLayout(): BackendLayout
    {
        return $this->backendLayout;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
