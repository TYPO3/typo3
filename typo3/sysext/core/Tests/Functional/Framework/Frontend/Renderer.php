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

/**
 * Model of frontend response
 */
class Renderer implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $tableFields;

	/**
	 * @var array
	 */
	protected $structure = array();

	/**
	 * @var array
	 */
	protected $structurePaths = array();

	/**
	 * @var array
	 */
	protected $records = array();

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;

	public function addRecordData($content, array $configuration = NULL) {
		$recordIdentifier = $this->cObj->currentRecord;
		list($tableName) = explode(':', $recordIdentifier);
		$currentWatcherValue = $this->getCurrentWatcherValue();
		$position = strpos($currentWatcherValue, '/' . $recordIdentifier);

		$recordData = $this->filterFields($tableName, $this->cObj->data);
		$this->records[$recordIdentifier] = $recordData;

		if ($currentWatcherValue === $recordIdentifier) {
			$this->structure[$recordIdentifier] = $recordData;
			$this->structurePaths[$recordIdentifier] = array(array());
		} elseif(!empty($position)) {
			$levelIdentifier = substr($currentWatcherValue, 0, $position);
			$this->addToStructure($levelIdentifier, $recordIdentifier, $recordData);
		}
	}

	public function addFileData($content, array $configuration = NULL) {
		$currentFile = $this->cObj->getCurrentFile();

		if ($currentFile instanceof \TYPO3\CMS\Core\Resource\File) {
			$tableName = 'sys_file';
		} elseif ($currentFile instanceof \TYPO3\CMS\Core\Resource\FileReference) {
			$tableName = 'sys_file_reference';
		} else {
			return;
		}

		$recordData = $this->filterFields($tableName, $currentFile->getProperties());
		$recordIdentifier = $tableName . ':' . $currentFile->getUid();
		$this->records[$recordIdentifier] = $recordData;

		$currentWatcherValue = $this->getCurrentWatcherValue();
		$levelIdentifier = rtrim($currentWatcherValue, '/');
		$this->addToStructure($levelIdentifier, $recordIdentifier, $recordData);
	}

	/**
	 * @param string $tableName
	 * @param array $recordData
	 * @return array
	 */
	protected function filterFields($tableName, array $recordData) {
		$recordData = array_intersect_key(
			$recordData,
			array_flip($this->getTableFields($tableName))
		);
		return $recordData;
	}

	protected function addToStructure($levelIdentifier, $recordIdentifier, array $recordData) {
		$steps = explode('/', $levelIdentifier);
		$structurePaths = array();
		$structure = &$this->structure;

		foreach ($steps as $step) {
			list($identifier, $fieldName) = explode('.', $step);
			$structurePaths[] = $identifier;
			$structurePaths[] = $fieldName;
			if (!isset($structure[$identifier])) {
				return;
			}
			$structure = &$structure[$identifier];
			if (!isset($structure[$fieldName]) || !is_array($structure[$fieldName])) {
				$structure[$fieldName] = array();
			}
			$structure = &$structure[$fieldName];
		}

		$structure[$recordIdentifier] = $recordData;
		$this->structurePaths[$recordIdentifier][] = $structurePaths;
	}

	/**
	 * @param array $parameters
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
	 */
	public function render($content, array $configuration = NULL) {
		$result = array(
			'structure' => $this->structure,
			'structurePaths' => $this->structurePaths,
			'records' => $this->records,
		);
		$content = json_encode($result);
		return $content;
	}

	/**
	 * @param string $tableName
	 * @return array
	 */
	protected function getTableFields($tableName) {
		if (!isset($this->tableFields) && !empty($this->getFrontendController()->tmpl->setup['config.']['watcher.']['tableFields.'])) {
			$this->tableFields = $this->getFrontendController()->tmpl->setup['config.']['watcher.']['tableFields.'];
			foreach ($this->tableFields as &$fieldList) {
				$fieldList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $fieldList, TRUE);
			}
			unset($fieldList);
		}

		return (!empty($this->tableFields[$tableName]) ? $this->tableFields[$tableName] : array());
	}

	/**
	 * @return string
	 */
	protected function getCurrentWatcherValue() {
		$watcherValue = NULL;
		if (isset($this->getFrontendController()->register['watcher'])) {
			$watcherValue = $this->getFrontendController()->register['watcher'];
		}
		return $watcherValue;
	}

	/**
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}

}
