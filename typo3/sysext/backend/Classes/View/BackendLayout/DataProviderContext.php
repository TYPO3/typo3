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

namespace TYPO3\CMS\Backend\View\BackendLayout;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Context that is forwarded to backend layout data providers.
 */
class DataProviderContext implements SingletonInterface
{
    protected int $pageId = 0;
    protected string $tableName = '';
    protected string $fieldName = '';
    protected array $data = [];
    protected array $pageTsConfig = [];

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function setPageId(int $pageId): self
    {
        $this->pageId = $pageId;
        return $this;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function setFieldName(string $fieldName): self
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getPageTsConfig(): array
    {
        return $this->pageTsConfig;
    }

    public function setPageTsConfig(array $pageTsConfig): self
    {
        $this->pageTsConfig = $pageTsConfig;
        return $this;
    }
}
