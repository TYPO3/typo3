<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

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

 */
class EmailViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Link\EmailViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $cObjBackup;

	public function setUp() {
		parent::setUp();
		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->cObj = $this->getMock('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer', array(), array(), '', FALSE);
		$this->viewHelper = $this->getMock($this->buildAccessibleProxy('TYPO3\\CMS\\Fluid\\ViewHelpers\\Link\\EmailViewHelper'), array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 */
	public function renderCorrectlySetsTagNameAndAttributesAndContent() {
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('setTagName', 'addAttribute', 'setContent'));
		$mockTagBuilder->expects($this->once())->method('setTagName')->with('a');
		$mockTagBuilder->expects($this->once())->method('addAttribute')->with('href', 'mailto:some@email.tld');
		$mockTagBuilder->expects($this->once())->method('setContent')->with('some content');
		$this->viewHelper->_set('tag', $mockTagBuilder);
		$this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('some content'));
		$this->viewHelper->initialize();
		$this->viewHelper->render('some@email.tld');
	}

	/**
	 * @test
	 */
	public function renderSetsTagContentToEmailIfRenderChildrenReturnNull() {
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('setTagName', 'addAttribute', 'setContent'));
		$mockTagBuilder->expects($this->once())->method('setContent')->with('some@email.tld');
		$this->viewHelper->_set('tag', $mockTagBuilder);
		$this->viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue(NULL));
		$this->viewHelper->initialize();
		$this->viewHelper->render('some@email.tld');
	}
}
