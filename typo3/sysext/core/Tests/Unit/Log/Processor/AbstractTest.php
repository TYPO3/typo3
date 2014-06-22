<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

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
