<?php
namespace TYPO3\CMS\Workspaces\Service;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Workspaces\ColumnDataProviderInterface;
use TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord;

/**
 * Service for additional columns in GridPanel
 */
class AdditionalColumnService implements SingletonInterface
{
    /**
     * @var array|ColumnDataProviderInterface[]
     */
    protected $columns = [];

    /**
     * @return AdditionalColumnService
     */
    public static function getInstance()
    {
        return self::getObjectManager()->get(AdditionalColumnService::class);
    }

    /**
     * @return ObjectManager
     */
    public static function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * Registers data provider for a particular column name.
     *
     * @param string $columnName
     * @param string|object $dataProviderClassOrObject
     * @throws \RuntimeException
     */
    public function register($columnName, $dataProviderClassOrObject)
    {
        if (is_object($dataProviderClassOrObject)) {
            $dataProvider = $dataProviderClassOrObject;
        } else {
            $dataProvider = GeneralUtility::makeInstance($dataProviderClassOrObject);
        }

        if (!$dataProvider instanceof ColumnDataProviderInterface) {
            throw new \RuntimeException('Data provider needs to implement ColumnDataProviderInterface', 1374309323);
        }

        $this->columns[$columnName] = $dataProvider;
    }

    /**
     * Gets definition for JavaScript settings.
     *
     * @return array Column settings
     */
    public function getDefinition()
    {
        $columnSettings = [];
        foreach ($this->columns as $columnName => $dataProvider) {
            $definition = $dataProvider->getDefinition();

            if (!is_array($definition)) {
                $definition = [];
            }

            $definition['name'] = $columnName;
            $columnSettings[] = $definition;
        }
        return $columnSettings;
    }

    /**
     * Gets JavaScript handler object, e.g.
     * TYPO3.Workspaces.Configuration.AdditionalColumn.extension.MyCustomField
     *
     * @return array Column settings
     */
    public function getHandler()
    {
        $columnSettings = [];
        foreach ($this->columns as $columnName => $_) {
            $columnSettings[] = 'TYPO3.Workspaces.extension.AdditionalColumn.' . $columnName;
        }
        return $columnSettings;
    }

    /**
     * Gets data for grid data.
     *
     * @param CombinedRecord $combinedRecord
     * @return array Record data
     */
    public function getData(CombinedRecord $combinedRecord)
    {
        $recordData = [];
        foreach ($this->columns as $columnName => $dataProvider) {
            $data = $dataProvider->getData($combinedRecord);

            if ($data !== null) {
                $recordData[$columnName] = $data;
            }
        }
        return $recordData;
    }
}
