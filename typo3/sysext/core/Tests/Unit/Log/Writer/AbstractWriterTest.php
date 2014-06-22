<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Writer;

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
 * Test case
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class AbstractWriterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function constructThrowsExceptionWithInvalidConfigurationOption() {
		$invalidConfiguration = array(
			'foo' => 'bar'
		);
		$this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Log\\Writer\\AbstractWriter', array($invalidConfiguration));
	}
}
