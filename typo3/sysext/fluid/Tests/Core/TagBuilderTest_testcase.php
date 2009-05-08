<?php

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

/**
 * @package
 * @subpackage
 * @version $Id$
 */

/**
 * Testcase for TagBuilder
 *
 * @package
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_TagBuilderTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_constructorSetsTagName() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('someTagName');
		$this->assertEquals('someTagName', $tagBuilder->getTagName());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_constructorSetsAndEscapesTagContent() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('', '<some text>');
		$this->assertEquals('&lt;some text&gt;', $tagBuilder->getContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_setContentEscapesValueByDefault() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder();
		$tagBuilder->setContent('<to be escaped>');
		$this->assertEquals('&lt;to be escaped&gt;', $tagBuilder->getContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_setContentDoesNotEscapeValueIfDisabled() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder();
		$tagBuilder->setContent('<to be escaped>', FALSE);
		$this->assertEquals('<to be escaped>', $tagBuilder->getContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_hasContentReturnsTrueIfTagContainsText() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('', 'foo');
		$this->assertTrue($tagBuilder->hasContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_hasContentReturnsFalseIfContentIsNull() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder();
		$tagBuilder->setContent(NULL);
		$this->assertFalse($tagBuilder->hasContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_hasContentReturnsFalseIfContentIsAnEmptyString() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder();
		$tagBuilder->setContent('');
		$this->assertFalse($tagBuilder->hasContent());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_renderReturnsEmptyStringByDefault() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder();
		$this->assertEquals('', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_renderReturnsSelfClosingTagIfNoContentIsSpecified() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('tag');
		$this->assertEquals('<tag />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_renderReturnsOpeningAndClosingTagIfNoContentIsSpecifiedButForceClosingTagIsTrue() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('tag');
		$tagBuilder->forceClosingTag(TRUE);
		$this->assertEquals('<tag></tag>', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_attributesAreProperlyRendered() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('tag');
		$tagBuilder->addAttribute('attribute1', 'attribute1value');
		$tagBuilder->addAttribute('attribute2', 'attribute2value');
		$tagBuilder->addAttribute('attribute3', 'attribute3value');
		$this->assertEquals('<tag attribute1="attribute1value" attribute2="attribute2value" attribute3="attribute3value" />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_attributeValuesAreEscapedByDefault() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('tag');
		$tagBuilder->addAttribute('foo', '<to be escaped>');
		$this->assertEquals('<tag foo="&lt;to be escaped&gt;" />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_attributeValuesAreNotEscapedIfDisabled() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('tag');
		$tagBuilder->addAttribute('foo', '<not to be escaped>', FALSE);
		$this->assertEquals('<tag foo="<not to be escaped>" />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_attributesCanBeRemoved() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('tag');
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
	public function test_tagNameCanBeOverridden() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('foo');
		$tagBuilder->setTagName('bar');
		$this->assertEquals('<bar />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_tagContentCanBeOverridden() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('foo', 'some content');
		$tagBuilder->setContent('');
		$this->assertEquals('<foo />', $tagBuilder->render());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function test_tagIsNotRenderedIfTagNameIsEmpty() {
		$tagBuilder = new Tx_Fluid_Core_TagBuilder('foo');
		$tagBuilder->setTagName('');
		$this->assertEquals('', $tagBuilder->render());
	}
}

?>
