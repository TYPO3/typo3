<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\Factory;

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
 * Test case for class \TYPO3\CMS\Form\Domain\Factory\TypoScriptFactory
 */
class TypoScriptFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Domain\Factory\TypoScriptFactory
	 */
	protected $subject;

	public function setUp() {
		$this->subject = new \TYPO3\CMS\Form\Domain\Factory\TypoScriptFactory();
	}

	/**
	 * @test
	 */
	public function stdWrapIsAppliedToElementValue() {
		$input = array(
				'value' => 'something',
				'value.' => array(
					'wrap' => 'ABC|DEF'
				)
			);
		$inputStdWrapped = 'ABCsomethingDEF';

		$element = $this->subject->createElement('textline', $input);

		$this->assertSame(
			$inputStdWrapped,
			$element->getValue()
		);
	}
}
