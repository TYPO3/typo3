<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Alexander Schnitzler <alex.schnitzler@typovision.de>
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
 * Testcase for \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory
 *
 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class QueryFactoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var string
	 */
	protected $className = 'Vendor\\Ext\\Domain\\Model\\ClubMate';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory
	 */
	protected $queryFactory = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $objectManager = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $dataMapper = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $dataMap;

	protected function setUp() {
		$this->dataMap = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMap', array('getIsStatic', 'getRootLevel'), array('Vendor\\Ext\\Domain\\Model\\ClubMate', 'tx_ext_domain_model_clubmate'));

		$this->queryFactory = new \TYPO3\CMS\Extbase\Persistence\Generic\QueryFactory();
		$this->queryFactory->injectConfigurationManager(
			$this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface')
		);

		$this->objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->queryFactory->injectObjectManager($this->objectManager);

		$this->dataMapper = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper', array('getDataMap', 'convertClassNameToTableName'));
		$this->dataMapper->expects($this->any())->method('getDataMap')->will($this->returnValue($this->dataMap));
		$this->queryFactory->injectDataMapper($this->dataMapper);
	}

	protected function tearDown() {
		unset($this->objectManager, $this->dataMapper, $this->queryFactory);
	}

	public function getStaticAndRootLevelAndExpectedResult() {
		return array(
			'Respect storage page is set when entity is neither marked as static nor as rootLevel.' => array(FALSE, FALSE, TRUE),
			'Respect storage page is set when entity is marked as static and rootLevel.' => array(TRUE, TRUE, FALSE),
			'Respect storage page is set when entity is marked as static but not rootLevel.' => array(TRUE, FALSE, FALSE),
			'Respect storage page is set when entity is not marked as static but as rootLevel.' => array(FALSE, TRUE, FALSE),
		);
	}

	/**
	 * @param boolean $static
	 * @param boolean $rootLevel
	 * @param boolean $expectedResult
	 *
	 * @dataProvider getStaticAndRootLevelAndExpectedResult
	 * @test
	 */
	public function createDoesNotRespectStoragePageIfStaticOrRootLevelIsTrue($static, $rootLevel, $expectedResult) {
		$this->dataMap->expects($this->any())->method('getIsStatic')->will($this->returnValue($static));
		$this->dataMap->expects($this->any())->method('getRootLevel')->will($this->returnValue($rootLevel));

		$query = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\QueryInterface');
		$this->objectManager->expects($this->at(0))->method('get')
			->with('TYPO3\\CMS\\Extbase\\Persistence\\QueryInterface')
			->will($this->returnValue($query));

		$querySettings = new \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings();
		$this->objectManager->expects($this->at(1))->method('get')
			->with('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface')
			->will($this->returnValue($querySettings));
		$query->expects($this->once())->method('setQuerySettings')->with($querySettings);
		$this->queryFactory->create($this->className);

		$this->assertSame(
			$expectedResult,
			$querySettings->getRespectStoragePage()
		);
	}
}

?>