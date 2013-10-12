<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Markus Klein <klein.t3@mfc-linz.at>
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

use TYPO3\CMS\Core\Utility\DateTimeUtility;

/**
 * Fixture to be able to mock protected static functions
 *
 * @author Markus Klein <klein.t3@mfc-linz.at>
 */
class DateTimeUtilityFixture extends DateTimeUtility {

	/**
	 * @var string
	 */
	static public $mockGetTimeDiffString = '';

	/**
	 * @param \DateTime $startDateTime
	 * @param \DateTime $endDateTime
	 * @param null $labels
	 * @return string
	 */
	static public function getTimeDiffString(\DateTime $startDateTime, \DateTime $endDateTime, $labels = NULL) {
		if (self::$mockGetTimeDiffString) {
			return self::$mockGetTimeDiffString;
		}
		return parent::getTimeDiffString($startDateTime, $endDateTime, $labels);
	}
}