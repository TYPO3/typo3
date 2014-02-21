<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend;

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

use \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Model of rendered content elements
 */
class RenderElement {

	/**
	 * @var array
	 */
	protected $recordData;

	/**
	 * @var string
	 */
	protected $recordIdentifier;

	/**
	 * @var string
	 */
	protected $recordTableName;

	/**
	 * @var array|RenderLevel[]
	 */
	protected $levels = array();

	/**
	 * @var array
	 */
	protected $expectedTableNames = array();

	/**
	 * @var array
	 */
	protected $queries = array();

	/**
	 * @param ContentObjectRenderer $contentObjectRenderer
	 * @return RenderElement
	 */
	public static function create(ContentObjectRenderer $contentObjectRenderer) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Tests\\Functional\\Framework\\Frontend\\RenderElement',
			$contentObjectRenderer
		);
	}

	/**
	 * @param ContentObjectRenderer $contentObjectRenderer
	 */
	public function __construct(ContentObjectRenderer $contentObjectRenderer) {
		$this->recordIdentifier = $contentObjectRenderer->currentRecord;
		list($this->recordTableName) = explode(':', $this->recordIdentifier);
		$this->recordData = $contentObjectRenderer->data;
	}

	/**
	 * @param ContentObjectRenderer $contentObjectRenderer
	 * @return RenderLevel
	 */
	public function add(ContentObjectRenderer $contentObjectRenderer) {
		$level = RenderLevel::create($contentObjectRenderer);
		$level->setParentRecordIdentifier($this->recordIdentifier);
		$this->levels[] = $level;
		return $level;
	}

	/**
	 * @return string
	 */
	public function getRecordIdentifier() {
		return $this->recordIdentifier;
	}

	/**
	 * @param string $expectedTableName
	 */
	public function addExpectedTableName($expectedTableName) {
		if (!$this->hasExpectedTableName($expectedTableName)) {
			$this->expectedTableNames[] = $expectedTableName;
		}
	}

	/**
	 * @param string $tableName
	 * @return bool
	 */
	public function hasExpectedTableName($tableName) {
		if (in_array($tableName, $this->expectedTableNames)) {
			return TRUE;
		}
		// Handling JOIN constructions
		// e.g. "sys_category JOIN sys_category_record_mm ON sys_category_record_mm.uid_local = sys_category.uid"
		foreach ($this->getExpectedTableNames() as $expectedTableName) {
			if (strpos($tableName, $expectedTableName . ' ') === 0) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * @return array
	 */
	public function getExpectedTableNames() {
		return $this->expectedTableNames;
	}

	/**
	 * @param string $query
	 * @param string $fromTable
	 */
	public function addQuery($query, $fromTable) {
		if (empty($this->expectedTableNames) || $this->hasExpectedTableName($fromTable)) {
			$this->queries[] = $query;
		}
	}

	/**
	 * @param ContentObjectRenderer $contentObjectRenderer
	 * @return NULL|RenderLevel
	 */
	public function findRenderLevel(ContentObjectRenderer $contentObjectRenderer) {
		if (empty($this->levels)) {
			return NULL;
		}

		foreach ($this->levels as $level) {
			$result = $level->findRenderLevel($contentObjectRenderer);
			if ($result !== NULL) {
				return $result;
			}
		}

		return NULL;
	}

	/**
	 * @param NULL|array $tableFields
	 * @return array
	 */
	public function getRecordData(array $tableFields = NULL) {
		$recordData = $this->recordData;

		if (!empty($tableFields[$this->recordTableName])) {
			$recordData = array_intersect_key(
				$recordData,
				array_flip($tableFields[$this->recordTableName])
			);
		}

		return $recordData;
	}

	/**
	 * @param NULL|array $tableFields
	 * @return array
	 */
	public function structureData(array $tableFields = NULL) {
		$data = array(
			$this->recordIdentifier => $this->getRecordData($tableFields)
		);

		foreach ($this->levels as $level) {
			$parentRecordIdentifier = $level->getParentRecordIdentifier();
			$parentRecordField = $level->getParentRecordField();

			foreach ($level->getElements() as $element) {
				if (empty($parentRecordIdentifier) || empty($parentRecordField) || !isset($data[$parentRecordIdentifier])) {
					$data = array_merge($data, $element->structureData($tableFields));
					continue;
				}

				if (!isset($data[$parentRecordIdentifier][$parentRecordField]) || !is_array($data[$parentRecordIdentifier][$parentRecordField])) {
					$data[$parentRecordIdentifier][$parentRecordField] = array();
				}

				$data[$parentRecordIdentifier][$parentRecordField] = array_merge(
					$data[$parentRecordIdentifier][$parentRecordField],
					$element->structureData($tableFields)
				);
			}
		}

		return $data;
	}

	/**
	 * @param NULL|array $tableFields
	 * @return array
	 */
	public function mergeData(array $tableFields = NULL) {
		$data = array(
			$this->recordIdentifier => $this->getRecordData($tableFields),
		);
		foreach ($this->levels as $level) {
			$data = array_merge($data, $level->mergeData($tableFields));
		}
		return $data;
	}

	/**
	 * @return array
	 */
	public function mergeQueries() {
		$queries = $this->queries;
		foreach ($this->levels as $level) {
			$queries = array_merge($queries, $level->mergeQueries());
		}
		return $queries;
	}

}
