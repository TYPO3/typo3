<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Widget;

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * Testcase for WidgetRequestHandler
 */
class WidgetRequestHandlerTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetRequestHandler
	 */
	protected $widgetRequestHandler;

	/**
	 * @var array
	 */
	protected $getBackup;

	/**

	 */
	public function setUp() {
		$this->getBackup = $_GET;
		$this->widgetRequestHandler = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Widget\\WidgetRequestHandler', array('dummy'), array(), '', FALSE);
	}

	public function tearDown() {
		$_GET = $this->getBackup;
	}

	/**
	 * @test
	 */
	public function canHandleRequestReturnsTrueIfCorrectGetParameterIsSet() {
		$_GET['fluid-widget-id'] = 123;
		$this->assertTrue($this->widgetRequestHandler->canHandleRequest());
	}

	/**
	 * @test
	 */
	public function canHandleRequestReturnsFalsefGetParameterIsNotSet() {
		$_GET['some-other-id'] = 123;
		$this->assertFalse($this->widgetRequestHandler->canHandleRequest());
	}

	/**
	 * @test
	 */
	public function priorityIsHigherThanDefaultRequestHandler() {
		$defaultWebRequestHandler = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Web\\AbstractRequestHandler', array('handleRequest'), array(), '', FALSE);
		$this->assertTrue($this->widgetRequestHandler->getPriority() > $defaultWebRequestHandler->getPriority());
	}
}

?>