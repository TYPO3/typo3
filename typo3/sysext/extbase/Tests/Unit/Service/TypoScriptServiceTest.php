<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
