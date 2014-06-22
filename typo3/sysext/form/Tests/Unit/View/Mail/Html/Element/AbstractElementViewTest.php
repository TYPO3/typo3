<?php
namespace TYPO3\CMS\Form\Tests\Unit\View\Mail\Html\Element;

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
 *
 * @author Stefan Neufeind
 */
class AbstractElementViewTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getInputValueDoesNotHtmlSpecialCharBr() {
		$model = $this->getMock('TYPO3\\CMS\\Form\\Domain\\Model\\Element\\AbstractElement');
		$model
			->expects($this->once())
			->method('getAttributeValue')
			->with('value')
			->will($this->returnValue('a&' . LF . 'b'));

		/** @var $fixture \TYPO3\CMS\Form\View\Mail\Html\Element\AbstractElementView|PHPUnit_Framework_MockObject_MockObject */
		$fixture = $this->getMock(
			'TYPO3\\CMS\\Form\\View\\Mail\\Html\\Element\\AbstractElementView',
			array('dummy'),
			array($model)
		);

		$this->assertSame('a&amp;<br />' . LF . 'b', $fixture->getInputValue());
	}
}
