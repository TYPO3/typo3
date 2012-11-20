<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Scheduler;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Stefan Neufeind <info@speedpartner.de>
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
 * FieldProvider Test Class
 */
class FieldProviderTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager
	 */
	protected $commandManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Scheduler\FieldProvider
	 */
	protected $fieldProvider;

	/**
	 *
	 * @test
	 */
	public function getCommandControllerActionFieldFetchesCorrectClassNames() {
		$fieldProvider = $fieldProvider = $this->getMock($this->buildAccessibleProxy('\TYPO3\CMS\Extbase\Scheduler\FieldProvider'), array('dummy'));
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'] = array(
			'TYPO3\\CMS\\Extbase\\Tests\\MockACommandController',
			'Acme\\Mypkg\\Command\\MockCCommandController',
			'Tx_Extbase_Command_MockDCommandController'
		);
		eval('
			namespace TYPO3\\CMS\\Extbase\\Tests; class MockACommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController { function mockBCommand() {} }
			namespace Acme\\Mypkg\\Command; class MockCCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController { function mockBCommand() {} }
		');
		eval('
			class Tx_Extbase_Command_MockDCommandController extends Tx_Extbase_MVC_Controller_CommandController { function mockBCommand() {} }
		');

		$actualResult = $fieldProvider->_call('getCommandControllerActionField', array());
		$this->assertContains('<option title="test" value="extbase:mocka:mockb">Extbase MockA: mockB</option>', $actualResult['code']);
		$this->assertContains('<option title="test" value="mypkg:mockc:mockb">Mypkg MockC: mockB</option>', $actualResult['code']);
		$this->assertContains('<option title="test" value="extbase:mockd:mockb">Extbase MockD: mockB</option>', $actualResult['code']);
	}

	/**
	 * @test
	 */
	public function constructResolvesExtensionnameFromNamespaced() {
		$className = uniqid('DummyController');
		eval('namespace ' . __NAMESPACE__ . '; class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\Mvc\\Controller\\AbstractController { function getExtensionName() { return $this->extensionName; } }');
		$classNameNamespaced = __NAMESPACE__ . '\\' . $className;
		$mockController = new $classNameNamespaced();
		$expectedResult = 'Extbase';
		$actualResult = $mockController->getExtensionName();
		$this->assertSame($expectedResult, $actualResult);
	}
}

?>