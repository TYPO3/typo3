<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * Add vanilla table TCA
 *
 * @todo: This one could vanish again - if all dependencies are
 * @todo: correct it would be sufficient to work with processedTca only
 */
class TableTca implements FormDataProviderInterface {

	/**
	 * Add TCA of table
	 *
	 * @param array $result
	 * @return array
	 * @throws \UnexpectedValueException
	 */
	public function addData(array $result) {
		if (
			!isset($GLOBALS['TCA'][$result['tableName']])
			|| !is_array($GLOBALS['TCA'][$result['tableName']])
		) {
			throw new \UnexpectedValueException(
				'TCA for table ' . $result['tableName'] . ' not found',
				1437914223
			);
		}
		$result['vanillaTableTca'] = $GLOBALS['TCA'][$result['tableName']];
		return $result;
	}

}
