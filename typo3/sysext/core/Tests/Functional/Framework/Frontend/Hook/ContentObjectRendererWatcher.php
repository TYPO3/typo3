<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Hook;

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

use TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\RenderLevel;
use TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\RenderElement;

/**
 * Watcher for the content object rendering process
 */
class ContentObjectRendererWatcher implements \TYPO3\CMS\Frontend\ContentObject\ContentObjectPostInitHookInterface, \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var RenderLevel
	 */
	protected $renderLevel;

	/**
	 * @var RenderElement
	 */
	protected $nextParentRenderElement;

	/**
	 * @var array
	 */
	protected $nextParentConfiguration;

	/**
	 * Holds parent objects (cObj) locally
	 * to avoid spl_object_hash() reassignments.
	 *
	 * @var array
	 */
	protected $localParentObjects = array();

	/**
	 * @param string $name
	 * @param NULL|array $configuration
	 * @param string $typoScriptKey
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject
	 * @return string
	 */
	public function cObjGetSingleExt($name, $configuration, $typoScriptKey, \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject) {
		$this->localParentObjects[] = $parentObject;
		$this->nextParentRenderElement = NULL;
		$this->nextParentConfiguration = NULL;

		if (($foundRenderElement = $this->renderLevel->findRenderElement($parentObject)) !== NULL) {
			$this->nextParentRenderElement = $foundRenderElement;
			$this->nextParentConfiguration = $configuration;
			if (!empty($configuration['table'])) {
				$this->nextParentRenderElement->addExpectedTableName($configuration['table']);
			}
		}

		if (!empty($configuration['if.']) && !$parentObject->checkIf($configuration['if.'])) {
			return '';
		}

		$contentObject = $parentObject->getContentObject($name);
		if ($contentObject) {
			$contentObject->render($configuration);
		}

		return '';
	}

	/**
	 * Hook for post processing the initialization of ContentObjectRenderer
	 *
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject Parent content object
	 */
	public function postProcessContentObjectInitialization(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer &$parentObject) {
		$this->localParentObjects[] = $parentObject;

		if (!isset($this->renderLevel)) {
			$this->renderLevel = RenderLevel::create($parentObject);
			$this->renderLevel->add($parentObject);
		} elseif (($foundRenderLevel = $this->renderLevel->findRenderLevel($parentObject)) !== NULL) {
			$foundRenderLevel->add($parentObject);
		} elseif ($this->nextParentRenderElement !== NULL) {
			$level = $this->nextParentRenderElement->add($parentObject);
			$level->add($parentObject);
			if (!empty($this->nextParentConfiguration['watcher.']['parentRecordField'])) {
				$level->setParentRecordField($this->nextParentConfiguration['watcher.']['parentRecordField']);
			}
			$this->nextParentRenderElement = NULL;
			$this->nextParentConfiguration = NULL;
		}
	}

	/**
	 * @param string $query
	 * @param string $fromTable
	 */
	public function addQuery($query, $fromTable) {
		if ($this->nextParentRenderElement === NULL) {
			return;
		}

		$this->nextParentRenderElement->addQuery($query, $fromTable);
	}

	/**
	 * @param array $parameters
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
	 */
	public function show(array $parameters, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController) {
		if (!isset($this->renderLevel) || empty($parameters['enableOutput']) || !empty($frontendController->content)) {
			return;
		}

		$tableFields = NULL;
		if (!empty($this->getFrontendController()->tmpl->setup['watcher.']['tableFields.'])) {
			$tableFields = $this->getFrontendController()->tmpl->setup['watcher.']['tableFields.'];
			foreach ($tableFields as &$fieldList) {
				$fieldList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $fieldList, TRUE);
			}
			unset($fieldList);
		}

		$structureData = $this->renderLevel->structureData($tableFields);

		$result = array(
			'structure' => $structureData,
			'structurePaths' => $this->getStructurePaths($structureData),
			'records' => $this->renderLevel->mergeData($tableFields),
			'queries' => $this->renderLevel->mergeQueries(),
		);

		$frontendController->content = json_encode($result);
	}

	/**
	 * @param array $structureData
	 * @param array $currentStructurePaths
	 * @return array
	 */
	protected function getStructurePaths(array $structureData, array $currentStructurePaths = array()) {
		$structurePaths = array();

		foreach ($structureData as $recordIdentifier => $recordData) {
			$structurePaths[$recordIdentifier][] = $currentStructurePaths;
			foreach ($recordData as $fieldName => $fieldValue) {
				if (!is_array($fieldValue)) {
					continue;
				}

				$nestedStructurePaths = $this->getStructurePaths(
					$fieldValue,
					array_merge($currentStructurePaths, array($recordIdentifier, $fieldName))
				);

				foreach ($nestedStructurePaths as $nestedRecordIdentifier => $nestedStructurePathDetails) {
					$structurePaths[$nestedRecordIdentifier] = array_merge(
							(array)$structurePaths[$nestedRecordIdentifier],
						$nestedStructurePathDetails
					);
				}
			}
		}

		return $structurePaths;
	}

	/**
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected function getFrontendController() {
		return $GLOBALS['TSFE'];
	}

}
