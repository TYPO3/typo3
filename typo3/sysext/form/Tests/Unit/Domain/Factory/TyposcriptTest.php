<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Stefan Neufeind <info@speedpartner.de>, SpeedPartner GmbH
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

	// @TODO for some reason these classes fail to autoload
require_once(dirname(__FILE__) . '/../../../../Classes/System/Request/Request.php');
require_once(dirname(__FILE__) . '/../../../../Classes/System/Validate/Validate.php');
require_once(dirname(__FILE__) . '/../../../../Classes/System/Elementcounter/Elementcounter.php');
require_once(dirname(__FILE__) . '/../../../../Classes/System/Localization/Localization.php');
require_once(dirname(__FILE__) . '/../../../../Classes/System/Filter/Filter.php');

/**
 * Test case for class tx_form_Domain_Factory_Typoscript
 *
 * @author Stefan Neufeind <info@speedpartner.de>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_Domain_Factory_TyposcriptTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @var tx_form_Domain_Factory_Typoscript
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new tx_form_Domain_Factory_Typoscript();
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