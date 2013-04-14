<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Parser;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(__DIR__ . '/Fixtures/PostParseFacetViewHelper.php');

/**
 * Testcase for TemplateParser.
 *
 * This is to at least half a system test, as it compares rendered results to
 * expectations, and does not strictly check the parsing...
 */
class TemplateParserTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function parseThrowsExceptionWhenStringArgumentMissing() {
		$templateParser = new \TYPO3\CMS\Fluid\Core\Parser\TemplateParser();
		$templateParser->parse(123);
	}

	/**
	 * @test
	 */
	public function extractNamespaceDefinitionsExtractsNamespacesCorrectly() {
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->_call('extractNamespaceDefinitions', ' \\{namespace f4=F7\\Rocks} {namespace f4=TYPO3\Rocks\Really}');
		$expected = array(
			'f' => 'TYPO3\CMS\Fluid\ViewHelpers',
			'f4' => 'TYPO3\Rocks\Really'
		);
		$this->assertEquals($expected, $templateParser->getNamespaces(), 'Namespaces do not match.');
	}

	/**
	 * @test
	 */
	public function extractNamespaceDefinitionsExtractsXmlNamespacesCorrectly() {
		$mockSettings = array(
			'namespaces' => array(
				'http://domain.tld/ns/my/viewhelpers' => 'My\Namespace',
				'http://otherdomain.tld/ns/other/viewhelpers' => 'My\Other\Namespace'
			)
		);
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->injectSettings($mockSettings);
		$templateParser->_call('extractNamespaceDefinitions', 'Some content <html xmlns="http://www.w3.org/1999/xhtml" xmlns:f5="http://domain.tld/ns/my/viewhelpers"
		xmlns:xyz="http://otherdomain.tld/ns/other/viewhelpers" />');
		$expected = array(
			'f' => 'TYPO3\CMS\Fluid\ViewHelpers',
			'f5' => 'My\Namespace',
			'xyz' => 'My\Other\Namespace'
		);
		$this->assertEquals($expected, $templateParser->getNamespaces(), 'Namespaces do not match.');
	}

	/**
	 * @test
	 */
	public function extractNamespaceDefinitionsResolveNamespacesWithDefaultPattern() {
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->_call('extractNamespaceDefinitions', '<xml xmlns="http://www.w3.org/1999/xhtml" xmlns:xyz="http://typo3.org/ns/Some/Package/ViewHelpers" />');
		$expected = array(
			'f' => 'TYPO3\CMS\Fluid\ViewHelpers',
			'xyz' => 'Some\Package\ViewHelpers'
		);
		$this->assertEquals($expected, $templateParser->getNamespaces(), 'Namespaces do not match.');
	}

	/**
	 * @test
	 */
	public function extractNamespaceDefinitionsSilentlySkipsXmlNamespaceDeclarationsThatCantBeResolved() {
		$mockSettings = array(
			'namespaces' => array(
				'http://domain.tld/ns/my/viewhelpers' => 'My\Namespace'
			)
		);
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->injectSettings($mockSettings);
		$templateParser->_call('extractNamespaceDefinitions', '<xml xmlns="http://www.w3.org/1999/xhtml" xmlns:f5="http://domain.tld/ns/my/viewhelpers"
		xmlns:xyz="http://otherdomain.tld/ns/other/viewhelpers" />');
		$expected = array(
			'f' => 'TYPO3\CMS\Fluid\ViewHelpers',
			'f5' => 'My\Namespace'
		);
		$this->assertEquals($expected, $templateParser->getNamespaces(), 'Namespaces do not match.');
	}

	/**
	 * @test
	 */
	public function extractNamespaceDefinitionsSilentlySkipsXmlNamespaceDeclarationForTheDefaultFluidNamespace() {
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->_call('extractNamespaceDefinitions', '<foo xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://domain.tld/this/will/be/ignored" />');
		$expected = array(
			'f' => 'TYPO3\CMS\Fluid\ViewHelpers'
		);
		$this->assertEquals($expected, $templateParser->getNamespaces(), 'Namespaces do not match.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function extractNamespaceDefinitionsThrowsExceptionIfNamespaceIsRedeclared() {
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->_call('extractNamespaceDefinitions', '{namespace typo3=TYPO3\CMS\Fluid\Blablubb} {namespace typo3= TYPO3\Rocks\Blu}');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function extractNamespaceDefinitionsThrowsExceptionIfXmlNamespaceIsRedeclaredAsFluidNamespace() {
		$mockSettings = array(
			'namespaces' => array(
				'http://domain.tld/ns/my/viewhelpers' => 'My\Namespace'
			)
		);
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->injectSettings($mockSettings);
		$templateParser->_call('extractNamespaceDefinitions', '<foo xmlns="http://www.w3.org/1999/xhtml" xmlns:typo3="http://domain.tld/ns/my/viewhelpers" />{namespace typo3=TYPO3\CMS\Fluid\Blablubb}');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function extractNamespaceDefinitionsThrowsExceptionIfFluidNamespaceIsRedeclaredAsXmlNamespace() {
		$mockSettings = array(
			'namespaces' => array(
				'http://domain.tld/ns/my/viewhelpers' => 'My\Namespace'
			)
		);
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->injectSettings($mockSettings);
		$templateParser->_call('extractNamespaceDefinitions', '{namespace typo3=TYPO3\CMS\Fluid\Blablubb} <foo xmlns="http://www.w3.org/1999/xhtml" xmlns:typo3="http://domain.tld/ns/my/viewhelpers" />');
	}

	/**
	 * @test
	 */
	public function viewHelperNameWithMultipleLevelsCanBeResolvedByResolveViewHelperName() {
		$mockTemplateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'), array(), '', FALSE);
		$result = $mockTemplateParser->_call('resolveViewHelperName', 'f', 'foo.bar.baz');
		$expected = 'TYPO3\CMS\Fluid\ViewHelpers\Foo\Bar\BazViewHelper';
		$this->assertEquals($expected, $result, 'The name of the View Helper Name could not be resolved.');
	}

	/**
	 * @test
	 */
	public function viewHelperNameWithOneLevelCanBeResolvedByResolveViewHelperName() {
		$mockTemplateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'), array(), '', FALSE);
		$actual = $mockTemplateParser->_call('resolveViewHelperName', 'f', 'myown');
		$expected = 'TYPO3\CMS\Fluid\ViewHelpers\MyownViewHelper';
		$this->assertEquals($expected, $actual);
	}

	/**
	 */
	public function quotedStrings() {
		return array(
			array('"no quotes here"', 'no quotes here'),
			array("'no quotes here'", 'no quotes here'),
			array("'this \"string\" had \\'quotes\\' in it'", 'this "string" had \'quotes\' in it'),
			array('"this \\"string\\" had \'quotes\' in it"', 'this "string" had \'quotes\' in it'),
			array('"a weird \"string\" \'with\' *freaky* \\\\stuff', 'a weird "string" \'with\' *freaky* \\stuff'),
			array('\'\\\'escaped quoted string in string\\\'\'', '\'escaped quoted string in string\'')
		);
	}

	/**
	 * @dataProvider quotedStrings
	 * @test
	 */
	public function unquoteStringReturnsUnquotedStrings($quoted, $unquoted) {
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$this->assertEquals($unquoted, $templateParser->_call('unquoteString', $quoted));
	}

	/**
	 */
	public function templatesToSplit() {
		return array(
			array('TemplateParserTestFixture01-shorthand'),
			array('TemplateParserTestFixture06'),
			array('TemplateParserTestFixture14')
		);
	}

	/**
	 * @dataProvider templatesToSplit
	 * @test
	 */
	public function splitTemplateAtDynamicTagsReturnsCorrectlySplitTemplate($templateName) {
		$template = file_get_contents(__DIR__ . '/Fixtures/' . $templateName . '.html', FILE_TEXT);
		$expectedResult = require(__DIR__ . '/Fixtures/' . $templateName . '-split.php');
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$this->assertSame($expectedResult, $templateParser->_call('splitTemplateAtDynamicTags', $template), 'Filed for ' . $templateName);
	}

	/**
	 * @test
	 */
	public function buildObjectTreeCreatesRootNodeAndSetsUpParsingState() {
		$mockRootNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RootNode');

		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('setRootNode')->with($mockRootNode);
		$mockState->expects($this->once())->method('pushNodeToStack')->with($mockRootNode);
		$mockState->expects($this->once())->method('countNodeStack')->will($this->returnValue(1));

		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState')->will($this->returnValue($mockState));
		$mockObjectManager->expects($this->at(1))->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RootNode')->will($this->returnValue($mockRootNode));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->_call('buildObjectTree', array(), \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::CONTEXT_OUTSIDE_VIEWHELPER_ARGUMENTS);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function buildObjectTreeThrowsExceptionIfOpenTagsRemain() {
		$mockRootNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RootNode');

		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('countNodeStack')->will($this->returnValue(2));

		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState')->will($this->returnValue($mockState));
		$mockObjectManager->expects($this->at(1))->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RootNode')->will($this->returnValue($mockRootNode));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->_call('buildObjectTree', array(), \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::CONTEXT_OUTSIDE_VIEWHELPER_ARGUMENTS);
	}

	/**
	 * @test
	 */
	public function buildObjectTreeDelegatesHandlingOfTemplateElements() {
		$mockRootNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RootNode');
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('countNodeStack')->will($this->returnValue(1));
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState')->will($this->returnValue($mockState));
		$mockObjectManager->expects($this->at(1))->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\RootNode')->will($this->returnValue($mockRootNode));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('textHandler', 'openingViewHelperTagHandler', 'closingViewHelperTagHandler', 'textAndShorthandSyntaxHandler'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->expects($this->at(0))->method('textAndShorthandSyntaxHandler')->with($mockState, 'The first part is simple');
		$templateParser->expects($this->at(1))->method('textHandler')->with($mockState, '<f:for each="{a: {a: 0, b: 2, c: 4}}" as="array"><f:for each="{array}" as="value">{value} </f:for>');
		$templateParser->expects($this->at(2))->method('openingViewHelperTagHandler')->with($mockState, 'f', 'format.printf', ' arguments="{number : 362525200}"', FALSE);
		$templateParser->expects($this->at(3))->method('textAndShorthandSyntaxHandler')->with($mockState, '%.3e');
		$templateParser->expects($this->at(4))->method('closingViewHelperTagHandler')->with($mockState, 'f', 'format.printf');
		$templateParser->expects($this->at(5))->method('textAndShorthandSyntaxHandler')->with($mockState, 'and here goes some {text} that could have {shorthand}');

		$splitTemplate = $templateParser->_call('splitTemplateAtDynamicTags', 'The first part is simple<![CDATA[<f:for each="{a: {a: 0, b: 2, c: 4}}" as="array"><f:for each="{array}" as="value">{value} </f:for>]]><f:format.printf arguments="{number : 362525200}">%.3e</f:format.printf>and here goes some {text} that could have {shorthand}');
		$templateParser->_call('buildObjectTree', $splitTemplate, \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::CONTEXT_OUTSIDE_VIEWHELPER_ARGUMENTS);
	}

	/**
	 * @test
	 */
	public function openingViewHelperTagHandlerDelegatesViewHelperInitialization() {
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->never())->method('popNodeFromStack');

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('parseArguments', 'initializeViewHelperAndAddItToStack'));
		$templateParser->expects($this->once())->method('parseArguments')->with(array('arguments'))->will($this->returnValue(array('parsedArguments')));
		$templateParser->expects($this->once())->method('initializeViewHelperAndAddItToStack')->with($mockState, 'namespaceIdentifier', 'methodIdentifier', array('parsedArguments'));

		$templateParser->_call('openingViewHelperTagHandler', $mockState, 'namespaceIdentifier', 'methodIdentifier', array('arguments'), FALSE);
	}

	/**
	 * @test
	 */
	public function openingViewHelperTagHandlerPopsNodeFromStackForSelfClosingTags() {
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('popNodeFromStack')->will($this->returnValue($this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\NodeInterface')));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('parseArguments', 'initializeViewHelperAndAddItToStack'));

		$templateParser->_call('openingViewHelperTagHandler', $mockState, '', '', array(), TRUE);
	}

	/**
	 * @test
	 */
	public function initializeViewHelperAndAddItToStackCreatesRequestedViewHelperAndViewHelperNode() {
		$mockViewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractViewHelper');
		$mockViewHelperNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode', array(), array(), '', FALSE);

		$mockNodeOnStack = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\NodeInterface');
		$mockNodeOnStack->expects($this->once())->method('addChildNode')->with($mockViewHelperNode);

		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\MyownViewHelper')->will($this->returnValue($mockViewHelper));
		$mockObjectManager->expects($this->at(1))->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode')->will($this->returnValue($mockViewHelperNode));

		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));
		$mockState->expects($this->once())->method('pushNodeToStack')->with($mockViewHelperNode);

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('abortIfUnregisteredArgumentsExist', 'abortIfRequiredArgumentsAreMissing', 'rewriteBooleanNodesInArgumentsObjectTree'));
		$templateParser->injectObjectManager($mockObjectManager);

		$templateParser->_call('initializeViewHelperAndAddItToStack', $mockState, 'f', 'myown', array('arguments'));
	}

	/**
	 * @test
	 */
	public function initializeViewHelperAndAddItToStackChecksViewHelperArguments() {
		$expectedArguments = array('expectedArguments');
		$argumentsObjectTree = array('arguments');

		$mockViewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\AbstractViewHelper');
		$mockViewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue($expectedArguments));
		$mockViewHelperNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode', array(), array(), '', FALSE);

		$mockNodeOnStack = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\NodeInterface');

		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\MyownViewHelper')->will($this->returnValue($mockViewHelper));
		$mockObjectManager->expects($this->at(1))->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode')->will($this->returnValue($mockViewHelperNode));

		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('abortIfUnregisteredArgumentsExist', 'abortIfRequiredArgumentsAreMissing', 'rewriteBooleanNodesInArgumentsObjectTree'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->expects($this->once())->method('abortIfUnregisteredArgumentsExist')->with($expectedArguments, $argumentsObjectTree);
		$templateParser->expects($this->once())->method('abortIfRequiredArgumentsAreMissing')->with($expectedArguments, $argumentsObjectTree);

		$templateParser->_call('initializeViewHelperAndAddItToStack', $mockState, 'f', 'myown', $argumentsObjectTree);
	}

	/**
	 * @test
	 */
	public function initializeViewHelperAndAddItToStackHandlesPostParseFacets() {
		$mockViewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\Fixtures\\PostParseFacetViewHelper', array('prepareArguments'));
		$mockViewHelperNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode', array(), array(), '', FALSE);

		$mockNodeOnStack = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\NodeInterface');
		$mockNodeOnStack->expects($this->once())->method('addChildNode')->with($mockViewHelperNode);

		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\MyownViewHelper')->will($this->returnValue($mockViewHelper));
		$mockObjectManager->expects($this->at(1))->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode')->will($this->returnValue($mockViewHelperNode));

		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));
		$mockState->expects($this->once())->method('getVariableContainer')->will($this->returnValue($this->getMock('TYPO3\\CMS\\Fluid\\Core\\ViewHelper\\TemplateVariableContainer')));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('abortIfUnregisteredArgumentsExist', 'abortIfRequiredArgumentsAreMissing', 'rewriteBooleanNodesInArgumentsObjectTree'));
		$templateParser->injectObjectManager($mockObjectManager);

		$templateParser->_call('initializeViewHelperAndAddItToStack', $mockState, 'f', 'myown', array('arguments'));
		$this->assertTrue(\TYPO3\CMS\Fluid\Core\Parser\Fixtures\PostParseFacetViewHelper::$wasCalled, 'PostParse was not called!');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function abortIfUnregisteredArgumentsExistThrowsExceptionOnUnregisteredArguments() {
		$expected = array(new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('firstArgument', 'string', '', FALSE));
		$actual = array('firstArgument' => 'foo', 'secondArgument' => 'bar');

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));

		$templateParser->_call('abortIfUnregisteredArgumentsExist', $expected, $actual);
	}

	/**
	 * @test
	 */
	public function abortIfUnregisteredArgumentsExistDoesNotThrowExceptionIfEverythingIsOk() {
		$expectedArguments = array(
			new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('name1', 'string', 'desc', FALSE),
			new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('name2', 'string', 'desc', TRUE)
		);
		$actualArguments = array(
			'name1' => 'bla'
		);

		$mockTemplateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));

		$mockTemplateParser->_call('abortIfUnregisteredArgumentsExist', $expectedArguments, $actualArguments);
		// dummy assertion to avoid "did not perform any assertions" error
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function abortIfRequiredArgumentsAreMissingThrowsException() {
		$expected = array(
			new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('firstArgument', 'string', '', FALSE),
			new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('secondArgument', 'string', '', TRUE)
		);

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));

		$templateParser->_call('abortIfRequiredArgumentsAreMissing', $expected, array());
	}

	/**
	 * @test
	 */
	public function abortIfRequiredArgumentsAreMissingDoesNotThrowExceptionIfRequiredArgumentExists() {
		$expectedArguments = array(
			new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('name1', 'string', 'desc', FALSE),
			new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('name2', 'string', 'desc', TRUE)
		);
		$actualArguments = array(
			'name2' => 'bla'
		);

		$mockTemplateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));

		$mockTemplateParser->_call('abortIfRequiredArgumentsAreMissing', $expectedArguments, $actualArguments);
		// dummy assertion to avoid "did not perform any assertions" error
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function closingViewHelperTagHandlerThrowsExceptionBecauseOfClosingTagWhichWasNeverOpened() {
		$mockNodeOnStack = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\NodeInterface', array(), array(), '', FALSE);
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('popNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));

		$templateParser->_call('closingViewHelperTagHandler', $mockState, 'f', 'method');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
	 */
	public function closingViewHelperTagHandlerThrowsExceptionBecauseOfWrongTagNesting() {
		$mockNodeOnStack = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode', array(), array(), '', FALSE);
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('popNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));

		$templateParser->_call('closingViewHelperTagHandler', $mockState, 'f', 'method');
	}

	/**
	 * @test
	 */
	public function objectAccessorHandlerCallsInitializeViewHelperAndAddItToStackIfViewHelperSyntaxIsPresent() {
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->exactly(2))->method('popNodeFromStack')->will($this->returnValue($this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\NodeInterface')));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('recursiveArrayHandler', 'postProcessArgumentsForObjectAccessor', 'initializeViewHelperAndAddItToStack'));
		$templateParser->expects($this->at(0))->method('recursiveArrayHandler')->with('format: "H:i"')->will($this->returnValue(array('format' => 'H:i')));
		$templateParser->expects($this->at(1))->method('postProcessArgumentsForObjectAccessor')->with(array('format' => 'H:i'))->will($this->returnValue(array('processedArguments')));
		$templateParser->expects($this->at(2))->method('initializeViewHelperAndAddItToStack')->with($mockState, 'f', 'format.date', array('processedArguments'));
		$templateParser->expects($this->at(3))->method('initializeViewHelperAndAddItToStack')->with($mockState, 'f', 'base', array());

		$templateParser->_call('objectAccessorHandler', $mockState, '', '', 'f:base() f:format.date(format: "H:i")', '');
	}

	/**
	 * @test
	 */
	public function objectAccessorHandlerCreatesObjectAccessorNodeWithExpectedValueAndAddsItToStack() {
		$objectAccessorNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ObjectAccessorNode', array(), array(), '', FALSE);

		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ObjectAccessorNode', 'objectAccessorString')->will($this->returnValue($objectAccessorNode));

		$mockNodeOnStack = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\AbstractNode', array(), array(), '', FALSE);
		$mockNodeOnStack->expects($this->once())->method('addChildNode')->with($objectAccessorNode);
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->injectObjectManager($mockObjectManager);

		$templateParser->_call('objectAccessorHandler', $mockState, 'objectAccessorString', '', '', '');
	}

	/**
	 * @test
	 */
	public function valuesFromObjectAccessorsAreRunThroughValueInterceptorsByDefault() {
		$objectAccessorNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ObjectAccessorNode', array(), array(), '', FALSE);
		$objectAccessorNodeInterceptor = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\InterceptorInterface');
		$objectAccessorNodeInterceptor->expects($this->once())->method('process')->with($objectAccessorNode)->will($this->returnArgument(0));

		$parserConfiguration = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\Configuration');
		$parserConfiguration->expects($this->once())->method('getInterceptors')->with(\TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_OBJECTACCESSOR)->will($this->returnValue(array($objectAccessorNodeInterceptor)));

		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->will($this->returnValue($objectAccessorNode));

		$mockNodeOnStack = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\AbstractNode', array(), array(), '', FALSE);
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->_set('configuration', $parserConfiguration);

		$templateParser->_call('objectAccessorHandler', $mockState, 'objectAccessorString', '', '', '');
	}

	/**
	 */
	public function argumentsStrings() {
		return array(
			array('a="2"', array('a' => '2')),
			array('a="2" b="foobar \' with \\" quotes"', array('a' => '2', 'b' => 'foobar \' with " quotes')),
			array(' arguments="{number : 362525200}"', array('arguments' => '{number : 362525200}'))
		);
	}

	/**
	 * @test
	 * @dataProvider argumentsStrings
	 * @param string $argumentsString
	 * @param array $expected
	 */
	public function parseArgumentsWorksAsExpected($argumentsString, array $expected) {
		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('buildArgumentObjectTree'));
		$templateParser->expects($this->any())->method('buildArgumentObjectTree')->will($this->returnArgument(0));

		$this->assertSame($expected, $templateParser->_call('parseArguments', $argumentsString));
	}

	/**
	 * @test
	 */
	public function buildArgumentObjectTreeReturnsTextNodeForSimplyString() {
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\TextNode', 'a very plain string')->will($this->returnValue('theTextNode'));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('dummy'));
		$templateParser->injectObjectManager($mockObjectManager);

		$this->assertEquals('theTextNode', $templateParser->_call('buildArgumentObjectTree', 'a very plain string'));
	}

	/**
	 * @test
	 */
	public function buildArgumentObjectTreeBuildsObjectTreeForComlexString() {
		$objectTree = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$objectTree->expects($this->once())->method('getRootNode')->will($this->returnValue('theRootNode'));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('splitTemplateAtDynamicTags', 'buildObjectTree'));
		$templateParser->expects($this->at(0))->method('splitTemplateAtDynamicTags')->with('a <very> {complex} string')->will($this->returnValue('split string'));
		$templateParser->expects($this->at(1))->method('buildObjectTree')->with('split string')->will($this->returnValue($objectTree));

		$this->assertEquals('theRootNode', $templateParser->_call('buildArgumentObjectTree', 'a <very> {complex} string'));
	}

	/**
	 * @test
	 */
	public function textAndShorthandSyntaxHandlerDelegatesAppropriately() {
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnArgument(1));
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('objectAccessorHandler', 'arrayHandler', 'textHandler'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->expects($this->at(0))->method('objectAccessorHandler')->with($mockState, 'someThing.absolutely', '', '', '');
		$templateParser->expects($this->at(1))->method('textHandler')->with($mockState, ' "fishy" is \'going\' ');
		$templateParser->expects($this->at(2))->method('arrayHandler')->with($mockState, 'on: "here"');

		$text = '{someThing.absolutely} "fishy" is \'going\' {on: "here"}';
		$templateParser->_call('textAndShorthandSyntaxHandler', $mockState, $text, \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::CONTEXT_INSIDE_VIEWHELPER_ARGUMENTS);
	}

	/**
	 * @test
	 */
	public function arrayHandlerAddsArrayNodeWithProperContentToStack() {
		$arrayNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ArrayNode', array(), array(array()));
		$mockNodeOnStack = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\AbstractNode', array(), array(), '', FALSE);
		$mockNodeOnStack->expects($this->once())->method('addChildNode')->with($arrayNode);
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ArrayNode', 'processedArrayText')->will($this->returnValue($arrayNode));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('recursiveArrayHandler'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->expects($this->once())->method('recursiveArrayHandler')->with('arrayText')->will($this->returnValue('processedArrayText'));

		$templateParser->_call('arrayHandler', $mockState, 'arrayText');
	}

	/**
	 */
	public function arrayTexts() {
		return array(
			array(
				'key1: "foo", key2: \'bar\', key3: someVar, key4: 123, key5: { key6: "baz" }',
				array('key1' => 'foo', 'key2' => 'bar', 'key3' => 'someVar', 'key4' => 123.0, 'key5' => array('key6' => 'baz'))
			),
			array(
				'key1: "\'foo\'", key2: \'\\\'bar\\\'\'',
				array('key1' => '\'foo\'', 'key2' => '\'bar\'')
			)
		);
	}

	/**
	 * @test
	 * @dataProvider arrayTexts
	 */
	public function recursiveArrayHandlerReturnsExpectedArray($arrayText, $expectedArray) {
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnArgument(1));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('buildArgumentObjectTree'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->expects($this->any())->method('buildArgumentObjectTree')->will($this->returnArgument(0));

		$this->assertSame($expectedArray, $templateParser->_call('recursiveArrayHandler', $arrayText));
	}

	/**
	 * @test
	 */
	public function textNodesAreRunThroughTextInterceptors() {
		$textNode = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\TextNode', array(), array(), '', FALSE);
		$textInterceptor = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\InterceptorInterface');
		$textInterceptor->expects($this->once())->method('process')->with($textNode)->will($this->returnArgument(0));

		$parserConfiguration = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\Configuration');
		$parserConfiguration->expects($this->once())->method('getInterceptors')->with(\TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_TEXT)->will($this->returnValue(array($textInterceptor)));

		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('get')->with('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\TextNode', 'string')->will($this->returnValue($textNode));

		$mockNodeOnStack = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\AbstractNode', array(), array(), '', FALSE);
		$mockNodeOnStack->expects($this->once())->method('addChildNode')->with($textNode);
		$mockState = $this->getMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\ParsingState');
		$mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

		$templateParser = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\Core\\Parser\\TemplateParser', array('splitTemplateAtDynamicTags', 'buildObjectTree'));
		$templateParser->injectObjectManager($mockObjectManager);
		$templateParser->_set('configuration', $parserConfiguration);

		$templateParser->_call('textHandler', $mockState, 'string');
	}
}

?>