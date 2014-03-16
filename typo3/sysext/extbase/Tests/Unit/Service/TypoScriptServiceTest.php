<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Christian Müller <christian@kitsunet.de>
 *  (c) 2011 Bastian Waidelich <bastian@typo3.org>
 *  (c) 2014 Markus Klein <klein.t3@mfc-linz.at>
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
 * Test case
 */
class TypoScriptServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * data provider for convertTypoScriptArrayToPlainArray
	 * @return array
	 */
	public function convertTypoScriptArrayToPlainArrayTestdata() {
		return array(
			'simple typoscript array' => array(
				'typoScriptSettings' => array(
					'10.' => array(
						'value' => 'Hello World!',
						'foo.' => array(
							'bar' => 5
						)
					),
					'10' => 'TEXT'
				),
				'expectedSettings' => array(
					'10' => array(
						'value' => 'Hello World!',
						'foo' => array(
							'bar' => 5
						),
						'_typoScriptNodeValue' => 'TEXT'
					)
				)
			),
			'typoscript with intermediate dots' => array(
				'typoScriptSettings' => array(
					'10.' => array(
						'value' => 'Hello World!',
						'foo.' => array(
							'bar' => 5
						)
					),
					'10' => 'TEXT'
				),
				'expectedSettings' => array(
					'10' => array(
						'value' => 'Hello World!',
						'foo' => array(
							'bar' => 5
						),
						'_typoScriptNodeValue' => 'TEXT'
					)
				)
			),
			'typoscript array with changed order' => array(
				'typoScriptSettings' => array(
					'10' => 'TEXT',
					'10.' => array(
						'value' => 'Hello World!',
						'foo.' => array(
							'bar' => 5
						)
					)
				),
				'expectedSettings' => array(
					'10' => array(
						'value' => 'Hello World!',
						'foo' => array(
							'bar' => 5
						),
						'_typoScriptNodeValue' => 'TEXT'
					)
				)
			),
			'nested typoscript array' => array(
				'typoScriptSettings' => array(
					'10' => 'COA',
					'10.' => array(
						'10' => 'TEXT',
						'10.' => array(
							'value' => 'Hello World!',
							'foo.' => array(
								'bar' => 5
							)
						),
						'20' => 'COA',
						'20.' => array(
							'10' => 'TEXT',
							'10.' => array(
								'value' => 'Test',
								'wrap' => '[|]'
							),
							'20' => 'TEXT',
							'20.' => array(
								'value' => 'Test',
								'wrap' => '[|]'
							)
						),
						'30' => 'custom'
					)
				),
				'expectedSettings' => array(
					'10' => array(
						'10' => array(
							'value' => 'Hello World!',
							'foo' => array(
								'bar' => 5
							),
							'_typoScriptNodeValue' => 'TEXT'
						),
						'20' => array(
							'10' => array(
								'value' => 'Test',
								'wrap' => '[|]',
								'_typoScriptNodeValue' => 'TEXT'
							),
							'20' => array(
								'value' => 'Test',
								'wrap' => '[|]',
								'_typoScriptNodeValue' => 'TEXT'
							),
							'_typoScriptNodeValue' => 'COA'
						),
						'30' => 'custom',
						'_typoScriptNodeValue' => 'COA'
					)
				)
			),
		);
	}

	/**
	 * @test
	 * @dataProvider convertTypoScriptArrayToPlainArrayTestdata
	 * @param mixed $typoScriptSettings
	 * @param mixed $expectedSettings
	 */
	public function convertTypoScriptArrayToPlainArrayRemovesTrailingDotsWithChangedOrderInTheTypoScriptArray($typoScriptSettings, $expectedSettings) {
		$typoScriptService = new \TYPO3\CMS\Extbase\Service\TypoScriptService();
		$processedSettings = $typoScriptService->convertTypoScriptArrayToPlainArray($typoScriptSettings);
		$this->assertEquals($expectedSettings, $processedSettings);
	}

	/**
	 * Dataprovider for testcase "convertPlainArrayToTypoScriptArray"
	 *
	 * @return array
	 */
	public function convertPlainArrayToTypoScriptArrayTestdata() {
		return array(
			'simple typoscript' => array(
				'extbaseTS' => array(
					'10' => array(
						'value' => 'Hallo',
						'_typoScriptNodeValue' => 'TEXT'
					)
				),
				'classic' => array(
					'10' => 'TEXT',
					'10.' => array(
						'value' => 'Hallo'
					)
				)
			),
			'typoscript with null value' => array(
				'extbaseTS' => array(
					'10' => array(
						'value' => 'Hallo',
						'_typoScriptNodeValue' => 'TEXT'
					),
				    '20' => NULL
				),
				'classic' => array(
					'10' => 'TEXT',
					'10.' => array(
						'value' => 'Hallo'
					),
				    '20' => ''
				)
			),
			'ts with dots in key' => array(
				'extbaseTS' => array(
					'1.0' => array(
						'value' => 'Hallo',
						'_typoScriptNodeValue' => 'TEXT'
					)
				),
				'classic' => array(
					'1.0' => 'TEXT',
					'1.0.' => array(
						'value' => 'Hallo'
					)
				)
			),
			'ts with backslashes in key' => array(
				'extbaseTS' => array(
					'1\\0\\' => array(
						'value' => 'Hallo',
						'_typoScriptNodeValue' => 'TEXT'
					)
				),
				'classic' => array(
					'1\\0\\' => 'TEXT',
					'1\\0\\.' => array(
						'value' => 'Hallo'
					)
				)
			),
			'bigger typoscript' => array(
				'extbaseTS' => array(
					'10' => array(
						'10' => array(
							'value' => 'Hello World!',
							'foo' => array(
								'bar' => 5
							),
							'_typoScriptNodeValue' => 'TEXT'
						),
						'20' => array(
							'10' => array(
								'value' => 'Test',
								'wrap' => '[|]',
								'_typoScriptNodeValue' => 'TEXT'
							),
							'20' => array(
								'value' => 'Test',
								'wrap' => '[|]',
								'_typoScriptNodeValue' => 'TEXT'
							),
							'_typoScriptNodeValue' => 'COA'
						),
						'_typoScriptNodeValue' => 'COA'
					)
				),
				'classic' => array(
					'10' => 'COA',
					'10.' => array(
						'10' => 'TEXT',
						'10.' => array(
							'value' => 'Hello World!',
							'foo.' => array(
								'bar' => 5
							)
						),
						'20' => 'COA',
						'20.' => array(
							'10' => 'TEXT',
							'10.' => array(
								'value' => 'Test',
								'wrap' => '[|]'
							),
							'20' => 'TEXT',
							'20.' => array(
								'value' => 'Test',
								'wrap' => '[|]'
							)
						)
					)
				)
			),
		);
	}

	/**
	 * @test
	 * @dataProvider convertPlainArrayToTypoScriptArrayTestdata
	 * @param mixed $extbaseTS
	 * @param mixed $classic
	 */
	public function convertPlainArrayToTypoScriptArray($extbaseTS, $classic) {
		$typoScriptService = new \TYPO3\CMS\Extbase\Service\TypoScriptService();
		$converted = $typoScriptService->convertPlainArrayToTypoScriptArray($extbaseTS);
		$this->assertEquals($converted, $classic);
	}
}
