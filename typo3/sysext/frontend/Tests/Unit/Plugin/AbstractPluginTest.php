<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Plugin;

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
 * Testcase for TYPO3\CMS\Frontend\Plugin\AbstractPlugin
 *
 * @author Stefan Neufeind <info (at) speedpartner.de>
 */
class AbstractPluginTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
	 */
	protected $abstractPlugin;

	/**
	 * @var array
	 */
	protected $defaultPiVars;

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		parent::setUp();

		// Allow objects until 100 levels deep when executing the stdWrap
		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->cObjectDepthCounter = 100;

		$this->abstractPlugin = new \TYPO3\CMS\Frontend\Plugin\AbstractPlugin();
		$this->abstractPlugin->cObj = new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer();
		$this->defaultPiVars = $this->abstractPlugin->piVars;
	}

	/**
	 * Data provider for piSetPiVarDefaultsStdWrap
	 *
	 * @return array input-array with configuration and stdWrap, expected output-array in piVars
	 */
	public function piSetPiVarDefaultsStdWrapProvider() {
		return array(
			'stdWrap on conf, non-recursive, stdWrap 1 level deep' => array(
				array(
					'abc' => 'DEF',
					'abc.' => array(
						'stdWrap.' => array(
							'wrap' => 'test | test'
						),
					),
				),
				array(
					'abc' => 'testDEFtest',
					'pointer' => '',
					'mode' => '',
					'sword' => '',
					'sort' => '',
				),
			),
			'stdWrap on conf, non-recursive, stdWrap 2 levels deep' => array(
				array(
					'xyz.' => array(
						'stdWrap.' => array(
							'cObject' => 'TEXT',
							'cObject.' => array(
								'data' => 'date:U',
								'strftime' => '%Y',
							),
						),
					),
				),
				array(
					'xyz' => date('Y'),
					'pointer' => '',
					'mode' => '',
					'sword' => '',
					'sort' => '',
				),
			),
			'stdWrap on conf, recursive' => array(
				array(
					'abc.' => array(
						'def' => 'DEF',
						'def.' => array(
							'ghi' => '123',
							'stdWrap.' => array(
								'wrap' => 'test | test'
							),
						),
					),
				),
				array(
					'abc.' => array(
						'def' => 'testDEFtest',
						'def.' => array(
							'ghi' => '123',
						),
					),
					'pointer' => '',
					'mode' => '',
					'sword' => '',
					'sort' => '',
				),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider piSetPiVarDefaultsStdWrapProvider
	 */
	public function piSetPiVarDefaultsStdWrap($input, $expected) {
		$this->abstractPlugin->piVars = $this->defaultPiVars;

		$this->abstractPlugin->conf['_DEFAULT_PI_VARS.'] = $input;
		$this->abstractPlugin->pi_setPiVarDefaults();
		$this->assertEquals($expected, $this->abstractPlugin->piVars);
	}

}
