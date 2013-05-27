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

require_once(dirname(__FILE__) . '/Fixtures/PostParseFacetViewHelper.php');

/**
 * Testcase for TemplateParser.
 *
 * This is to at least half a system test, as it compares rendered results to
 * expectations, and does not strictly check the parsing...
 *
 * @version $Id: TemplateParserTest.php 3952 2010-03-16 08:00:53Z sebastian $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_Parser_TemplateParserTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.or>
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 */
	public function parseThrowsExceptionWhenStringArgumentMissing() {
		$templateParser = new Tx_Fluid_Core_Parser_TemplateParser();
		$templateParser->parse(123);
	}

	/**
	 * Checks that parse() calls the expected methods internally in the
	 * correct order:
	 * initialize();
	 * extractNamespaceDefinitions($templateString)
	 * splitTemplateAtDynamicTags($templateString)
	 * buildObjectTree($splitTemplate)
	 * 
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseDelegatesTasksAsExpected() {
		$templateParser = $this->getMock('Tx_Fluid_Core_Parser_TemplateParser', array('reset', 'extractNamespaceDefinitions', 'splitTemplateAtDynamicTags', 'buildObjectTree'));
		$templateParser->expects($this->at(0))->method('reset');
		$templateParser->expects($this->at(1))->method('extractNamespaceDefinitions')->with('templateString1')->will($this->returnValue('templateString2'));
		$templateParser->expects($this->at(2))->method('splitTemplateAtDynamicTags')->with('templateString2')->will($this->returnValue('templateString3'));
		$templateParser->expects($this->at(3))->method('buildObjectTree')->with('templateString3')->will($this->returnValue('templateString4'));
		$this->assertEquals('templateString4', $templateParser->parse('templateString1'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function extractNamespaceDefinitionsExtractsNamespacesCorrectly() {
		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));
		$templateParser->_call('extractNamespaceDefinitions', ' \{namespace f4=F7\Rocks} {namespace f4=Tx_Fluid_Really}');
		$expected = array(
			'f' => 'Tx_Fluid_ViewHelpers',
			'f4' => 'Tx_Fluid_Really'
		);
		$this->assertEquals($templateParser->getNamespaces(), $expected, 'Namespaces do not match.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 */
	public function extractNamespaceDefinitionsThrowsExceptionIfNamespaceIsRedeclared() {
		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));
		$templateParser->_call('extractNamespaceDefinitions', '{namespace f3=Tx_Fluid_Blablubb} {namespace f3= Tx_Fluid_Blu}');
	}


	/**
	 * @test
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function viewHelperNameWithMultipleLevelsCanBeResolvedByResolveViewHelperName() {
		$mockTemplateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'), array(), '', FALSE);
		$result = $mockTemplateParser->_call('resolveViewHelperName', 'f', 'foo.bar.baz');
		$expected = 'Tx_Fluid_ViewHelpers_Foo_Bar_BazViewHelper';
		$this->assertEquals($result, $expected, 'The name of the View Helper Name could not be resolved.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function viewHelperNameWithOneLevelCanBeResolvedByResolveViewHelperName() {
		$mockTemplateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'), array(), '', FALSE);
		$actual = $mockTemplateParser->_call('resolveViewHelperName', 'f', 'myown');
		$expected = 'Tx_Fluid_ViewHelpers_MyownViewHelper';
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function quotedStrings() {
		return array(
			array('"no quotes here"', 'no quotes here'),
			array("'no quotes here'", 'no quotes here'),
			array("'this \"string\" had \\'quotes\\' in it'", 'this "string" had \'quotes\' in it'),
			array('"this \\"string\\" had \'quotes\' in it"', 'this "string" had \'quotes\' in it'),
			array('"a weird \"string\" \'with\' *freaky* \\\\stuff', 'a weird "string" \'with\' *freaky* \\stuff'),
		);
	}

	/**
	 * @dataProvider quotedStrings
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unquoteStringReturnsUnquotedStrings($quoted, $unquoted) {
		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));
		$this->assertEquals($unquoted, $templateParser->_call('unquoteString', $quoted));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function templatesToSplit() {
		return array(
			array('TemplateParserTestFixture01-shorthand'),
			array('TemplateParserTestFixture06'),
			array('TemplateParserTestFixture14'),
		);
	}

	/**
	 * @dataProvider templatesToSplit
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function splitTemplateAtDynamicTagsReturnsCorrectlySplitTemplate($templateName) {
		$template = file_get_contents(dirname(__FILE__) . '/Fixtures/' . $templateName . '.html', FILE_TEXT);
		$result = require(dirname(__FILE__) . '/Fixtures/' . $templateName . '-split.php');
		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));
		$this->assertSame($result, $templateParser->_call('splitTemplateAtDynamicTags', $template));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildObjectTreeCreatesRootNodeAndSetsUpParsingState() {
		$mockRootNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_RootNode');

		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('setRootNode')->with($mockRootNode);
		$mockState->expects($this->once())->method('pushNodeToStack')->with($mockRootNode);
		$mockState->expects($this->once())->method('countNodeStack')->will($this->returnValue(1));

		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->at(0))->method('create')->with('Tx_Fluid_Core_Parser_ParsingState')->will($this->returnValue($mockState));
		$mockObjectManager->expects($this->at(1))->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_RootNode')->will($this->returnValue($mockRootNode));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));
		$templateParser->injectObjectManager($mockObjectManager);

		$templateParser->_call('buildObjectTree', array());
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildObjectTreeThrowsRootNodeIfOpentagsRemain() {
		$mockRootNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_RootNode');

		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('countNodeStack')->will($this->returnValue(2));

		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->at(0))->method('create')->with('Tx_Fluid_Core_Parser_ParsingState')->will($this->returnValue($mockState));
		$mockObjectManager->expects($this->at(1))->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_RootNode')->will($this->returnValue($mockRootNode));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));
		$templateParser->injectObjectManager($mockObjectManager);

		$templateParser->_call('buildObjectTree', array());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildObjectTreeDelegatesHandlingOfTemplateElements() {
		$mockRootNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_RootNode');
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('countNodeStack')->will($this->returnValue(1));
		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->at(0))->method('create')->with('Tx_Fluid_Core_Parser_ParsingState')->will($this->returnValue($mockState));
		$mockObjectManager->expects($this->at(1))->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_RootNode')->will($this->returnValue($mockRootNode));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('textHandler', 'openingViewHelperTagHandler', 'closingViewHelperTagHandler', 'textAndShorthandSyntaxHandler'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->expects($this->at(0))->method('textAndShorthandSyntaxHandler')->with($mockState, 'The first part is simple');
		$templateParser->expects($this->at(1))->method('textHandler')->with($mockState, '<f3:for each="{a: {a: 0, b: 2, c: 4}}" as="array"><f3:for each="{array}" as="value">{value} </f3:for>');
		$templateParser->expects($this->at(2))->method('openingViewHelperTagHandler')->with($mockState, 'f', 'format.printf', ' arguments="{number : 362525200}"', FALSE);
		$templateParser->expects($this->at(3))->method('textAndShorthandSyntaxHandler')->with($mockState, '%.3e');
		$templateParser->expects($this->at(4))->method('closingViewHelperTagHandler')->with($mockState, 'f', 'format.printf');
		$templateParser->expects($this->at(5))->method('textAndShorthandSyntaxHandler')->with($mockState, 'and here goes some {text} that could have {shorthand}');

		$splitTemplate = $templateParser->_call('splitTemplateAtDynamicTags', 'The first part is simple<![CDATA[<f3:for each="{a: {a: 0, b: 2, c: 4}}" as="array"><f3:for each="{array}" as="value">{value} </f3:for>]]><f:format.printf arguments="{number : 362525200}">%.3e</f:format.printf>and here goes some {text} that could have {shorthand}');

		$templateParser->_call('buildObjectTree', $splitTemplate);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function openingViewHelperTagHandlerDelegatesViewHelperInitialization() {
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->never())->method('popNodeFromStack');

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('parseArguments', 'initializeViewHelperAndAddItToStack'));
		$templateParser->expects($this->once())->method('parseArguments')->with(array('arguments'))->will($this->returnValue(array('parsedArguments')));
		$templateParser->expects($this->once())->method('initializeViewHelperAndAddItToStack')->with($mockState, 'namespaceIdentifier', 'methodIdentifier', array('parsedArguments'));

		$templateParser->_call('openingViewHelperTagHandler', $mockState, 'namespaceIdentifier', 'methodIdentifier', array('arguments'), FALSE);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function openingViewHelperTagHandlerPopsNodeFromStackForSelfClosingTags() {
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('popNodeFromStack')->will($this->returnValue($this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface')));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('parseArguments', 'initializeViewHelperAndAddItToStack'));

		$templateParser->_call('openingViewHelperTagHandler', $mockState, '', '', array(), TRUE);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeViewHelperAndAddItToStackCreatesRequestedViewHelperAndViewHelperNode() {
		$mockViewHelper = $this->getMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper');
		$mockViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array(), array(), '', FALSE);

		$mockNodeOnStack = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface');
		$mockNodeOnStack->expects($this->once())->method('addChildNode')->with($mockViewHelperNode);

		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->at(0))->method('create')->with('Tx_Fluid_ViewHelpers_MyownViewHelper')->will($this->returnValue($mockViewHelper));
		$mockObjectManager->expects($this->at(1))->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode')->will($this->returnValue($mockViewHelperNode));

		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));
		$mockState->expects($this->once())->method('pushNodeToStack')->with($mockViewHelperNode);

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('abortIfUnregisteredArgumentsExist', 'abortIfRequiredArgumentsAreMissing'));
		$templateParser->injectObjectManager($mockObjectManager);

		$templateParser->_call('initializeViewHelperAndAddItToStack', $mockState, 'f', 'myown', array('arguments'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeViewHelperAndAddItToStackChecksViewHelperArguments() {
		$expectedArguments = array('expectedArguments');
		$argumentsObjectTree = array('arguments');

		$mockViewHelper = $this->getMock('Tx_Fluid_Core_ViewHelper_AbstractViewHelper');
		$mockViewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue($expectedArguments));
		$mockViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array(), array(), '', FALSE);

		$mockNodeOnStack = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface');

		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->at(0))->method('create')->with('Tx_Fluid_ViewHelpers_MyownViewHelper')->will($this->returnValue($mockViewHelper));
		$mockObjectManager->expects($this->at(1))->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode')->will($this->returnValue($mockViewHelperNode));

		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('abortIfUnregisteredArgumentsExist', 'abortIfRequiredArgumentsAreMissing'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->expects($this->once())->method('abortIfUnregisteredArgumentsExist')->with($expectedArguments, $argumentsObjectTree);
		$templateParser->expects($this->once())->method('abortIfRequiredArgumentsAreMissing')->with($expectedArguments, $argumentsObjectTree);

		$templateParser->_call('initializeViewHelperAndAddItToStack', $mockState, 'f', 'myown', $argumentsObjectTree);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeViewHelperAndAddItToStackhandlesPostParseFacets() {
		$mockViewHelper = $this->getMock('Tx_Fluid_Core_Parser_Fixtures_PostParseFacetViewHelper');
		$mockViewHelperNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array(), array(), '', FALSE);

		$mockNodeOnStack = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface');
		$mockNodeOnStack->expects($this->once())->method('addChildNode')->with($mockViewHelperNode);

		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->at(0))->method('create')->with('Tx_Fluid_ViewHelpers_MyownViewHelper')->will($this->returnValue($mockViewHelper));
		$mockObjectManager->expects($this->at(1))->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode')->will($this->returnValue($mockViewHelperNode));

		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));
		$mockState->expects($this->once())->method('getVariableContainer')->will($this->returnValue($this->getMock('Tx_Fluid_Core_ViewHelper_TemplateVariableContainer')));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('abortIfUnregisteredArgumentsExist', 'abortIfRequiredArgumentsAreMissing'));
		$templateParser->injectObjectManager($mockObjectManager);

		$templateParser->_call('initializeViewHelperAndAddItToStack', $mockState, 'f', 'myown', array('arguments'));
		$this->assertTrue(Tx_Fluid_Core_Parser_Fixtures_PostParseFacetViewHelper::$wasCalled, 'PostParse was not called!');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function abortIfUnregisteredArgumentsExistThrowsExceptionOnUnregisteredArguments() {
		$expected = array(new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('firstArgument', 'string', '', FALSE));
		$actual = array('firstArgument' => 'foo', 'secondArgument' => 'bar');

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));

		$templateParser->_call('abortIfUnregisteredArgumentsExist', $expected, $actual);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function abortIfUnregisteredArgumentsExistDoesNotThrowExceptionIfEverythingIsOk() {
		$expectedArguments = array(
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name1', 'string', 'desc', FALSE),
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name2', 'string', 'desc', TRUE)
		);
		$actualArguments = array(
			'name1' => 'bla'
		);

		$mockTemplateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));

		$mockTemplateParser->_call('abortIfUnregisteredArgumentsExist', $expectedArguments, $actualArguments);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function abortIfRequiredArgumentsAreMissingThrowsException() {
		$expected = array(
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('firstArgument', 'string', '', FALSE),
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('secondArgument', 'string', '', TRUE));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));

		$templateParser->_call('abortIfRequiredArgumentsAreMissing', $expected, array());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function abortIfRequiredArgumentsAreMissingDoesNotThrowExceptionIfRequiredArgumentExists() {
		$expectedArguments = array(
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name1', 'string', 'desc', FALSE),
			new Tx_Fluid_Core_ViewHelper_ArgumentDefinition('name2', 'string', 'desc', TRUE)
		);
		$actualArguments = array(
			'name2' => 'bla'
		);

		$mockTemplateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));

		$mockTemplateParser->_call('abortIfRequiredArgumentsAreMissing', $expectedArguments, $actualArguments);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function closingViewHelperTagHandlerThrowsExceptionBecauseOfClosingTagWhichWasNeverOpened() {
		$mockNodeOnStack = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface', array(), array(), '', FALSE);
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('popNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));

		$templateParser->_call('closingViewHelperTagHandler', $mockState, 'f', 'method');
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_Parser_Exception
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function closingViewHelperTagHandlerThrowsExceptionBecauseOfWrongTagNesting () {
		$mockNodeOnStack = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode', array(), array(), '', FALSE);
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('popNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));

		$templateParser->_call('closingViewHelperTagHandler', $mockState, 'f', 'method');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function objectAccessorHandlerCallsInitializeViewHelperAndAddItToStackIfViewHelperSyntaxIsPresent() {
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->exactly(2))->method('popNodeFromStack')->will($this->returnValue($this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_NodeInterface')));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('recursiveArrayHandler', 'postProcessArgumentsForObjectAccessor', 'initializeViewHelperAndAddItToStack'));
		$templateParser->expects($this->at(0))->method('recursiveArrayHandler')->with('format: "H:i"')->will($this->returnValue(array('format' => 'H:i')));
		$templateParser->expects($this->at(1))->method('postProcessArgumentsForObjectAccessor')->with(array('format' => 'H:i'))->will($this->returnValue(array('processedArguments')));
		$templateParser->expects($this->at(2))->method('initializeViewHelperAndAddItToStack')->with($mockState, 'f', 'format.date', array('processedArguments'));
		$templateParser->expects($this->at(3))->method('initializeViewHelperAndAddItToStack')->with($mockState, 'f', 'base', array());

		$templateParser->_call('objectAccessorHandler', $mockState, '', '', 'f:base() f:format.date(format: "H:i")', '');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function objectAccessorHandlerCreatesObjectAccessorNodeWithExpectedValueAndAddsItToStack() {
		$objectAccessorNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array(), array(), '', FALSE);

		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->once())->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', 'objectAccessorString')->will($this->returnValue($objectAccessorNode));

		$mockNodeOnStack = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode', array(), array(), '', FALSE);
		$mockNodeOnStack->expects($this->once())->method('addChildNode')->with($objectAccessorNode);
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));
		$templateParser->injectObjectManager($mockObjectManager);

		$templateParser->_call('objectAccessorHandler', $mockState, 'objectAccessorString', '', '', '');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function valuesFromObjectAccessorsAreRunThroughValueInterceptorsByDefault() {
		$objectAccessorNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode', array(), array(), '', FALSE);
		$objectAccessorNodeInterceptor = $this->getMock('Tx_Fluid_Core_Parser_InterceptorInterface');
		$objectAccessorNodeInterceptor->expects($this->once())->method('process')->with($objectAccessorNode)->will($this->returnArgument(0));

		$parserConfiguration = $this->getMock('Tx_Fluid_Core_Parser_Configuration');
		$parserConfiguration->expects($this->once())->method('getInterceptors')->with(Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_OBJECTACCESSOR)->will($this->returnValue(array($objectAccessorNodeInterceptor)));

		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->once())->method('create')->will($this->returnValue($objectAccessorNode));

		$mockNodeOnStack = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode', array(), array(), '', FALSE);
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->_set('configuration', $parserConfiguration);

		$templateParser->_call('objectAccessorHandler', $mockState, 'objectAccessorString', '', '', '');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function viewHelperArgumentsAreNotRunThroughValueInterceptors() {
		$this->markTestIncomplete('Needs implementation!');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function argumentsStrings() {
		return array(
			array('a="2"', array('a' => '2')),
			array('a="2" b="foobar \' with \\" quotes"', array('a' => '2', 'b' => 'foobar \' with " quotes')),
			array(' arguments="{number : 362525200}"', array('arguments' => '{number : 362525200}')),
		);
	}

	/**
	 * @test
	 * @dataProvider argumentsStrings
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseArgumentsWorksAsExpected($argumentsString, $expected) {
		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('buildArgumentObjectTree'));
		$templateParser->expects($this->any())->method('buildArgumentObjectTree')->will($this->returnArgument(0));

		$this->assertSame($expected, $templateParser->_call('parseArguments', $argumentsString));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildArgumentObjectTreeReturnsTextNodeForSimplyString() {
		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->once())->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_TextNode', 'a very plain string')->will($this->returnValue('theTextNode'));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('dummy'));
		$templateParser->injectObjectManager($mockObjectManager);

		$this->assertEquals('theTextNode', $templateParser->_call('buildArgumentObjectTree', 'a very plain string'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function buildArgumentObjectTreeBuildsObjectTreeForComlexString() {
		$objectTree = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$objectTree->expects($this->once())->method('getRootNode')->will($this->returnValue('theRootNode'));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('splitTemplateAtDynamicTags', 'buildObjectTree'));
		$templateParser->expects($this->at(0))->method('splitTemplateAtDynamicTags')->with('a <very> {complex} string')->will($this->returnValue('split string'));
		$templateParser->expects($this->at(1))->method('buildObjectTree')->with('split string')->will($this->returnValue($objectTree));

		$this->assertEquals('theRootNode', $templateParser->_call('buildArgumentObjectTree', 'a <very> {complex} string'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function textAndShorthandSyntaxHandlerDelegatesAppropriately() {
		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->any())->method('create')->will($this->returnArgument(1));
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('objectAccessorHandler', 'arrayHandler', 'textHandler'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->expects($this->at(0))->method('objectAccessorHandler')->with($mockState, 'someThing.absolutely', '', '', '');
		$templateParser->expects($this->at(1))->method('textHandler')->with($mockState, ' "fishy" is \'going\' ');
		$templateParser->expects($this->at(2))->method('arrayHandler')->with($mockState, 'on: "here"');

		$text = '{someThing.absolutely} "fishy" is \'going\' {on: "here"}';
		$templateParser->_call('textAndShorthandSyntaxHandler', $mockState, $text);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function arrayHandlerAddsArrayNodeWithProperContentToStack() {
		$arrayNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_ArrayNode', array(), array(array()));
		$mockNodeOnStack = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode', array(), array(), '', FALSE);
		$mockNodeOnStack->expects($this->once())->method('addChildNode')->with($arrayNode);
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->once())->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_ArrayNode', 'processedArrayText')->will($this->returnValue($arrayNode));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('recursiveArrayHandler'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->expects($this->once())->method('recursiveArrayHandler')->with('arrayText')->will($this->returnValue('processedArrayText'));

		$templateParser->_call('arrayHandler', $mockState, 'arrayText');
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function arrayTexts() {
		return array(
			array(
				'key1: "foo", key2: \'bar\', key3: someVar, key4: 123, key5: { key6: "baz" }',
				array('key1' => 'foo', 'key2' => 'bar', 'key3' => 'someVar', 'key4' => 123.0, 'key5' => array('key6' => 'baz'),
			)),
		);
	}

	/**
	 * @test
	 * @dataProvider arrayTexts
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function recursiveArrayHandlerReturnsExpectedArray($arrayText, $expectedArray) {
		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->any())->method('create')->will($this->returnArgument(1));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('buildArgumentObjectTree'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->expects($this->any())->method('buildArgumentObjectTree')->will($this->returnArgument(0));

		$this->assertSame($expectedArray, $templateParser->_call('recursiveArrayHandler', $arrayText));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function textNodesAreRunThroughTextInterceptors() {
		$textNode = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_TextNode', array(), array(), '', FALSE);
		$textInterceptor = $this->getMock('Tx_Fluid_Core_Parser_InterceptorInterface');
		$textInterceptor->expects($this->once())->method('process')->with($textNode)->will($this->returnArgument(0));

		$parserConfiguration = $this->getMock('Tx_Fluid_Core_Parser_Configuration');
		$parserConfiguration->expects($this->once())->method('getInterceptors')->with(Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_TEXT)->will($this->returnValue(array($textInterceptor)));

		$mockObjectManager = $this->getMock('Tx_Fluid_Compatibility_ObjectManager');
		$mockObjectManager->expects($this->once())->method('create')->with('Tx_Fluid_Core_Parser_SyntaxTree_TextNode', 'string')->will($this->returnValue($textNode));

		$mockNodeOnStack = $this->getMock('Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode', array(), array(), '', FALSE);
		$mockNodeOnStack->expects($this->once())->method('addChildNode')->with($textNode);
		$mockState = $this->getMock('Tx_Fluid_Core_Parser_ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('Tx_Fluid_Core_Parser_TemplateParser', array('splitTemplateAtDynamicTags', 'buildObjectTree'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->_set('configuration', $parserConfiguration);

		$templateParser->_call('textHandler', $mockState, 'string');
	}

}

?>