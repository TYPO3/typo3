<?php
namespace TYPO3\CMS\Form\Tests\View\Mail\Html\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Helmut Hummel <helmut.hummel@typo3.org>
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
?>