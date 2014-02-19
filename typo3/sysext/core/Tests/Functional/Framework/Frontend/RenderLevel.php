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
 * Model of rendered content levels
 */
class RenderLevel {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var string
	 */
	protected $parentRecordIdentifier;

	/**
	 * @var string
	 */
	protected $parentRecordField;

	/**
	 * @var array|RenderElement[]
	 */
	protected $elements = array();

	/**
	 * @param ContentObjectRenderer $contentObjectRenderer
	 * @return RenderLevel
	 */
	public static function create(ContentObjectRenderer $contentObjectRenderer) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Tests\\Functional\\Framework\\Frontend\\RenderLevel',
			$contentObjectRenderer
		);
	}

	/**
	 * @param ContentObjectRenderer $contentObjectRenderer
	 */
	public function __construct(ContentObjectRenderer $contentObjectRenderer) {
		$this->identifier = spl_object_hash($contentObjectRenderer);
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @param string $parentRecordIdentifier
	 */
	public function setParentRecordIdentifier($parentRecordIdentifier) {
		$this->parentRecordIdentifier = $parentRecordIdentifier;
	}

	public function getParentRecordIdentifier() {
		return $this->parentRecordIdentifier;
	}

	/**
	 * @param string $parentRecordField
	 */
	public function setParentRecordField($parentRecordField) {
		$this->parentRecordField = $parentRecordField;
	}

	/**
	 * @return string
	 */
	public function getParentRecordField() {
		return $this->parentRecordField;
	}

	/**
	 * @return array|RenderElement[]
	 */
	public function getElements() {
		return $this->elements;
	}

	/**
	 * @param ContentObjectRenderer $contentObjectRenderer
	 * @return RenderElement
	 */
	public function add(ContentObjectRenderer $contentObjectRenderer) {
		$element = RenderElement::create($contentObjectRenderer);
		$this->elements[] = $element;
		return $element;
	}

	/**
	 * @param ContentObjectRenderer $contentObjectRenderer
	 * @return NULL|RenderLevel
	 */
	public function findRenderLevel(ContentObjectRenderer $contentObjectRenderer) {
		if (spl_object_hash($contentObjectRenderer) === $this->identifier) {
			return $this;
		}

		foreach ($this->elements as $element) {
			$result = $element->findRenderLevel($contentObjectRenderer);
			if ($result !== NULL) {
				return $result;
			}
		}

		return NULL;
	}

	/**
	 * @param ContentObjectRenderer $contentObjectRenderer
	 * @return NULL|RenderElement
	 */
	public function findRenderElement(ContentObjectRenderer $contentObjectRenderer) {
		$foundRenderLevel = $this->findRenderLevel($contentObjectRenderer);

		if ($foundRenderLevel === NULL) {
			return NULL;
		}

		if ($foundRenderLevel !== $this) {
			return $foundRenderLevel->findRenderElement($contentObjectRenderer);
		}


		foreach ($this->elements as $element) {
			if ($element->getRecordIdentifier() === $contentObjectRenderer->currentRecord) {
				return $element;
			}
		}

		return NULL;
	}

	/**
	 * @param NULL|array $tableFields
	 * @return array
	 */
	public function structureData(array $tableFields = NULL) {
		$data = array();

		foreach ($this->elements as $element) {
			$data = array_merge($data, $element->structureData($tableFields));
		}

		return $data;
	}

	/**
	 * @param NULL|array $tableFields
	 * @return array
	 */
	public function mergeData(array $tableFields = NULL) {
		$data = array();

		foreach ($this->elements as $element) {
			$data = array_merge($data, $element->mergeData($tableFields));
		}

		return $data;
	}

	/**
	 * @return array
	 */
	public function mergeQueries() {
		$queries = array();

		foreach ($this->elements as $element) {
			$queries = array_merge($queries, $element->mergeQueries());
		}

		return $queries;
	}

}
