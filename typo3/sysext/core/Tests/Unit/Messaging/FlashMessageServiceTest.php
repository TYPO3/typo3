<?php
namespace TYPO3\CMS\Core\Tests\Unit\Messaging;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Schnitzler <alex.schnitzler@typovision.de>
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
 * Testcase for the TYPO3\CMS\Core\Messaging\FlashMessageService class.
 *
 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
 */
class FlashMessageServiceTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Messaging\FlashMessageService|\PHPUnit_Framework_MockObject_MockObject|\Tx_Phpunit_Interface_AccessibleObject
	 */
	protected $flashMessageService;

	public function setUp() {
		$this->flashMessageService = $this->getAccessibleMock('TYPO3\CMS\Core\Messaging\FlashMessageService', array('dummy'));
	}

	/**
	 * @test
	 */
	public function flashMessageServiceInitiallyIsEmpty() {
		$this->assertSame(array(), $this->flashMessageService->_get('flashMessageQueues'));
	}

	/**
	 * @test
	 */
	public function getMessageQueueByIdentifierRegistersNewFlashmessageQueuesOnlyOnce() {
		$this->assertSame(
			$this->flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages'),
			$this->flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages')
		);
	}
}

?>