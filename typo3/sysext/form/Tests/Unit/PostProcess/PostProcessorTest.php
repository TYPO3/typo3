<?php
namespace TYPO3\CMS\Form\Tests\Unit\PostProcess;

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
 * Testcase for PostProcessor
 */
class PostProcessorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Form\PostProcess\PostProcessor
	 */
	public $fixture;

	/**
	 * Set up
	 */
	public function setUp() {
		$form = $this->getMock('TYPO3\\CMS\\Form\\Domain\\Model\\Form', array(), array(), '', FALSE);
		$this->fixture = $this->getMock(
			'TYPO3\CMS\Form\PostProcess\PostProcessor',
			array('sortTypoScriptKeyList'),
			array($form, array())
		);
	}

	/**
	 * @test
	 */
	public function processFindsClassSpecifiedByTypoScriptWithoutFormPrefix() {
		$classNameWithoutPrefix = $this->getUniqueId('postprocess');
		eval(
			'namespace TYPO3\CMS\Form\PostProcess;' .
			'class ' . $classNameWithoutPrefix . 'PostProcessor implements PostProcessorInterface {' .
			'  public function __construct(\TYPO3\CMS\Form\Domain\Model\Form $form, array $typoScript) {' .
			'  }' .
			'  public function process() {' .
			'    return \'processedWithoutPrefix\';' .
			'  }' .
			'}'
		);
		$typoScript = array(
			10 => $this->getUniqueId('postprocess'),
			20 => $classNameWithoutPrefix
		);
		$this->fixture->typoScript = $typoScript;
		$this->fixture->expects($this->once())->method('sortTypoScriptKeyList')->will($this->returnValue(array(10, 20)));
		$returnValue = $this->fixture->process();
		$this->assertEquals('processedWithoutPrefix', $returnValue);
	}

	/**
	 * @test
	 */
	public function processFindsClassSpecifiedByTypoScriptWithFormPrefix() {
		$classNameWithPrefix = $this->getUniqueId('postprocess');
		eval(
			'namespace TYPO3\CMS\Form\PostProcess;' .
			'class ' . $classNameWithPrefix . 'PostProcessor implements PostProcessorInterface {' .
			'  public function __construct(\TYPO3\CMS\Form\Domain\Model\Form $form, array $typoScript) {' .
			'  }' .
			'  public function process() {' .
			'    return \'processedWithPrefix\';' .
			'  }' .
			'}'
		);
		$typoScript = array(
			10 => $this->getUniqueId('postprocess'),
			20 => $classNameWithPrefix
		);
		$this->fixture->typoScript = $typoScript;
		$this->fixture->expects($this->once())->method('sortTypoScriptKeyList')->will($this->returnValue(array(10, 20)));
		$returnValue = $this->fixture->process();
		$this->assertEquals('processedWithPrefix', $returnValue);
	}

	/**
	 * @test
	 */
	public function processReturnsEmptyStringIfSpecifiedPostProcessorDoesNotImplementTheInterface() {
		$classNameWithoutInterface = $this->getUniqueId('postprocess');
		eval(
			'namespace TYPO3\CMS\Form\PostProcess;' .
			'class ' . $classNameWithoutInterface . 'PostProcessor {' .
			'  public function __construct(\TYPO3\CMS\Form\Domain\Model\Form $form, array $typoScript) {' .
			'  }' .
			'  public function process() {' .
			'    return \'withoutInterface\';' .
			'  }' .
			'}'
		);
		$typoScript = array(
			10 => $this->getUniqueId('postprocess'),
			20 => $classNameWithoutInterface
		);
		$this->fixture->typoScript = $typoScript;
		$this->fixture->expects($this->once())->method('sortTypoScriptKeyList')->will($this->returnValue(array(10, 20)));
		$returnValue = $this->fixture->process();
		$this->assertEquals('', $returnValue);
	}

}
