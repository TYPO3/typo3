<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Property;

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
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

require_once __DIR__ . '/../../Fixture/TxClassWithGettersAndSetters.php';

/**
 * Test case
 */
class MapperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Property\Mapper
	 */
	protected $subject;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager */
		$persistenceManager = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');

		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory $queryFactory */
		$queryFactory = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactory');

		/** @var \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService */
		$reflectionService = $this-> getAccessibleMock('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService');

		/** @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver */
		$validatorResolver = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver');

		$this->subject = $this->getAccessibleMock('TYPO3\CMS\Extbase\Property\Mapper', array('dummy'));
		$this->subject->_set('persistenceManager', $persistenceManager);
		$this->subject->_set('queryFactory', $queryFactory);
		$this->subject->_set('reflectionService', $reflectionService);
		$this->subject->_set('validatorResolver', $validatorResolver);
	}

	/**
	 * @test
	 */
	public function mapReturnsObjectForNamespaceClasses() {
		/** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
		$objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$objectManager->expects($this->at(0))->method('get')->will($this->returnValue($this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\ClassWithGettersAndSetters')));
		$this->subject->_set('objectManager', $objectManager);

		$source = array(
			'property1' => 'foo',
			'property2' => 'bar'
		);

		$expectedObject = $this->getMock('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\ClassWithGettersAndSetters');
		$expectedObject->setProperty1($source['property1']);
		$expectedObject->setProperty2($source['property2']);

		$this->assertEquals($expectedObject, $this->subject->map(array('property1', 'property2'), $source, 'TYPO3\\CMS\\Extbase\\Tests\\Fixture\\ClassWithGettersAndSetters'));
	}

	/**
	 * @test
	 */
	public function mapReturnsObjectForOldTxClasses() {

		/** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
		$objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$objectManager->expects($this->at(0))->method('get')->will($this->returnValue($this->getMock('Tx_Extbase_Tests_Fixture_TxClassWithGettersAndSetters')));
		$this->subject->_set('objectManager', $objectManager);
		$source = array(
			'property1' => 'foo',
			'property2' => 'bar'
		);

		$expectedObject = $this->getMock('Tx_Extbase_Tests_Fixture_TxClassWithGettersAndSetters');
		$expectedObject->setProperty1($source['property1']);
		$expectedObject->setProperty2($source['property2']);

		$this->assertEquals($expectedObject, $this->subject->map(array('property1', 'property2'), $source, 'Tx_Extbase_Tests_Fixture_TxClassWithGettersAndSetters'));
	}
}
