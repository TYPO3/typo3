<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for TagBuilder
 *
 * @version $Id: TagBuilderTest.php 3835 2010-02-22 15:15:17Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_TagBuilderTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsTagName() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('someTagName');
		$this->assertEquals('someTagName', $tagBuilder->getTagName());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsTagContent() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('', '<some text>');
		$this->assertEquals('<some text>', $tagBuilder->getContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setContentDoesNotEscapeValue() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder();
		$tagBuilder->setContent('<to be escaped>', FALSE);
		$this->assertEquals('<to be escaped>', $tagBuilder->getContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasContentReturnsTrueIfTagContainsText() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('', 'foo');
		$this->assertTrue($tagBuilder->hasContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasContentReturnsFalseIfContentIsNull() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder();
		$tagBuilder->setContent(NULL);
		$this->assertFalse($tagBuilder->hasContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasContentReturnsFalseIfContentIsAnEmptyString() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder();
		$tagBuilder->setContent('');
		$this->assertFalse($tagBuilder->hasContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyStringByDefault() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder();
		$this->assertEquals('', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsSelfClosingTagIfNoContentIsSpecified() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('tag');
		$this->assertEquals('<tag />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function contentCanBeRemoved() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('tag', 'some content');
		$tagBuilder->setContent(NULL);
		$this->assertEquals('<tag />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsOpeningAndClosingTagIfNoContentIsSpecifiedButForceClosingTagIsTrue() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('tag');
		$tagBuilder->forceClosingTag(TRUE);
		$this->assertEquals('<tag></tag>', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function attributesAreProperlyRendered() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('tag');
		$tagBuilder->addAttribute('attribute1', 'attribute1value');
		$tagBuilder->addAttribute('attribute2', 'attribute2value');
		$tagBuilder->addAttribute('attribute3', 'attribute3value');
		$this->assertEquals('<tag attribute1="attribute1value" attribute2="attribute2value" attribute3="attribute3value" />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function attributeValuesAreEscapedByDefault() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('tag');
		$tagBuilder->addAttribute('foo', '<to be escaped>');
		$this->assertEquals('<tag foo="&lt;to be escaped&gt;" />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function attributeValuesAreNotEscapedIfDisabled() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('tag');
		$tagBuilder->addAttribute('foo', '<not to be escaped>', FALSE);
		$this->assertEquals('<tag foo="<not to be escaped>" />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function attributesCanBeRemoved() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('tag');
		$tagBuilder->addAttribute('attribute1', 'attribute1value');
		$tagBuilder->addAttribute('attribute2', 'attribute2value');
		$tagBuilder->addAttribute('attribute3', 'attribute3value');
		$tagBuilder->removeAttribute('attribute2');
		$this->assertEquals('<tag attribute1="attribute1value" attribute3="attribute3value" />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resetResetsTagBuilder() {
		$tagBuilder = $this->getAccessibleMock('Tx_Fluid_Core_ViewHelper_TagBuilder', array('dummy'));
		$tagBuilder->setTagName('tagName');
		$tagBuilder->setContent('some content');
		$tagBuilder->forceClosingTag(TRUE);
		$tagBuilder->addAttribute('attribute1', 'attribute1value');
		$tagBuilder->addAttribute('attribute2', 'attribute2value');
		$tagBuilder->reset();

		$this->assertEquals('', $tagBuilder->_get('tagName'));
		$this->assertEquals('', $tagBuilder->_get('content'));
		$this->assertEquals(array(), $tagBuilder->_get('attributes'));
		$this->assertFalse($tagBuilder->_get('forceClosingTag'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function tagNameCanBeOverridden() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('foo');
		$tagBuilder->setTagName('bar');
		$this->assertEquals('<bar />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function tagContentCanBeOverridden() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('foo', 'some content');
		$tagBuilder->setContent('');
		$this->assertEquals('<foo />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function tagIsNotRenderedIfTagNameIsEmpty() {
		$tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder('foo');
		$tagBuilder->setTagName('');
		$this->assertEquals('', $tagBuilder->render());
	}
}

?>
