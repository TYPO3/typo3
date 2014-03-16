<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Felix Oertel <typo3@foertel.com>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class FlexFormServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array Backup of singletons
	 */
	protected $backupSingletons = array();

	/**
	 * Set up
	 */
	public function setUp() {
		$this->backupSingletons = GeneralUtility::getSingletonInstances();
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
		GeneralUtility::resetSingletonInstances($this->backupSingletons);
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function convertFlexFormContentToArrayResolvesComplexArrayStructure() {
		$input = '<?xml version="1.0" encoding="iso-8859-1" standalone="yes"?>
<T3FlexForms>
	<data>
		<sheet index="sDEF">
			<language index="lDEF">
				<field index="settings.foo">
					<value index="vDEF">Foo-Value</value>
				</field>
				<field index="settings.bar">
					<el index="el">
						<section index="1">
							<itemType index="_arrayContainer">
								<el>
									<field index="baz">
										<value index="vDEF">Baz1-Value</value>
									</field>
									<field index="bum">
										<value index="vDEF">Bum1-Value</value>
									</field>
								</el>
							</itemType>
							<itemType index="_TOGGLE">0</itemType>
						</section>
						<section index="2">
							<itemType index="_arrayContainer">
								<el>
									<field index="baz">
										<value index="vDEF">Baz2-Value</value>
									</field>
									<field index="bum">
										<value index="vDEF">Bum2-Value</value>
									</field>
								</el>
							</itemType>
							<itemType index="_TOGGLE">0</itemType>
						</section>
					</el>
				</field>
			</language>
		</sheet>
	</data>
</T3FlexForms>';

		$expected = array(
			'settings' => array(
				'foo' => 'Foo-Value',
				'bar' => array(
					1 => array(
						'baz' => 'Baz1-Value',
						'bum' => 'Bum1-Value'
					),
					2 => array(
						'baz' => 'Baz2-Value',
						'bum' => 'Bum2-Value'
					)
				)
			)
		);

		// The subject calls xml2array statically, which calls getHash and setHash statically, which uses
		// caches, those need to be mocked.
		$cacheManagerMock = $this->getMock('TYPO3\\CMS\\Core\\Cache\\CacheManager', array(), array(), '', FALSE);
		$cacheMock = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface', array(), array(), '', FALSE);
		$cacheManagerMock->expects($this->any())->method('getCache')->will($this->returnValue($cacheMock));
		GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager', $cacheManagerMock);

		$flexFormService = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\FlexFormService', array('dummy'), array(), '', FALSE);
		$convertedFlexFormArray = $flexFormService->convertFlexFormContentToArray($input);
		$this->assertSame($expected, $convertedFlexFormArray);
	}
}
