<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
require_once __DIR__ . '/../ViewHelperBaseTestcase.php';

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

?>