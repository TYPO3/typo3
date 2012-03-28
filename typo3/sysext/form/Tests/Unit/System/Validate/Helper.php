<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Andreas Lappe <a.lappe@kuehlhaus.com>, kuehlhaus AG
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
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Validate_Helper extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * Helper method to get a mock of a requestHandler returning
	 * defined values if asked.
	 *
	 * @param array $data $key=>$value pairs to return if asked for key
	 * @return TODO
	 */
	public function getRequestHandler($data) {
		$requestHandlerMock = $this->getMock('tx_form_System_Request', array(
			'has', 'getByMethod'
		));

		foreach ($data as $key => $value) {
			$requestHandlerMock->expects($this->atLeastOnce())
				->method('has')
				->with($key)
				->will($this->returnValue(TRUE));
			$requestHandlerMock->expects($this->once())
				->method('getByMethod')
				->with($key)
				->will($this->returnValue($value));
		}

		return $requestHandlerMock;
	}
}
?>