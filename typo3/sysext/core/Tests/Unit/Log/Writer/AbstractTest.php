<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Writer;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Steffen Gebert (steffen.gebert@typo3.org)
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once __DIR__ . DIRECTORY_SEPARATOR . '../Fixtures/WriterFixture.php';

/**
 * Testcase for \TYPO3\CMS\Core\Log\Writer\AbstractWriter
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class AbstractTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function refusesInvalidConfigurationOptions() {
		$invalidConfiguration = array(
			'foo' => 'bar'
		);
		$writer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Tests\\Unit\\Log\\Fixtures\\WriterFixture', $invalidConfiguration);
	}

}

?>