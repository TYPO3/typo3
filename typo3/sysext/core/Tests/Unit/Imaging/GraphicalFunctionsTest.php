<?php
namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

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
 * Testcase for \TYPO3\CMS\Core\Imaging\GraphicalFunctions
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 */
class GraphicalFunctionsTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Imaging\GraphicalFunctions
	 */
	protected $fixture = NULL;

	/**
	 * Set up
	 *
	 * @return void
	 */
	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\Imaging\GraphicalFunctions();
	}

	/**
	 * Dataprovider for getScaleForImage
	 *
	 * @return array
	 */
	public function getScaleForImageDataProvider() {
		return array(
			'Get image scale for a width of 150px' => array(
				array(
					170,
					136,
				),
				'150',
				'',
				array(),
				array(
					'crs' => FALSE,
					'origW' => 150,
					'origH' => 0,
					'max' => 0,
					0 => 150,
					1 => (float) 120
				),
			),
			'Get image scale with a maximum width of 100px' => array(
				array(
					170,
					136,
				),
				'',
				'',
				array(
					'maxW' => 100
				),
				array(
					'crs' => FALSE,
					'origW' => 100,
					'origH' => 0,
					'max' => 1,
					0 => 100,
					1 => (float) 80
				),
			),
			'Get image scale with a minimum width of 200px' => array(
				array(
					170,
					136,
				),
				'',
				'',
				array(
					'minW' => 200
				),
				array(
					'crs' => FALSE,
					'origW' => 0,
					'origH' => 0,
					'max' => 0,
					0 => 200,
					1 => (float) 136
				),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getScaleForImageDataProvider
	 */
	public function getScaleForImage($info, $width, $height, $options, $expected) {
		$result = $this->fixture->getImageScale($info, $width, $height, $options);
		$this->assertEquals($result, $expected);
	}
}
