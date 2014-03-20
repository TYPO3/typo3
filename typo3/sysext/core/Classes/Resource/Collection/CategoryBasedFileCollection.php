<?php
namespace TYPO3\CMS\Core\Resource\Collection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Frans Saris <franssaris (at) gmail.com>
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
 * A collection containing a set files belonging to certain categories.
 * This collection is persisted to the database with the accordant category identifiers.
 */
class CategoryBasedFileCollection extends \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection {

	/**
	 * @var string
	 */
	static protected $storageTableName = 'sys_file_collection';

	/**
	 * @var string
	 */
	static protected $type = 'categories';

	/**
	 * @var string
	 */
	static protected $itemsCriteriaField = 'category';

	/**
	 * @var string
	 */
	protected $itemTableName = 'sys_category';

	/**
	 * Populates the content-entries of the collection
	 *
	 * @return void
	 */
	public function loadContents() {

		$resource = $this->getDatabaseConnection()->exec_SELECT_mm_query(
			'sys_file_metadata.file',
			'sys_category',
			'sys_category_record_mm',
			'sys_file_metadata',
			'AND sys_category.uid=' . (int)$this->getItemsCriteria() .
			' AND sys_category_record_mm.tablenames = \'sys_file_metadata\''
		);

		$resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
		if ($resource) {
			while (($record = $this->getDatabaseConnection()->sql_fetch_assoc($resource)) !== FALSE) {
				$this->add($resourceFactory->getFileObject((int)$record['file']));
			}
			$this->getDatabaseConnection()->sql_free_result($resource);
		}
	}

	/**
	 * Gets the database object.
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
