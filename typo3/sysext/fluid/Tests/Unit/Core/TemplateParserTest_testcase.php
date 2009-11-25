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
 * Testcase for TemplateParser
 *
 * @version $Id: TemplateParserTest_testcase.php 1734 2009-11-25 21:53:57Z stucki $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_TemplateParserTest_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @var Tx_Fluid_Core_Parser_TemplateParser
	 */
	protected $templateParser;

	/**
	 * @var Tx_Fluid_Core_Rendering_RenderingContext
	 */
	protected $renderingContext;
	/**
	 * Sets up this test case
	 *
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->templateParser = new Tx_Fluid_Core_Parser_TemplateParser();
		$this->templateParser->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());

		$this->renderingContext = new Tx_Fluid_Core_Rendering_RenderingContext();
		$this->renderingContext->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());
		$this->renderingContext->setControllerContext($this->getMock('Tx_Extbase_MVC_Controller_ControllerContext', array(), array(), '', FALSE));
		$this->renderingContext->setRenderingConfiguration(new Tx_Fluid_Core_Rendering_RenderingConfiguration());
		$this->renderingContext->setViewHelperVariableContainer(new Tx_Fluid_Core_ViewHelper_ViewHelperVariableContainer());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.or>
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 */
	public function parseThrowsExceptionWhenStringArgumentMissing() {
		$this->templateParser->parse(123);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function parseExtractsNamespacesCorrectly() {
		$this->templateParser->parse(" \{namespace f4=F7\Rocks} {namespace f4=Tx_Fluid_Really}");
		$expected = array(
			'f' => 'Tx_Fluid_ViewHelpers',
			'f4' => 'Tx_Fluid_Really'
		);
		$this->assertEquals($this->templateParser->getNamespaces(), $expected, 'Namespaces do not match.');
	}

	/**
	 * @test
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function viewHelperNameCanBeResolved() {
		$mockTemplateParser = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_Parser_TemplateParser'), array('dummy'), array(), '', FALSE);
		$result = $mockTemplateParser->_call('resolveViewHelperName', 'f', 'foo.bar.baz');
		$expected = 'Tx_Fluid_ViewHelpers_Foo_Bar_BazViewHelper';
		$this->assertEquals($result, $expected, 'The name of the View Helper Name could not be resolved.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 */
	public function parseThrowsExceptionIfNamespaceIsRedeclared() {
		$this->templateParser->parse("{namespace f3=Tx_Fluid_Blablubb} {namespace f3= Tx_Fluid_Blu}");
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture01ReturnsCorrectObjectTree($file = '/Fixtures/TemplateParserTestFixture01.html') {
		$templateSource = file_get_contents(dirname(__FILE__) . $file, FILE_TEXT);

		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode("\na"));
		$dynamicNode = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_BaseViewHelper', array());
		$rootNode->addChildNode($dynamicNode);
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('b'));

		$expected = $rootNode;
		$actual = $this->templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals($expected, $actual, 'Fixture 01 was not parsed correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture01ShorthandSyntaxReturnsCorrectObjectTree() {
		$this->fixture01ReturnsCorrectObjectTree('/Fixtures/TemplateParserTestFixture01-shorthand.html');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture02ReturnsCorrectObjectTree($file = '/Fixtures/TemplateParserTestFixture02.html') {
		$templateSource = file_get_contents(dirname(__FILE__) . $file, FILE_TEXT);

		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode("\n"));
		$dynamicNode = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_BaseViewHelper', array());
		$rootNode->addChildNode($dynamicNode);
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode("\n"));
		$dynamicNode = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_BaseViewHelper', array());
		$rootNode->addChildNode($dynamicNode);

		$expected = $rootNode;
		$actual = $this->templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals($expected, $actual, 'Fixture 02 was not parsed correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture02ShorthandSyntaxReturnsCorrectObjectTree() {
		$this->fixture02ReturnsCorrectObjectTree('/Fixtures/TemplateParserTestFixture02-shorthand.html');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture03ThrowsExceptionBecauseWrongTagNesting() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture03.html', FILE_TEXT);
		$this->templateParser->parse($templateSource);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture04ThrowsExceptionBecauseClosingATagWhichWasNeverOpened() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture04.html', FILE_TEXT);
		$this->templateParser->parse($templateSource);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture05ReturnsCorrectObjectTree() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture05.html', FILE_TEXT);

		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode("\na"));
		$dynamicNode = new Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode('posts.bla.Testing3');
		$rootNode->addChildNode($dynamicNode);
		$rootNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('b'));

		$expected = $rootNode;
		$actual = $this->templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals($expected, $actual, 'Fixture 05 was not parsed correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture06ReturnsCorrectObjectTree($file = '/Fixtures/TemplateParserTestFixture06.html') {
		$templateSource = file_get_contents(dirname(__FILE__) . $file, FILE_TEXT);

		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();

		$dynamicNode1 = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_Format_Nl2brViewHelper', array());
		$rootNode->addChildNode($dynamicNode1);

		$arguments = array(
			'decimals' => new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('1')
		);
		$dynamicNode2 = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_Format_NumberViewHelper', $arguments);
		$dynamicNode1->addChildNode($dynamicNode2);
		$dynamicNode2->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode('number'));

		$expected = $rootNode;
		$actual = $this->templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals($expected, $actual, 'Fixture 06 was not parsed correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture06InlineNotationReturnsCorrectObjectTree() {
		$this->fixture06ReturnsCorrectObjectTree('/Fixtures/TemplateParserTestFixture06-shorthand.html');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture07ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture07.html', FILE_TEXT);

		$templateVariableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array('id' => 1));

		$this->renderingContext->setTemplateVariableContainer($templateVariableContainer);

		$parsedTemplate = $this->templateParser->parse($templateSource);
		$result = $parsedTemplate->render($this->renderingContext);
		$expected = '1';
		$this->assertEquals($expected, $result, 'Fixture 07 was not parsed correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture08ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture08.html', FILE_TEXT);

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array('idList' => array(0, 1, 2, 3, 4, 5)));
		$this->renderingContext->setTemplateVariableContainer($variableContainer);

		$parsedTemplate = $this->templateParser->parse($templateSource);
		$result = $parsedTemplate->render($this->renderingContext);

		$expected = '0 1 2 3 4 5 ';
		$this->assertEquals($expected, $result, 'Fixture 08 was not rendered correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture09ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture09.html', FILE_TEXT);

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array('idList' => array(0, 1, 2, 3, 4, 5), 'variableName' => 3));
		$this->renderingContext->setTemplateVariableContainer($variableContainer);

		$parsedTemplate = $this->templateParser->parse($templateSource);
		$result = $parsedTemplate->render($this->renderingContext);

		$expected = '0 hallo test 3 4 ';
		$this->assertEquals($expected, $result, 'Fixture 09 was not rendered correctly. This is most likely due to problems in the array parser.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture10ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture10.html', FILE_TEXT);

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array('idList' => array(0, 1, 2, 3, 4, 5)));
		$this->renderingContext->setTemplateVariableContainer($variableContainer);

		$parsedTemplate = $this->templateParser->parse($templateSource);
		$result = $parsedTemplate->render($this->renderingContext);

		$expected = '0 1 2 3 4 5 ';
		$this->assertEquals($expected, $result, 'Fixture 10 was not rendered correctly. This has proboably something to do with line breaks inside tags.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture11ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture11.html', FILE_TEXT);

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());
		$this->renderingContext->setTemplateVariableContainer($variableContainer);

		$parsedTemplate = $this->templateParser->parse($templateSource);
		$result = $parsedTemplate->render($this->renderingContext);

		$expected = '0 2 4 ';
		$this->assertEquals($expected, $result, 'Fixture 11 was not rendered correctly.');
	}

	/**
	 * Test for CDATA support
	 *
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture12ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture12_cdata.html', FILE_TEXT);

		$variableContainer = new Tx_Fluid_Core_ViewHelper_TemplateVariableContainer(array());
		$this->renderingContext->setTemplateVariableContainer($variableContainer);

		$parsedTemplate = $this->templateParser->parse($templateSource);
		$result = $parsedTemplate->render($this->renderingContext);

		$expected = '<f3:for each="{a: {a: 0, b: 2, c: 4}}" as="array">' . chr(10) . '<f3:for each="{array}" as="value">{value} </f3:for>';
		$this->assertEquals($expected, $result, 'Fixture 12 was not rendered correctly. This hints at some problem with CDATA handling.');
	}

	/**
	 * Test for CDATA support
	 *
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function fixture13ReturnsCorrectlyRenderedResult() {
		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestFixture13_mandatoryInformation.html', FILE_TEXT);

		$templateTree = $this->templateParser->parse($templateSource)->getRootNode();
	}

	/**
	 *
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function postParseFacetIsCalledOnParse() {
		$templateParser = new Tx_Fluid_Core_Parser_TemplateParser();
		$templateParser->injectObjectFactory(new Tx_Fluid_Compatibility_ObjectFactory());

		$templateSource = file_get_contents(dirname(__FILE__) . '/Fixtures/TemplateParserTestPostParseFixture.html', FILE_TEXT);
		$templateTree = $templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals(Tx_Fluid_PostParseFacetViewHelper::$wasCalled, TRUE, 'PostParse was not called!');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function abortIfUnregisteredArgumentsExist() {
		$mockTemplateParser = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_Parser_TemplateParser'), array('dummy'), array(), '', FALSE);
		$expectedArguments = array(
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name1', 'string', 'desc', TRUE),
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name2', 'string', 'desc', TRUE)
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
	public function makeSureThatAbortIfUnregisteredArgumentsExistDoesNotThrowExceptionIfEverythingIsOk() {
		$mockTemplateParser = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_Parser_TemplateParser'), array('dummy'), array(), '', FALSE);
		$expectedArguments = array(
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name1', 'string', 'desc', TRUE),
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name2', 'string', 'desc', TRUE)
		);
		$actualArguments = array(
			'name1' => 'bla'
		);
		$mockTemplateParser->_call('abortIfUnregisteredArgumentsExist', $expectedArguments, $actualArguments);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function abortIfRequiredArgumentsAreMissingShouldThrowExceptionIfRequiredArgumentIsMissing() {
		$mockTemplateParser = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_Parser_TemplateParser'), array('dummy'), array(), '', FALSE);
		$expectedArguments = array(
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name1', 'string', 'desc', TRUE),
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name2', 'string', 'desc', FALSE)
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
	public function abortIfRequiredArgumentsAreMissingShouldNotThrowExceptionIfRequiredArgumentIsNotMissing() {
		$mockTemplateParser = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_Parser_TemplateParser'), array('dummy'), array(), '', FALSE);
		$expectedArguments = array(
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name1', 'string', 'desc', FALSE),
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name2', 'string', 'desc', FALSE)
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
	public function fixture14ReturnsCorrectObjectTree($file = '/Fixtures/TemplateParserTestFixture14.html') {
		$templateSource = file_get_contents(dirname(__FILE__) . $file, FILE_TEXT);

		$rootNode = new Tx_Fluid_Core_Parser_SyntaxTree_RootNode();
		$arguments = array(
			'arguments' => new Tx_Fluid_Core_Parser_SyntaxTree_RootNode(),
		);
		$arguments['arguments']->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_ArrayNode(array('number' => 362525200)));

		$dynamicNode = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode('Tx_Fluid_ViewHelpers_Format_PrintfViewHelper', $arguments);
		$rootNode->addChildNode($dynamicNode);
		$dynamicNode->addChildNode(new Tx_Fluid_Core_Parser_SyntaxTree_TextNode('%.3e'));

		$expected = $rootNode;
		$actual = $this->templateParser->parse($templateSource)->getRootNode();
		$this->assertEquals($expected, $actual, 'Fixture 14 was not parsed correctly.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function resolveViewHelperNameWorksWithMoreThanOneLevel() {
		$mockTemplateParser = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_Core_Parser_TemplateParser'), array('dummy'), array(), '', FALSE);
		$actual = $mockTemplateParser->_call('resolveViewHelperName', 'f', 'my.multi.level');
		$expected = 'Tx_Fluid_ViewHelpers_My_Multi_LevelViewHelper';
		$this->assertEquals($expected, $actual, 'View Helper resolving does not support multiple nesting levels.');
	}
}

?>