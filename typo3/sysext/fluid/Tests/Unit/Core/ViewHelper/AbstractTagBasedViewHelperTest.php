<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\ViewHelper;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for TagBasedViewHelper
 */
class AbstractTagBasedViewHelperTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	public function setUp() {
		$this->viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractTagBasedViewHelper', array('dummy'), array(), '', FALSE);
	}

	/**
	 * @test
	 */
	public function initializeResetsUnderlyingTagBuilder() {
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('reset'), array(), '', FALSE);
		$mockTagBuilder->expects($this->once())->method('reset');
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->initialize();
	}

	/**
	 * @test
	 */
	public function oneTagAttributeIsRenderedCorrectly() {
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('addAttribute'), array(), '', FALSE);
		$mockTagBuilder->expects($this->once())->method('addAttribute')->with('foo', 'bar');
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->_call('registerTagAttribute', 'foo', 'string', 'Description', FALSE);
		$arguments = array('foo' => 'bar');
		$this->viewHelper->setArguments($arguments);
		$this->viewHelper->initialize();
	}

	/**
	 * @test
	 */
	public function additionalTagAttributesAreRenderedCorrectly() {
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('addAttribute'), array(), '', FALSE);
		$mockTagBuilder->expects($this->once())->method('addAttribute')->with('foo', 'bar');
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$this->viewHelper->_call('registerTagAttribute', 'foo', 'string', 'Description', FALSE);
		$arguments = array('additionalAttributes' => array('foo' => 'bar'));
		$this->viewHelper->setArguments($arguments);
		$this->viewHelper->initialize();
	}

	/**
	 * @test
	 */
	public function standardTagAttributesAreRegistered() {
		$mockTagBuilder = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TagBuilder', array('addAttribute'), array(), '', FALSE);
		$mockTagBuilder->expects($this->at(0))->method('addAttribute')->with('class', 'classAttribute');
		$mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('dir', 'dirAttribute');
		$mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('id', 'idAttribute');
		$mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('lang', 'langAttribute');
		$mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('style', 'styleAttribute');
		$mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('title', 'titleAttribute');
		$mockTagBuilder->expects($this->at(6))->method('addAttribute')->with('accesskey', 'accesskeyAttribute');
		$mockTagBuilder->expects($this->at(7))->method('addAttribute')->with('tabindex', 'tabindexAttribute');
		$this->viewHelper->injectTagBuilder($mockTagBuilder);

		$arguments = array(
			'class' => 'classAttribute',
			'dir' => 'dirAttribute',
			'id' => 'idAttribute',
			'lang' => 'langAttribute',
			'style' => 'styleAttribute',
			'title' => 'titleAttribute',
			'accesskey' => 'accesskeyAttribute',
			'tabindex' => 'tabindexAttribute'
		);
		$this->viewHelper->_call('registerUniversalTagAttributes');
		$this->viewHelper->setArguments($arguments);
		$this->viewHelper->initializeArguments();
		$this->viewHelper->initialize();
	}

	/**
	 * @test
	 */
	public function registerTagAttributeRegistersArgumentWithDefaultValue() {
		$this->viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractTagBasedViewHelper', array('registerArgument'), array(), '', FALSE);
		$this->viewHelper->expects($this->once())->method('registerArgument')->with('foo', 'string', 'Description', FALSE, 'defaultValue');
		$this->viewHelper->_call('registerTagAttribute', 'foo', 'string', 'Description', FALSE, 'defaultValue');
	}

	/**
	 * @test
	 */
	public function registerTagAttributeRegistersArgumentWithNullIfNoDefaultValueIsSet() {
		$this->viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractTagBasedViewHelper', array('registerArgument'), array(), '', FALSE);
		$this->viewHelper->expects($this->once())->method('registerArgument')->with('foo', 'string', 'Description', FALSE, NULL);
		$this->viewHelper->_call('registerTagAttribute', 'foo', 'string', 'Description', FALSE);
	}
}

?>