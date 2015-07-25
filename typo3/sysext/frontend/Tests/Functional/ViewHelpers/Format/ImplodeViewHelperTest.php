<?php
namespace TYPO3\CMS\Frontend\Tests\Functional\ViewHelpers\Format;

/*
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
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\CMS\Frontend\ViewHelpers\Format\ImplodeViewHelper;

/**
 * Class ImplodeViewHelperTest
 */
class ImplodeViewHelperTest extends ViewHelperBaseTestcase {

	/**
	 * @var ImplodeViewHelper|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $subject;

	protected function setUp() {
		parent::setUp();
		$this->subject = $this->getMock(ImplodeViewHelper::class, array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($this->subject);
	}

	/**
	 * @return array
	 */
	public function implodeDataProvider() {
		return [
			'Join elements with space' => [
				['class-a', 'class-b', 'class-c'],
				' ',
				TRUE,
				'class-a class-b class-c'
			],
			'Join elements with space excluding empty values' => [
				['class-a', 'class-b', '', 'class-d'],
				' ',
				TRUE,
				'class-a class-b class-d'
			],
			'Join elements with space including empty values' => [
				['class-a', 'class-b', '', 'class-d'],
				' ',
				FALSE,
				'class-a class-b  class-d'
			],
			'Join elements with /' => [
				['class-a', 'class-b', 'class-c'],
				'/',
				TRUE,
				'class-a/class-b/class-c'
			],
			'Join elements with / excluding empty values' => [
				['class-a', 'class-b', '', 'class-d'],
				'/',
				TRUE,
				'class-a/class-b/class-d'
			],
			'Join elements with / including empty values' => [
				['class-a', 'class-b', '', 'class-d'],
				'/',
				FALSE,
				'class-a/class-b//class-d'
			],
		];
	}

	/**
	 * @test
	 * @dataProvider implodeDataProvider
	 */
	public function ImplodeViewHelperOutputTest($values, $glue, $excludeEmptyValues, $expectedResult) {
		$actualResult = $this->subject->render($values, $glue, $excludeEmptyValues);
		$this->assertEquals($expectedResult, $actualResult);
	}

}