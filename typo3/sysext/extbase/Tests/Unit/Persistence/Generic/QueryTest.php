<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
 *  (c) 2010 Bastian Waidelich <bastian@typo3.org>
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
class QueryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Query
	 */
	protected $query;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
	 */
	protected $querySettings;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\BackendInterface
	 */
	protected $backend;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	protected $dataMapper;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 */
	public function setUp() {
		$this->objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->query = new \TYPO3\CMS\Extbase\Persistence\Generic\Query('someType');
		$this->query->injectObjectManager($this->objectManager);
		$this->querySettings = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$this->query->setQuerySettings($this->querySettings);
		$this->persistenceManager = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface');
		$this->backend = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\BackendInterface');
		$this->backend->expects($this->any())->method('getQomFactory')->will($this->returnValue(NULL));
		$this->persistenceManager->expects($this->any())->method('getBackend')->will($this->returnValue($this->backend));
		$this->query->injectPersistenceManager($this->persistenceManager);
		$this->dataMapper = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper');
		$this->query->injectDataMapper($this->dataMapper);
	}

	/**
	 * @test
	 */
	public function executeReturnsQueryResultInstanceAndInjectsItself() {
		$queryResult = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QueryResult', array(), array(), '', FALSE);
		$this->objectManager->expects($this->once())->method('get')->with('TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface', $this->query)->will($this->returnValue($queryResult));
		$actualResult = $this->query->execute();
		$this->assertSame($queryResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function executeReturnsRawObjectDataIfRawQueryResultSettingIsTrue() {
		$this->querySettings->expects($this->once())->method('getReturnRawQueryResult')->will($this->returnValue(TRUE));
		$this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue('rawQueryResult'));
		$expectedResult = 'rawQueryResult';
		$actualResult = $this->query->execute();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setLimitAcceptsOnlyIntegers() {
		$this->query->setLimit(1.5);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setLimitRejectsIntegersLessThanOne() {
		$this->query->setLimit(0);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setOffsetAcceptsOnlyIntegers() {
		$this->query->setOffset(1.5);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setOffsetRejectsIntegersLessThanZero() {
		$this->query->setOffset(-1);
	}

	/**
	 * @return array
	 */
	public function equalsForCaseSensitiveFalseLowercasesOperandProvider() {
		return array(
			'Polish alphabet' => array('name', 'ĄĆĘŁŃÓŚŹŻABCDEFGHIJKLMNOPRSTUWYZQXVąćęłńóśźżabcdefghijklmnoprstuwyzqxv', 'ąćęłńóśźżabcdefghijklmnoprstuwyzqxvąćęłńóśźżabcdefghijklmnoprstuwyzqxv'),
			'German alphabet' => array('name', 'ßÜÖÄüöä', 'ßüöäüöä'),
			'Greek alphabet' => array('name', 'Τάχιστη αλώπηξ βαφής ψημένη γη', 'τάχιστη αλώπηξ βαφής ψημένη γη'),
			'Russian alphabet' => array('name', 'АВСТРАЛИЯавстралия', 'австралияавстралия')
		);
	}

	/**
	 * Checks if equals condition makes utf-8 argument lowercase correctly
	 *
	 * @test
	 * @dataProvider equalsForCaseSensitiveFalseLowercasesOperandProvider
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param string $expectedOperand
	 */
	public function equalsForCaseSensitiveFalseLowercasesOperand($propertyName, $operand, $expectedOperand) {
		/** @var $qomFactory \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory */
		$qomFactory = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\QueryObjectModelFactory', array('comparison'));
		$qomFactory->injectObjectManager(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager'));
		$qomFactory->expects($this->once())->method('comparison')->with($this->anything(), $this->anything(), $expectedOperand);
		$this->query->injectQomFactory($qomFactory);
		$this->query->equals($propertyName, $operand, FALSE);
	}
}

?>