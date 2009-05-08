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

include_once(dirname(__FILE__) . '/Fixtures/PostParseFacetViewHelper.php');
/**
 * @package Fluid
 * @subpackage Tests
 * @version $Id$
 */
/**
 * Testcase for TemplateParser
 *
 * @package Fluid
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_Core_TemplateParserTest_testcase extends Tx_Extbase_Base_testcase {

	/**
	 * @var Tx_Fluid_TemplateParser
	 */
	protected $templateParser;

	/**
	 * Sets up this test case
	 *
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->templateParser = new Tx_Fluid_Core_TemplateParser();
		$this->templateParser->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.or>
	 * @expectedException Tx_Fluid_Core_ParsingException
	 */
	public function test_parseThrowsExceptionWhenStringArgumentMissing() {
		$this->templateParser->parse(123);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_parseExtractsNamespacesCorrectly() {
		$this->templateParser->parse(" \{namespace f4=F7\Rocks} {namespace f4=Tx_Fluid}");
		$expected = array(
			'f' => 'Tx_Fluid_ViewHelpers',
			'f4' => 'Tx_Fluid'
		);
		$this->assertEquals($this->templateParser->getNamespaces(), $expected, 'Namespaces do not match.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Fluid_Core_ParsingException
	 */
	public function test_parseThrowsExceptionIfNamespaceIsRedeclared() {
		$this->templateParser->parse("{namespace f3=Tx_Fluid_Blablubb} {namespace f3= Tx_Fluid}");
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture01ReturnsCorrectObjectTree() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture01.html', FILE_TEXT);

		$rootNode = new Tx_Fluid_Core_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_SyntaxTree_TextNode("\na"));
		$dynamicNode = new Tx_Fluid_Core_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_BaseViewHelper', array());
		$rootNode->addChildNode($dynamicNode);
		$rootNode->addChildNode(new Tx_Fluid_Core_SyntaxTree_TextNode('b'));

		$expected = $rootNode;
		$actual = $this->templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals($expected, $actual, 'Fixture 01 was not parsed correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture02ReturnsCorrectObjectTree() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture02.html', FILE_TEXT);

		$rootNode = new Tx_Fluid_Core_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_SyntaxTree_TextNode("\n"));
		$dynamicNode = new Tx_Fluid_Core_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_BaseViewHelper', array());
		$dynamicNode->addChildNode(new Tx_Fluid_Core_SyntaxTree_TextNode("\nHallo\n"));
		$rootNode->addChildNode($dynamicNode);
		$dynamicNode = new Tx_Fluid_Core_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_BaseViewHelper', array());
		$dynamicNode->addChildNode(new Tx_Fluid_Core_SyntaxTree_TextNode("Second"));
		$rootNode->addChildNode($dynamicNode);

		$expected = $rootNode;
		$actual = $this->templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals($expected, $actual, 'Fixture 02 was not parsed correctly.');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ParsingException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture03ThrowsExceptionBecauseWrongTagNesting() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture03.html', FILE_TEXT);
		$this->templateParser->parse($templateSource);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ParsingException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture04ThrowsExceptionBecauseClosingATagWhichWasNeverOpened() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture04.html', FILE_TEXT);
		$this->templateParser->parse($templateSource);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture05ReturnsCorrectObjectTree() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture05.html', FILE_TEXT);

		$rootNode = new Tx_Fluid_Core_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_SyntaxTree_TextNode("\na"));
		$dynamicNode = new Tx_Fluid_Core_SyntaxTree_ObjectAccessorNode('posts.bla.Testing3');
		$rootNode->addChildNode($dynamicNode);
		$rootNode->addChildNode(new Tx_Fluid_Core_SyntaxTree_TextNode('b'));

		$expected = $rootNode;
		$actual = $this->templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals($expected, $actual, 'Fixture 05 was not parsed correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture06ReturnsCorrectObjectTree() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture06.html', FILE_TEXT);

		$rootNode = new Tx_Fluid_Core_SyntaxTree_RootNode();
		$arguments = array(
			'each' => new Tx_Fluid_Core_SyntaxTree_RootNode(),
			'as' => new Tx_Fluid_Core_SyntaxTree_RootNode()
		);
		$arguments['each']->addChildNode(new Tx_Fluid_Core_SyntaxTree_ObjectAccessorNode('posts'));
		$arguments['as']->addChildNode(new Tx_Fluid_Core_SyntaxTree_TextNode('post'));
		$dynamicNode = new Tx_Fluid_Core_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_ForViewHelper', $arguments);
		$rootNode->addChildNode($dynamicNode);
		$dynamicNode->addChildNode(new Tx_Fluid_Core_SyntaxTree_ObjectAccessorNode('post'));

		$expected = $rootNode;
		$actual = $this->templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals($expected, $actual, 'Fixture 06 was not parsed correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture07ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture07.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
		$context = new Tx_Fluid_Core_VariableContainer(array('id' => 1));
		$context->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$result = $templateTree->render($context);
		$expected = '1';
		$this->assertEquals($expected, $result, 'Fixture 07 was not parsed correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture08ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture08.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
		$context = new Tx_Fluid_Core_VariableContainer(array('idList' => array(0, 1, 2, 3, 4, 5)));
		$context->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$result = $templateTree->render($context);
		$expected = '0 1 2 3 4 5 ';
		$this->assertEquals($expected, $result, 'Fixture 08 was not rendered correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture09ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture09.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
		$context = new Tx_Fluid_Core_VariableContainer(array('idList' => array(0, 1, 2, 3, 4, 5), 'variableName' => 3));
		$context->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$result = $templateTree->render($context);
		$expected = '0 hallo test 3 4 ';
		$this->assertEquals($expected, $result, 'Fixture 09 was not rendered correctly. This is most likely due to problems in the array parser.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture10ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture10.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
		$context = new Tx_Fluid_Core_VariableContainer(array('idList' => array(0, 1, 2, 3, 4, 5)));
		$context->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$result = $templateTree->render($context);
		$expected = '0 1 2 3 4 5 ';
		$this->assertEquals($expected, $result, 'Fixture 10 was not rendered correctly. This has proboably something to do with line breaks inside tags.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture11ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture11.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
		$context = new Tx_Fluid_Core_VariableContainer(array());
		$context->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$result = $templateTree->render($context);
		$expected = '0 2 4 ';
		$this->assertEquals($expected, $result, 'Fixture 11 was not rendered correctly.');
	}

	/**
	 * Test for CDATA support
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture12ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture12_cdata.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
		$context = new Tx_Fluid_Core_VariableContainer(array());
		$context->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$result = $templateTree->render($context);
		$expected = '<f3:for each="{a: {a: 0, b: 2, c: 4}}" as="array">' . chr(10) . '<f3:for each="{array}" as="value">{value} </f3:for>';
		$this->assertEquals($expected, $result, 'Fixture 12 was not rendered correctly. This hints at some problem with CDATA handling.');
	}

	/**
	 * Test for CDATA support
	 *
	 * @test
	 * @expectedException Tx_Fluid_Core_ParsingException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_fixture13ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture13_mandatoryInformation.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
	}

	/**
	 *
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_postParseFacetIsCalledOnParse() {
		$templateParser = new Tx_Fluid_Core_TemplateParser();
		$templateParser->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());

		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestPostParseFixture.html', FILE_TEXT);
		$templateTree = $templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals(Tx_Fluid_PostParseFacetViewHelper::$wasCalled, TRUE, 'PostParse was not called!');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ParsingException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_abortIfUnregisteredArgumentsExist() {
		$mockTemplateParser = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_TemplateParser'), array('dummy'), array(), '', FALSE);
		$expectedArguments = array(
			new Tx_Fluid_Core_ArgumentDefinition('name1', 'string', 'desc', TRUE),
			new Tx_Fluid_Core_ArgumentDefinition('name2', 'string', 'desc', TRUE)
		);
		$actualArguments = array(
			'name1' => 'bla',
			'name4' => 'bla'
		);
		$mockTemplateParser->_call('abortIfUnregisteredArgumentsExist', $expectedArguments, $actualArguments);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_makeSureThatAbortIfUnregisteredArgumentsExistDoesNotThrowExceptionIfEverythingIsOk() {
		$mockTemplateParser = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_TemplateParser'), array('dummy'), array(), '', FALSE);
		$expectedArguments = array(
			new Tx_Fluid_Core_ArgumentDefinition('name1', 'string', 'desc', TRUE),
			new Tx_Fluid_Core_ArgumentDefinition('name2', 'string', 'desc', TRUE)
		);
		$actualArguments = array(
			'name1' => 'bla'
		);
		$mockTemplateParser->_call('abortIfUnregisteredArgumentsExist', $expectedArguments, $actualArguments);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ParsingException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_abortIfRequiredArgumentsAreMissingShouldThrowExceptionIfRequiredArgumentIsMissing() {
		$mockTemplateParser = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_TemplateParser'), array('dummy'), array(), '', FALSE);
		$expectedArguments = array(
			new Tx_Fluid_Core_ArgumentDefinition('name1', 'string', 'desc', TRUE),
			new Tx_Fluid_Core_ArgumentDefinition('name2', 'string', 'desc', FALSE)
		);
		$actualArguments = array(
			'name2' => 'bla'
		);
		$mockTemplateParser->_call('abortIfRequiredArgumentsAreMissing', $expectedArguments, $actualArguments);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function test_abortIfRequiredArgumentsAreMissingShouldNotThrowExceptionIfRequiredArgumentIsNotMissing() {
		$mockTemplateParser = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_TemplateParser'), array('dummy'), array(), '', FALSE);
		$expectedArguments = array(
			new Tx_Fluid_Core_ArgumentDefinition('name1', 'string', 'desc', FALSE),
			new Tx_Fluid_Core_ArgumentDefinition('name2', 'string', 'desc', FALSE)
		);
		$actualArguments = array(
			'name2' => 'bla'
		);
		$mockTemplateParser->_call('abortIfRequiredArgumentsAreMissing', $expectedArguments, $actualArguments);
	}
}



?>
