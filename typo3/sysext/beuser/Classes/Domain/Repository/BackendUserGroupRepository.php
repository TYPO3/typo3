<?php
namespace TYPO3\CMS\Beuser\Domain\Repository;

/**
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

/**
 * Repository for \TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class BackendUserGroupRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * @var array Default order is by title ascending
	 */
	protected $defaultOrderings = array(
		'title' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
	);
}
