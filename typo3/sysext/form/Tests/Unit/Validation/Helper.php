<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validation;
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
 * Small helper class to DRY the code.
 */
class Helper extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array
	 */
	protected $mockData = array();

	/**
	 * Helper method to get a mock of a requestHandler returning
	 * defined values if asked.
	 *
	 * @param array $data $key => $value pairs to return if asked for key
	 * @return \TYPO3\CMS\Form\Request|\PHPUnit_Framework_MockObject_MockObject
	 */
	public function getRequestHandler(array $data) {
		$this->mockData = $data;

		$requestHandlerMock = $this->getMock('TYPO3\\CMS\\Form\\Request', array('has', 'getByMethod'));
		$requestHandlerMock->expects($this->any())->method('has')->will($this->returnCallback(array($this, 'has')));
		$requestHandlerMock->expects($this->any())->method('getByMethod')
			->will($this->returnCallback(array($this, 'getByMethod')));

		return $requestHandlerMock;
	}

	/**
	 * Callback for \TYPO3\CMS\Form\Request::getByMethod.
	 *
	 * Returns the value stored for $key
	 *
	 * @param string $key the key of the value to retrieve, must not be empty
	 * @return mixed the stored value for $key or FALSE if there is no value for $key stored
	 */
	public function getByMethod($key) {
		if (!$this->has($key)) {
			return FALSE;
		}

		return $this->mockData[$key];
	}

	/**
	 * Callback for \TYPO3\CMS\Form\Request::has()
	 *
	 * Checks whether a value for $key has been stored.
	 *
	 * @param string $key the key to check, must not be empty
	 * @return boolean whether a value for $key has been stored.
	 */
	public function has($key) {
		return isset($this->mockData[$key]);
	}
}
