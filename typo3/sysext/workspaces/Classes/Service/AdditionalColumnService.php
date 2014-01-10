<?php
namespace TYPO3\CMS\Workspaces\Service;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Service for additional columns in GridPanel
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class AdditionalColumnService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array|\TYPO3\CMS\Workspaces\ColumnDataProviderInterface[]
	 */
	protected $columns = array();

	/**
	 * @return \TYPO3\CMS\Workspaces\Service\AdditionalColumnService
	 */
	static public function getInstance() {
		return self::getObjectManager()->get('TYPO3\\CMS\\Workspaces\\Service\\AdditionalColumnService');
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	static public function getObjectManager() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
	}

	/**
	 * Registers data provider for a particular column name.
	 *
	 * @param string $columnName
	 * @param string|object $dataProviderClassOrObject
	 * @return void
	 * @throws \RuntimeException
	 */
	public function register($columnName, $dataProviderClassOrObject) {
		if (is_object($dataProviderClassOrObject)) {
			$dataProvider = $dataProviderClassOrObject;
		} else {
			$dataProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($dataProviderClassOrObject);
		}

		if (!$dataProvider instanceof \TYPO3\CMS\Workspaces\ColumnDataProviderInterface) {
			throw new \RuntimeException('Data provider needs to implement ColumnDataProviderInterface', 1374309323);
		}

		$this->columns[$columnName] = $dataProvider;
	}

	/**
	 * Gets definition for JavaScript settings.
	 *
	 * @return array Column settings
	 */
	public function getDefinition() {
		$columnSettings = array();
		foreach ($this->columns as $columnName => $dataProvider) {
			$definition = $dataProvider->getDefinition();

			if (!is_array($definition)) {
				$definition = array();
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
	public function getHandler() {
		$columnSettings = array();
		foreach (array_keys($this->columns) as $columnName) {
			$columnSettings[] = 'TYPO3.Workspaces.extension.AdditionalColumn.' . $columnName;
		}
		return $columnSettings;
	}

	/**
	 * Gets data for grid data.
	 *
	 * @param \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord $combinedRecord
	 * @return array Record data
	 */
	public function getData(\TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord $combinedRecord) {
		$recordData = array();
		foreach ($this->columns as $columnName => $dataProvider) {
			$data = $dataProvider->getData($combinedRecord);

			if ($data !== NULL) {
				$recordData[$columnName] = $data;
			}
		}
		return $recordData;
	}
}
