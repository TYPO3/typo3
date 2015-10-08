<?php
namespace TYPO3\CMS\Backend\View\BackendLayout;

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
 * Context that is forwarded to backend layout data providers.
 */
class DataProviderContext implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var int
     */
    protected $pageId;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $pageTsConfig;

    /**
     * @return int
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * @param int $pageId
     * @return DataProviderContext
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     * @return DataProviderContext
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     * @return DataProviderContext
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return DataProviderContext
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getPageTsConfig()
    {
        return $this->pageTsConfig;
    }

    /**
     * @param array $pageTsConfig
     * @return DataProviderContext
     */
    public function setPageTsConfig(array $pageTsConfig)
    {
        $this->pageTsConfig = $pageTsConfig;
        return $this;
    }
}
