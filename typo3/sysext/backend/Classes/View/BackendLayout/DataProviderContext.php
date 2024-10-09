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

/**
 * Context that is forwarded to backend layout data providers.
 */
final class DataProviderContext
{
    /**
     * @todo: Declare arguments non-optional in TYPO3 v14.
     */
    public function __construct(
        public int $pageId = 0,
        public string $tableName = '',
        public string $fieldName = '',
        public array $data = [],
        public array $pageTsConfig = [],
    ) {}

    /**
     * @deprecated: Remove all setters and getters and set readonly in TYPO3 v14.
     */
    public function getPageId(): int
    {
        trigger_error(
            'DataProviderContext->getPageId() is deprecated and will be removed in TYPO3 v14.0. Use $dataProviderContext->pageId instead.',
            E_USER_DEPRECATED
        );
        return $this->pageId;
    }

    /**
     * @deprecated: Remove all setters and getters and set readonly in TYPO3 v14.
     */
    public function setPageId(int $pageId): self
    {
        trigger_error(
            'DataProviderContext->setPageId() is deprecated and will be removed in TYPO3 v14.0. Create readonly instances using __construct().',
            E_USER_DEPRECATED
        );
        $this->pageId = $pageId;
        return $this;
    }

    /**
     * @deprecated: Remove all setters and getters and set readonly in TYPO3 v14.
     */
    public function getTableName(): string
    {
        trigger_error(
            'DataProviderContext->getTableName() is deprecated and will be removed in TYPO3 v14.0. Use $dataProviderContext->tableName instead.',
            E_USER_DEPRECATED
        );
        return $this->tableName;
    }

    /**
     * @deprecated: Remove all setters and getters and set readonly in TYPO3 v14.
     */
    public function setTableName(string $tableName): self
    {
        trigger_error(
            'DataProviderContext->setTableName() is deprecated and will be removed in TYPO3 v14.0. Create readonly instances using __construct().',
            E_USER_DEPRECATED
        );
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @deprecated: Remove all setters and getters and set readonly in TYPO3 v14.
     */
    public function getFieldName(): string
    {
        trigger_error(
            'DataProviderContext->getFieldName() is deprecated and will be removed in TYPO3 v14.0. Use $dataProviderContext->fieldName instead.',
            E_USER_DEPRECATED
        );
        return $this->fieldName;
    }

    /**
     * @deprecated: Remove all setters and getters and set readonly in TYPO3 v14.
     */
    public function setFieldName(string $fieldName): self
    {
        trigger_error(
            'DataProviderContext->setFieldName() is deprecated and will be removed in TYPO3 v14.0. Create readonly instances using __construct().',
            E_USER_DEPRECATED
        );
        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * @deprecated: Remove all setters and getters and set readonly in TYPO3 v14.
     */
    public function getData(): array
    {
        trigger_error(
            'DataProviderContext->getData() is deprecated and will be removed in TYPO3 v14.0. Use $dataProviderContext->data instead.',
            E_USER_DEPRECATED
        );
        return $this->data;
    }

    /**
     * @deprecated: Remove all setters and getters and set readonly in TYPO3 v14.
     */
    public function setData(array $data): self
    {
        trigger_error(
            'DataProviderContext->setData() is deprecated and will be removed in TYPO3 v14.0. Create readonly instances using __construct().',
            E_USER_DEPRECATED
        );
        $this->data = $data;
        return $this;
    }

    /**
     * @deprecated: Remove all setters and getters and set readonly in TYPO3 v14.
     */
    public function getPageTsConfig(): array
    {
        trigger_error(
            'DataProviderContext->getPageTsConfig() is deprecated and will be removed in TYPO3 v14.0. Use $dataProviderContext->pageTsConfig instead.',
            E_USER_DEPRECATED
        );
        return $this->pageTsConfig;
    }

    /**
     * @deprecated: Remove all setters and getters and set readonly in TYPO3 v14.
     */
    public function setPageTsConfig(array $pageTsConfig): self
    {
        trigger_error(
            'DataProviderContext->setPageTsConfig() is deprecated and will be removed in TYPO3 v14.0. Create readonly instances using __construct().',
            E_USER_DEPRECATED
        );
        $this->pageTsConfig = $pageTsConfig;
        return $this;
    }
}
