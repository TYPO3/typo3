<?php
namespace TYPO3\CMS\Backend\Toolbar\Enumeration;

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

/**
 * This class holds the severities of the SystemInformation toolbar menu
 */
class InformationStatus extends \TYPO3\CMS\Core\Type\Enumeration {

	/**
	 * @var string
	 */
	const STATUS_DEFAULT = '';

	/**
	 * @var string
	 */
	const STATUS_OK = 'success';

	/**
	 * @var string
	 */
	const STATUS_WARNING = 'warning';

	/**
	 * @var string
	 */
	const STATUS_ERROR = 'danger';

	/**
	 * @var int[]
	 */
	static protected $statusIntegerMap = array(
		self::STATUS_DEFAULT => -1,
		self::STATUS_OK => 0,
		self::STATUS_WARNING => 1,
		self::STATUS_ERROR => 2
	);

	/**
	 * Map the status string to an integer
	 *
	 * @param string $status
	 * @return int
	 */
	static public function mapStatusToInt($status) {
		if (isset(static::$statusIntegerMap[$status])) {
			return static::$statusIntegerMap[$status];
		}
		return -1;
	}
}