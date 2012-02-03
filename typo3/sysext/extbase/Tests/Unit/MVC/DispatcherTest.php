<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Jochen Rau <jochen.rau@typoplanet.de>
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
 * Testcase for the MVC Dispatcher
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_Tests_Unit_MVC_DispatcherTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function dispatchCallsTheControllersProcessRequestMethodUntilTheIsDispatchedFlagInTheRequestObjectIsSet() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface');
		$mockRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(1))->method('isDispatched')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(2))->method('isDispatched')->will($this->returnValue(TRUE));

		$mockResponse = $this->getMock('Tx_Extbase_MVC_ResponseInterface');

		$mockController = $this->getMock('Tx_Extbase_MVC_Controller_ControllerInterface', array('processRequest', 'canProcessRequest'));
		$mockController->expects($this->exactly(2))->method('processRequest')->with($mockRequest, $mockResponse);
		$mockSignalSlotDispatcher = $this->getMock('Tx_Extbase_SignalSlot_Dispatcher', array('dispatch'));

		$dispatcher = $this->getMock('Tx_Extbase_MVC_Dispatcher', array('resolveController'), array(), '', FALSE);
		$dispatcher->injectSignalSlotDispatcher($mockSignalSlotDispatcher);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_InfiniteLoop
	 */
	public function dispatchThrowsAnInfiniteLoopExceptionIfTheRequestCouldNotBeDispachedAfter99Iterations() {
		$requestCallCounter = 0;
		$requestCallBack = function() use (&$requestCallCounter) {
			return ($requestCallCounter++ < 101) ? FALSE : TRUE;
		};
		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface');
		$mockRequest->expects($this->any())->method('isDispatched')->will($this->returnCallBack($requestCallBack, '__invoke'));

		$mockResponse = $this->getMock('Tx_Extbase_MVC_ResponseInterface');
		$mockController = $this->getMock('Tx_Extbase_MVC_Controller_ControllerInterface', array('processRequest', 'canProcessRequest'));
		$mockSignalSlotDispatcher = $this->getMock('Tx_Extbase_SignalSlot_Dispatcher', array('dispatch'));

		$dispatcher = $this->getMock('Tx_Extbase_MVC_Dispatcher', array('resolveController'), array(), '', FALSE);
		$dispatcher->injectSignalSlotDispatcher($mockSignalSlotDispatcher);
		$dispatcher->expects($this->any())->method('resolveController')->will($this->returnValue($mockController));
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 */
	public function dispatchDispatchesSignalAfterDispatchOfRequest() {

		$mockRequest = $this->getMock('Tx_Extbase_MVC_RequestInterface');
		$mockRequest->expects($this->at(0))->method('isDispatched')->will($this->returnValue(TRUE));

		$mockResponse = $this->getMock('Tx_Extbase_MVC_ResponseInterface');

		$mockSignalSlotDispatcher = $this->getMock('Tx_Extbase_SignalSlot_Dispatcher', array('dispatch'));
		$mockSignalSlotDispatcher->expects($this->exactly(1))->method('dispatch')->with('Tx_Extbase_MVC_Dispatcher', 'afterRequestDispatch', array('request' => $mockRequest, 'response' => $mockResponse));

		$dispatcher = $this->getMock('Tx_Extbase_MVC_Dispatcher', array('resolveController'), array(), '', FALSE);
		$dispatcher->injectSignalSlotDispatcher($mockSignalSlotDispatcher);
		$dispatcher->dispatch($mockRequest, $mockResponse);
	}
}
?>
