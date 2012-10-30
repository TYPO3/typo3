<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Exception;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
 *                                                                        *
 * This class is a backport of the corresponding class of FLOW3.          *
 * All credits go to the v5 team.                                         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An "NotImplementedException" exception
 */
class NotImplementedException extends \TYPO3\CMS\Extbase\Persistence\Exception {

	/**
	 * @param string $method
	 */
	public function __construct($method) {
		parent::__construct(sprintf('Method %s is not supported by generic persistence"', $method), 1350213237);
	}
}

?>