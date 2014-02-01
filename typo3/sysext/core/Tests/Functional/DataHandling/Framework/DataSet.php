<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Oliver Hader <oliver.hader@typo3.org>
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
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DataHandler DataSet
 */
class DataSet {

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @param string $fileName
	 * @return DataSet
	 */
	public static function read($fileName) {
		$data = self::parseData(self::readData($fileName));

		return GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Tests\\Functional\\DataHandling\\Framework\\DataSet',
			$data
		);
	}

	/**
	 * @param string $fileName
	 * @return array
	 * @throws \RuntimeException
	 */
	protected static function readData($fileName) {
		if (!file_exists($fileName)) {
			throw new \RuntimeException('File "' . $fileName . '" does not exist');
		}

		$rawData = array();
		$fileHandle = fopen($fileName, 'r');
		while (($values = fgetcsv($fileHandle, 0)) !== FALSE) {
			$rawData[] = $values;
		}
		fclose($fileHandle);
		return $rawData;
	}

	/**
	 * Parses CSV data.
	 *
	 * Special values are:
	 * + "\NULL" to treat as NULL value
	 * + "\*" to ignore value during comparison
	 *
	 * @param array $rawData
	 * @return array
	 */
	protected static function parseData(array $rawData) {
		$data = array();
		$tableName = NULL;
		$fieldCount = NULL;
		$idIndex = NULL;
		foreach ($rawData as $values) {
			if (!empty($values[0])) {
				// Skip comment lines, starting with "#"
				if ($values[0]{0} === '#') {
					continue;
				}
				$tableName = $values[0];
				$fieldCount = NULL;
				$idIndex = NULL;
				if (!isset($data[$tableName])) {
					$data[$tableName] = array();
				}
			} elseif (implode('', $values) === '') {
				$tableName = NULL;
				$fieldCount = NULL;
				$idIndex = NULL;
			} elseif ($tableName !== NULL && !empty($values[1])) {
				array_shift($values);
				if (!isset($data[$tableName]['fields'])) {
					$data[$tableName]['fields'] = array();
					foreach ($values as $value) {
						if (empty($value)) {
							continue;
						}
						$data[$tableName]['fields'][] = $value;
						$fieldCount = count($data[$tableName]['fields']);
					}
					if (in_array('uid', $values)) {
						$idIndex = array_search('uid', $values);
						$data[$tableName]['idIndex'] = $idIndex;
					}
				} else {
					if (!isset($data[$tableName]['elements'])) {
						$data[$tableName]['elements'] = array();
					}
					$values = array_slice($values, 0, $fieldCount);
					foreach ($values as &$value) {
						if ($value === '\\NULL') {
							$value = NULL;
						}
					}
					unset($value);
					$element = array_combine($data[$tableName]['fields'], $values);
					if ($idIndex !== NULL) {
						$data[$tableName]['elements'][$values[$idIndex]] = $element;
					} else {
						$data[$tableName]['elements'][] = $element;
					}
				}
			}
		}
		return $data;
	}

	/**
	 * @param array $data
	 */
	public function __construct(array $data) {
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function getTableNames() {
		return array_keys($this->data);
	}

	/**
	 * @param string $tableName
	 * @return NULL|array
	 */
	public function getFields($tableName) {
		$fields = NULL;
		if (isset($this->data[$tableName]['fields'])) {
			$fields = $this->data[$tableName]['fields'];
		}
		return $fields;
	}

	/**
	 * @param string $tableName
	 * @return NULL|integer
	 */
	public function getIdIndex($tableName) {
		$idIndex = NULL;
		if (isset($this->data[$tableName]['idIndex'])) {
			$idIndex = $this->data[$tableName]['idIndex'];
		}
		return $idIndex;
	}

	/**
	 * @param string $tableName
	 * @return NULL|array
	 */
	public function getElements($tableName) {
		$elements = NULL;
		if (isset($this->data[$tableName]['elements'])) {
			$elements = $this->data[$tableName]['elements'];
		}
		return $elements;
	}

	/**
	 * @param string $fileName
	 */
	public function persist($fileName) {
		$fileHandle = fopen($fileName, 'w');

		foreach ($this->data as $tableName => $tableData) {
			if (empty($tableData['fields']) || empty($tableData['elements'])) {
				continue;
			}

			$fields = $tableData['fields'];
			array_unshift($fields, '');

			fputcsv($fileHandle, array($tableName));
			fputcsv($fileHandle, $fields);

			foreach ($tableData['elements'] as $element) {
				array_unshift($element, '');
				fputcsv($fileHandle, $element);
			}
		}

		fclose($fileHandle);
	}

}
