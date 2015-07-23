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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * On "new" command, initialize uid with an unqique uid
 */
class DatabaseUniqueUidNewRow implements FormDataProviderInterface {

	/**
	 * Initialize new row with unique uid
	 *
	 * @param array $result
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	public function addData(array $result) {
		if ($result['command'] !== 'new') {
			return $result;
		}
		// Throw exception if uid is already set
		if (isset($result['databaseRow']['uid'])) {
			throw new \InvalidArgumentException(
				'uid is already set to ' . $result['databaseRow']['uid'],
				1437991120
			);
		}
		// @todo: There is a dot in this uid that can give us headaches if the
		// @todo: uid is used as id argument in HTML. It would probably better
		// @todo: to remove the dot here already.
		$result['databaseRow']['uid'] = uniqid('NEW', TRUE);
		return $result;
	}

}
