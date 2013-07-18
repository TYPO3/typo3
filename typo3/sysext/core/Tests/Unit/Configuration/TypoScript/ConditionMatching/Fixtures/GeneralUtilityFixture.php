<?php
namespace TYPO3\CMS\Core\Tests\Unit\Configuration\TypoScript\ConditionMatching\Fixtures;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Steffen Müller <typo3@t3node.com>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Fixture for TYPO3\CMS\Core\Utility\GeneralUtility
 *
 * @author Steffen Müller <typo3@t3node.com>
 */
class GeneralUtilityFixture extends \TYPO3\CMS\Core\Utility\GeneralUtility {

	/**
	 * @param \TYPO3\CMS\Core\Core\ApplicationContext $applicationContext
	 * @return void
	 */
	static public function setApplicationContext($applicationContext) {
		static::$applicationContext = $applicationContext;
	}
}
