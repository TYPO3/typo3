<?php
namespace TYPO3\CMS\Form\Tests\Unit\PostProcess;

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

/**
 * Testcase for PostProcessor
 */
class PostProcessorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Form\PostProcess\PostProcessor
	 */
	public $subject;

	/**
	 * Set up
	 */
	protected function setUp() {
		$form = $this->getMock(\TYPO3\CMS\Form\Domain\Model\Form::class, array(), array(), '', FALSE);
		$this->subject = $this->getMock(
			\TYPO3\CMS\Form\PostProcess\PostProcessor::class,
			array('sortTypoScriptKeyList'),
			array($form, array())
		);
	}

	/**
	 * @test
	 */
	public function processFindsClassSpecifiedByTypoScriptWithoutFormPrefix() {
		$typoScript = array(
			10 => $this->getUniqueId('postprocess'),
			20 => \TYPO3\CMS\Form\Tests\Unit\Fixtures\PostProcessorWithoutFormPrefixFixture::class
		);
		$this->subject->typoScript = $typoScript;
		$this->subject->expects($this->once())->method('sortTypoScriptKeyList')->will($this->returnValue(array(10, 20)));
		$returnValue = $this->subject->process();
		$this->assertEquals('processedWithoutPrefix', $returnValue);
	}

	/**
	 * @test
	 */
	public function processFindsClassSpecifiedByTypoScriptWithFormPrefix() {
		$typoScript = array(
			10 => $this->getUniqueId('postprocess'),
			20 => \TYPO3\CMS\Form\Tests\Unit\Fixtures\PostProcessorWithFormPrefixFixture::class
		);
		$this->subject->typoScript = $typoScript;
		$this->subject->expects($this->once())->method('sortTypoScriptKeyList')->will($this->returnValue(array(10, 20)));
		$returnValue = $this->subject->process();
		$this->assertEquals('processedWithPrefix', $returnValue);
	}

	/**
	 * @test
	 */
	public function processReturnsEmptyStringIfSpecifiedPostProcessorDoesNotImplementTheInterface() {
		$typoScript = array(
			10 => $this->getUniqueId('postprocess'),
			20 => \TYPO3\CMS\Form\Tests\Unit\Fixtures\PostProcessorWithoutInterfaceFixture::class
		);
		$this->subject->typoScript = $typoScript;
		$this->subject->expects($this->once())->method('sortTypoScriptKeyList')->will($this->returnValue(array(10, 20)));
		$returnValue = $this->subject->process();
		$this->assertEquals('', $returnValue);
	}

}
