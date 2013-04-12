<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Steffen Müller (typo3@t3node.com)
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

require_once __DIR__ . DIRECTORY_SEPARATOR . '../Fixtures/ProcessorFixture.php';

/**
 * Testcase for \TYPO3\CMS\Core\Log\Processor\Abstract
 *
 * @author Steffen Müller <typo3@t3node.com>
 */
class AbstractTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function processorRefusesInvalidConfigurationOptions() {
		$invalidConfiguration = array(
			'foo' => 'bar'
		);
		$processor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Tests\\Unit\\Log\\Fixtures\\ProcessorFixture', $invalidConfiguration);
	}

}

?>