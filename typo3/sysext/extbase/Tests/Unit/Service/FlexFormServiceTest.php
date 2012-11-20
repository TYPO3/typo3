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
/**
 * Testcase for class \TYPO3\CMS\Extbase\Service\FlexFormService
 */
class FlexFormServiceTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Service\FlexFormService
	 */
	protected $flexFormService;

	public function setUp() {
		$this->flexFormService = new \TYPO3\CMS\Extbase\Service\FlexFormService();
	}

	/**
	 * @return array
	 */
	public function convertFlexFormContentToArrayTestData() {
		$testdata = array();
		$testdata[0] = array(
			'flexFormXML' => '<?xml version="1.0" encoding="iso-8859-1" standalone="yes"?>
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
</T3FlexForms>',
			'expectedFlexFormArray' => array(
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
			)
		);
		return $testdata;
	}

	/**
	 * @test
	 * @dataProvider convertFlexFormContentToArrayTestData
	 * @param string $flexFormXML
	 * @param array $expectedFlexFormArray
	 */
	public function convertFlexFormContentToArrayResolvesComplexArrayStructure($flexFormXML, $expectedFlexFormArray) {
		$convertedFlexFormArray = $this->flexFormService->convertFlexFormContentToArray($flexFormXML);
		$this->assertSame($expectedFlexFormArray, $convertedFlexFormArray);
	}
}

?>