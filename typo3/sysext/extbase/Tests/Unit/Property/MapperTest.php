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
 * Testcase for the Property Mapper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @covers \TYPO3\CMS\Extbase\Property\Mapper
 */
class MapperTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Property\Mapper
	 */
	protected $fixture;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		$objectManager = new \TYPO3\CMS\Extbase\Object\ObjectManager();

		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager */
		$persistenceManager = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');

		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory $queryFactory */
		$queryFactory = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryFactory');

		/** @var \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService */
		$reflectionService = $this->getMock('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService');
		$reflectionService->injectObjectManager($objectManager);

		/** @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver */
		$validatorResolver = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\ValidatorResolver');

		$this->fixture = new \TYPO3\CMS\Extbase\Property\Mapper();
		$this->fixture->injectObjectManager($objectManager);
		$this->fixture->injectPersistenceManager($persistenceManager);
		$this->fixture->injectQueryFactory($queryFactory);
		$this->fixture->injectReflectionService($reflectionService);
		$this->fixture->injectValidatorResolver($validatorResolver);
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function mapReturnsObjectForNamespaceClasses() {
		$source = array(
			'property1' => 'foo',
			'property2' => 'bar'
		);

		$expectedObject = new \TYPO3\CMS\Extbase\Tests\Fixture\ClassWithGettersAndSetters();
		$expectedObject->setProperty1($source['property1']);
		$expectedObject->setProperty2($source['property2']);

		$this->assertEquals($expectedObject, $this->fixture->map(array('property1', 'property2'), $source, 'TYPO3\\CMS\\Extbase\\Tests\\Fixture\\ClassWithGettersAndSetters'));
	}

	/**
	 * @test
	 */
	public function mapReturnsObjectForOldTxClasses() {
		$source = array(
			'property1' => 'foo',
			'property2' => 'bar'
		);

		$expectedObject = new \Tx_Extbase_Tests_Fixture_TxClassWithGettersAndSetters();
		$expectedObject->setProperty1($source['property1']);
		$expectedObject->setProperty2($source['property2']);

		$this->assertEquals($expectedObject, $this->fixture->map(array('property1', 'property2'), $source, 'Tx_Extbase_Tests_Fixture_TxClassWithGettersAndSetters'));
	}
}

?>