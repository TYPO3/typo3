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
 * Model of frontend response content
 */
class ResponseContent {

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var array
	 */
	protected $structure;

	/**
	 * @var array
	 */
	protected $structurePaths;

	/**
	 * @var array
	 */
	protected $records;

	/**
	 * @var array
	 */
	protected $queries;

	/**
	 * @param Response $response
	 */
	public function __construct(Response $response) {
		$this->response = $response;
		$content = json_decode($response->getContent(), TRUE);

		if ($content !== NULL && is_array($content)) {
			$this->structure = $content['structure'];
			$this->structurePaths = $content['structurePaths'];
			$this->records = $content['records'];
			$this->queries = $content['queries'];
		}
	}

	/**
	 * @return array
	 */
	public function getStructure() {
		return $this->structure;
	}

	/**
	 * @return array
	 */
	public function getStructurePaths() {
		return $this->structurePaths;
	}

	/**
	 * @return array
	 */
	public function getRecords() {
		return $this->records;
	}

	/**
	 * @return array
	 */
	public function getQueries() {
		return $this->queries;
	}

	/**
	 * @param string $recordIdentifier
	 * @param string $fieldName
	 * @return array
	 */
	public function findStructures($recordIdentifier, $fieldName = '') {
		$structures = array();

		if (empty($this->structurePaths[$recordIdentifier])) {
			return $structures;
		}

		foreach ($this->structurePaths[$recordIdentifier] as $steps) {
			$structure = $this->structure;
			$steps[] = $recordIdentifier;

			if (!empty($fieldName)) {
				$steps[] = $fieldName;
			}

			foreach ($steps as $step) {
				if (!isset($structure[$step])) {
					$structure = NULL;
					break;
				}
				$structure = $structure[$step];
			}

			if (!empty($structure)) {
				$structures[implode('/', $steps)] = $structure;
			}
		}

		return $structures;
	}

}
