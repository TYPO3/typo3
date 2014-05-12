<?php
namespace TYPO3\CMS\Backend\View\BackendLayout;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Oliver Hader <oliver.hader@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Context that is forwarded to backend layout data providers.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class DataProviderContext implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var integer
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
	 * @return integer
	 */
	public function getPageId() {
		return $this->pageId;
	}

	/**
	 * @param integer $pageId
	 * @return DataProviderContext
	 */
	public function setPageId($pageId) {
		$this->pageId = $pageId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * @param string $tableName
	 * @return DataProviderContext
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFieldName() {
		return $this->fieldName;
	}

	/**
	 * @param string $fieldName
	 * @return DataProviderContext
	 */
	public function setFieldName($fieldName) {
		$this->fieldName = $fieldName;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param array $data
	 * @return DataProviderContext
	 */
	public function setData(array $data) {
		$this->data = $data;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getPageTsConfig() {
		return $this->pageTsConfig;
	}

	/**
	 * @param array $pageTsConfig
	 * @return DataProviderContext
	 */
	public function setPageTsConfig(array $pageTsConfig) {
		$this->pageTsConfig = $pageTsConfig;
		return $this;
	}

}