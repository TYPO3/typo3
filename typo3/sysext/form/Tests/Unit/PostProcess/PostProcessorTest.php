<?php
namespace TYPO3\CMS\Form\Tests\Unit\PostProcess;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <typo3@susannemoog.de>
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
 * Test case for class tx_form_System_Postprocessor
 *
 * @author Susanne Moog, <typo3@susannemoog.de>
 * @package TYPO3
 * @subpackage form
 */
class PostProcessorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	public $postprocessor;

	/**
	 * @var string
	 */
	public $classNameWithoutPrefix;

	/**
	 * @var string
	 */
	public $classNameWithPrefix;

	/**
	 * @var string
	 */
	public $classNameWithoutInterface;

	/**
	 * @var \TYPO3\CMS\Form\Domain\Model\Form
	 */
	public $form;

	/**
	 * set up
	 */
	public function setUp() {
		$this->form = new \TYPO3\CMS\Form\Domain\Model\Form();
		$this->postprocessor = $this->getMock('TYPO3\\CMS\\Form\\PostProcess\\PostProcessor', array('sortTypoScriptKeyList'), array(
			$this->form,
			array()
		));
		$this->classNameWithoutPrefix = uniqid('postprocess');
		$this->classNameWithPrefix = uniqid('postprocess');
		$this->classNameWithoutInterface = uniqid('postprocess');
		eval(((((((('class ' . $this->classNameWithoutPrefix) . ' implements TYPO3\\CMS\\Form\\PostProcess\\PostProcessor_Interface {

				public function __construct(TYPO3\\CMS\\Form\\Domain\\Model\\Form $form, array $typoScript) {

				}

				public function process() {
					return \'processedWithoutPrefix\';
				}
			}') . 'class TYPO3\\CMS\\Form\\PostProcess\\PostProcessor_') . $this->classNameWithPrefix) . ' implements TYPO3\\CMS\\Form\\PostProcess\\PostProcessor_Interface {

				public function __construct(TYPO3\\CMS\\Form\\Domain\\Model\\Form $form, array $typoScript) {

				}

				public function process() {
					return \'processedWithPrefix\';
				}
			}') . 'class ') . $this->classNameWithoutInterface) . '{

				public function __construct(TYPO3\\CMS\\Form\\Domain\\Model\\Form $form, array $typoScript) {

				}

				public function process() {
					return \'withoutInterface\';
				}
			}');
	}

	/**
	 * @test
	 */
	public function processFindsClassSpecifiedByTypoScriptWithoutFormPrefix() {
		$typoScript = array(
			10 => uniqid('postprocess'),
			20 => $this->classNameWithoutPrefix
		);
		$this->postprocessor->typoScript = $typoScript;
		$this->postprocessor->expects($this->once())->method('sortTypoScriptKeyList')->will($this->returnValue(array(10, 20)));
		$returnValue = $this->postprocessor->process();
		$this->assertEquals('processedWithoutPrefix', $returnValue);
	}

	/**
	 * @test
	 */
	public function processFindsClassSpecifiedByTypoScriptWithFormPrefix() {
		$typoScript = array(
			10 => uniqid('postprocess'),
			20 => $this->classNameWithPrefix
		);
		$this->postprocessor->typoScript = $typoScript;
		$this->postprocessor->expects($this->once())->method('sortTypoScriptKeyList')->will($this->returnValue(array(10, 20)));
		$returnValue = $this->postprocessor->process();
		$this->assertEquals('processedWithPrefix', $returnValue);
	}

	/**
	 * @test
	 */
	public function processReturnsEmptyStringIfSpecifiedPostProcessorDoesNotImplementTheInterface() {
		$typoScript = array(
			10 => uniqid('postprocess'),
			20 => $this->classNameWithoutInterface
		);
		$this->postprocessor->typoScript = $typoScript;
		$this->postprocessor->expects($this->once())->method('sortTypoScriptKeyList')->will($this->returnValue(array(10, 20)));
		$returnValue = $this->postprocessor->process();
		$this->assertEquals('', $returnValue);
	}

}


?>