<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validation;
/***************************************************************
*  Copyright notice
*
*  (c) 2012-2013 Andreas Lappe <a.lappe@kuehlhaus.com>, kuehlhaus AG
*
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
 * Small helper class to DRY the code.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 */
class Helper extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
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
	 * Callback for tx_form_System_Request::has.
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
?>