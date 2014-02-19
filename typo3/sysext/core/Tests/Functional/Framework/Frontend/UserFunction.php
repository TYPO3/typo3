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
class UserFunction implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;

	/**
	 * @param string $content
	 * @param array $configuration
	 * @return string
	 */
	public function getManyToManyIds($content, array $configuration = NULL) {
		$where = array();
		$uidLocal = NULL;
		$uidForeign = NULL;
		$manyToManyTableName = NULL;

		$uidLocal = (int)$this->getValue('uidLocal', $configuration);
		$uidForeign = (int)$this->getValue('uidForeign', $configuration);
		$manyToManyTableName = $this->getValue('manyToManyTableName', $configuration);

		if (!($uidLocal xor $uidForeign) || empty($manyToManyTableName)) {
			return $content;
		}

		if (!empty($uidLocal)) {
			$selectField = 'uid_foreign';
			$sortingField = 'sorting';
			$where[] = 'uid_local=' . $uidLocal;
		} else {
			$selectField = 'uid_local';
			$sortingField = 'sorting_foreign';
			$where[] = 'uid_foreign=' . $uidForeign;
		}

		if (!empty($configuration['matchTableName'])) {
			$where[] = 'tablenames=' . $this->getDatabaseConnection()->fullQuoteStr($configuration['matchTableName'], $manyToManyTableName);
		}
		if (!empty($configuration['matchFieldName'])) {
			$where[] = 'fieldname=' . $this->getDatabaseConnection()->fullQuoteStr($configuration['matchFieldName'], $manyToManyTableName);
		}

		$references = $this->getDatabaseConnection()->exec_SELECTgetRows(
			$selectField,
			$manyToManyTableName,
			implode(' AND ', $where),
			'',
			$sortingField,
			'',
			$selectField
		);

		if (empty($references)) {
			return $content;
		}

		$content = implode(',', array_keys($references));
		return $content;
	}

	/**
	 * @param string $property
	 * @param array $configuration
	 * @return string
	 */
	protected function getValue($property, array $configuration = NULL) {
		$value = '';

		if (!empty($configuration[$property])) {
			$value = $configuration[$property];
		}
		if (!empty($configuration[$property . '.'])) {
			$value = $this->cObj->stdWrap($value, $configuration[$property . '.']);
		}

		return $value;
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
