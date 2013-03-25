<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\Factory;

/***************************************************************
*  Copyright notice
*
*  (c) 2012-2013 Stefan Neufeind <info@speedpartner.de>, SpeedPartner GmbH
*
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
 * Test case for class \TYPO3\CMS\Form\Domain\Factory\TypoScriptFactory
 *
 * @author Stefan Neufeind <info@speedpartner.de>
 */
class TypoScriptFactoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
	/**
	 * @var tx_form_Domain_Factory_Typoscript
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Form\Domain\Factory\TypoScriptFactory();
	}

	public function tearDown() {
		unset($this->fixture);
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

		$element = $this->fixture->createElement('textline', $input);

		$this->assertSame(
			$inputStdWrapped,
			$element->getValue()
		);
	}
}
?>