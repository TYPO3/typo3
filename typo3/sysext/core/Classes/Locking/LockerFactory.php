<?php
namespace TYPO3\CMS\Core\Locking;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Daniel Hürtgen <huertgen@rheinschafe.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
 * This locker factory takes core of instantiating of valid locker types.
 *
 * @author Daniel Hürtgen <huertgen@rheinschafe.de>
 * @api
 */
class LockerFactory implements \Typo3\CMS\Core\SingletonInterface {

	/**
	 * Factory method which creates the specified lockertype.
	 *
	 * @param string $className
	 * @param string $context
	 * @param string $id
	 * @param array  $options
	 * @throws \InvalidArgumentException
	 * @return \TYPO3\CMS\Core\Locking\Locker\LockerInterface
	 * @api
	 */
	public function create($className, $context, $id, array $options = array()) {
		if (!is_subclass_of($className, 'TYPO3\\CMS\\Core\\Locking\\Locker\\LockerInterface')) {
			// TODO
			throw new \InvalidArgumentException('Class ' . $className . ' does not exist.', 1314979197);
		}

		/** @var $locker \TYPO3\CMS\Core\Locking\Locker\LockerInterface */
		$locker = GeneralUtility::makeInstance($className, $context, $id, $options);

		return $locker;
	}

}

?>
