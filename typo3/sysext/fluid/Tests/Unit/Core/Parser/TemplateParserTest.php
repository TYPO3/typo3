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
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Fluid\Core\Parser\TemplateParser;

/**
 * Testcase for TemplateParser.
 *
 * This is to at least half a system test, as it compares rendered results to
 * expectations, and does not strictly check the parsing...
 */
class TemplateParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function parseThrowsExceptionWhenStringArgumentMissing()
    {
        $templateParser = new \TYPO3\CMS\Fluid\Core\Parser\TemplateParser();
        $templateParser->parse(123);
    }

    /**
     * @test
     */
    public function extractNamespaceDefinitionsExtractsNamespacesCorrectly()
    {
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->_call('extractNamespaceDefinitions', ' \\{namespace f4=F7\\Rocks} {namespace f4=TYPO3\Rocks\Really}');
        $expected = [
            'f' => \TYPO3\CMS\Fluid\ViewHelpers::class,
            'f4' => 'TYPO3\Rocks\Really'
        ];
        $this->assertEquals($expected, $templateParser->getNamespaces(), 'Namespaces do not match.');
    }

    /**
     * @test
     */
    public function extractNamespaceDefinitionsExtractsXmlNamespacesCorrectly()
    {
        $mockSettings = [
            'namespaces' => [
                'http://domain.tld/ns/my/viewhelpers' => 'My\Namespace',
                'http://otherdomain.tld/ns/other/viewhelpers' => 'My\Other\Namespace'
            ]
        ];
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->injectSettings($mockSettings);
        $templateParser->_call('extractNamespaceDefinitions', 'Some content <html xmlns="http://www.w3.org/1999/xhtml" xmlns:f5="http://domain.tld/ns/my/viewhelpers"
		xmlns:xyz="http://otherdomain.tld/ns/other/viewhelpers" />');
        $expected = [
            'f' => \TYPO3\CMS\Fluid\ViewHelpers::class,
            'f5' => 'My\Namespace',
            'xyz' => 'My\Other\Namespace'
        ];
        $this->assertEquals($expected, $templateParser->getNamespaces(), 'Namespaces do not match.');
    }

    /**
     * @test
     */
    public function extractNamespaceDefinitionsResolveNamespacesWithDefaultPattern()
    {
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->_call('extractNamespaceDefinitions', '<xml xmlns="http://www.w3.org/1999/xhtml" xmlns:xyz="http://typo3.org/ns/Some/Package/ViewHelpers" />');
        $expected = [
            'f' => \TYPO3\CMS\Fluid\ViewHelpers::class,
            'xyz' => 'Some\Package\ViewHelpers'
        ];
        $this->assertEquals($expected, $templateParser->getNamespaces(), 'Namespaces do not match.');
    }

    /**
     * @test
     */
    public function extractNamespaceDefinitionsSilentlySkipsXmlNamespaceDeclarationsThatCantBeResolved()
    {
        $mockSettings = [
            'namespaces' => [
                'http://domain.tld/ns/my/viewhelpers' => 'My\Namespace'
            ]
        ];
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->injectSettings($mockSettings);
        $templateParser->_call('extractNamespaceDefinitions', '<xml xmlns="http://www.w3.org/1999/xhtml" xmlns:f5="http://domain.tld/ns/my/viewhelpers"
		xmlns:xyz="http://otherdomain.tld/ns/other/viewhelpers" />');
        $expected = [
            'f' => \TYPO3\CMS\Fluid\ViewHelpers::class,
            'f5' => 'My\Namespace'
        ];
        $this->assertEquals($expected, $templateParser->getNamespaces(), 'Namespaces do not match.');
    }

    /**
     * @test
     */
    public function extractNamespaceDefinitionsSilentlySkipsXmlNamespaceDeclarationForTheDefaultFluidNamespace()
    {
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->_call('extractNamespaceDefinitions', '<foo xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://domain.tld/this/will/be/ignored" />');
        $expected = [
            'f' => \TYPO3\CMS\Fluid\ViewHelpers::class
        ];
        $this->assertEquals($expected, $templateParser->getNamespaces(), 'Namespaces do not match.');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function extractNamespaceDefinitionsThrowsExceptionIfNamespaceIsRedeclared()
    {
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->_call('extractNamespaceDefinitions', '{namespace typo3=TYPO3\CMS\Fluid\Blablubb} {namespace typo3= TYPO3\Rocks\Blu}');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function extractNamespaceDefinitionsThrowsExceptionIfXmlNamespaceIsRedeclaredAsFluidNamespace()
    {
        $mockSettings = [
            'namespaces' => [
                'http://domain.tld/ns/my/viewhelpers' => 'My\Namespace'
            ]
        ];
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->injectSettings($mockSettings);
        $templateParser->_call('extractNamespaceDefinitions', '<foo xmlns="http://www.w3.org/1999/xhtml" xmlns:typo3="http://domain.tld/ns/my/viewhelpers" />{namespace typo3=TYPO3\CMS\Fluid\Blablubb}');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function extractNamespaceDefinitionsThrowsExceptionIfFluidNamespaceIsRedeclaredAsXmlNamespace()
    {
        $mockSettings = [
            'namespaces' => [
                'http://domain.tld/ns/my/viewhelpers' => 'My\Namespace'
            ]
        ];
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->injectSettings($mockSettings);
        $templateParser->_call('extractNamespaceDefinitions', '{namespace typo3=TYPO3\CMS\Fluid\Blablubb} <foo xmlns="http://www.w3.org/1999/xhtml" xmlns:typo3="http://domain.tld/ns/my/viewhelpers" />');
    }

    /**
     * @param array $expectedFoundIdentifiers
     * @param string $templateString
     * @param array $namespaces
     * @test
     * @dataProvider extractNamespaceDefinitionsCallsRemoveXmlnsViewHelperNamespaceDeclarationsWithCorrectFoundIdentifiersDataProvider
     */
    public function extractNamespaceDefinitionsCallsRemoveXmlnsViewHelperNamespaceDeclarationsWithCorrectFoundIdentifiers(array $expectedFoundIdentifiers, $templateString, array $namespaces)
    {
        $mockSettings = [
            'namespaces' => $namespaces
        ];

        /** @var TemplateParser|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $templateParser */
        $templateParser = $this->getAccessibleMock(TemplateParser::class, ['removeXmlnsViewHelperNamespaceDeclarations']);
        $templateParser->injectSettings($mockSettings);

        // this verifies that the method is called with the correct found identifiers
        // and also that the templateString was not modified before calling removeXmlnsViewHelperNamespaceDeclarations
        $templateParser
            ->expects($this->once())
            ->method('removeXmlnsViewHelperNamespaceDeclarations')
            ->with($templateString, $expectedFoundIdentifiers)
            ->willReturnArgument(0);
        $templateParser->_call('extractNamespaceDefinitions', $templateString);
    }

    /**
     * @return array
     */
    public function extractNamespaceDefinitionsCallsRemoveXmlnsViewHelperNamespaceDeclarationsWithCorrectFoundIdentifiersDataProvider()
    {
        return [
            'bothViewHelperNamespacesDefinitionsOnlyProvideXmlnsViewHelpersUsingNonDefaultPatternViewHelpers' => [
                ['foo'],
                '{namespace typo3=TYPO3\\CMS\\Fluid\\Blablubb} <div xmlns:foo="http://domain.tld/ns/foo/viewhelpers">Content</div>',
                ['http://domain.tld/ns/foo/viewhelpers' => 'My\\Namespace']
            ],
            'bothViewHelperNamespacesDefinitionsOnlyProvideXmlnsViewHelpersUsingDefaultPatternViewHelpers' => [
                ['xyz'],
                '{namespace typo3=TYPO3\\CMS\\Fluid\\Blablubb} <div xmlns:xyz="http://typo3.org/ns/Some/Package/ViewHelpers">Content</div>',
                []
            ],
            'xmlnsIdentifiersWithWhitespaces' => [
                [' ', 'foo bar', '"x y z"'],
                '
					<div xmlns: ="http://typo3.org/ns/Some/Package/ViewHelpers"
							xmlns:foo bar="http://domain.tld/ns/foobar/viewhelpers"
							xmlns:"x y z"="http://typo3.org/ns/My/Xyz/ViewHelpers">

						Content
					</div>
				',
                ['http://domain.tld/ns/foobar/viewhelpers' => 'My\\Namespace']
            ],
            'xmlnsWithEqualsSign' => [
                ['=', 'foo=bar', '"x=y=z"'],
                '
					<div xmlns:=="http://typo3.org/ns/Some/Package/ViewHelpers"
							xmlns:foo=bar="http://domain.tld/ns/foobar/viewhelpers"
							xmlns:"x=y=z"="http://typo3.org/ns/My/Xyz/ViewHelpers">

						Content
					</div>
				',
                ['http://domain.tld/ns/foobar/viewhelpers' => 'My\\Namespace']
            ],
            'nonViewHelpersXmlnsAreNotIncludedButDefaultPatternAndNonDefaultAreIncluded' => [
                ['xyz', 'foo'],
                '<div xmlns:xyz="http://typo3.org/ns/Some/Package/ViewHelpers"
						xmlns:foo="http://domain.tld/ns/foo/viewhelpers"
						xmlns:bar="http://typo3.org/foo/bar">

					Content
				</div>',
                ['http://domain.tld/ns/foo/viewhelpers' => 'My\\Namespace']
            ],
            'nonViewHelpersInBetweenViewHelperXmlnsAreNotIncludedButDefaultPatternAndNonDefaultAreIncluded' => [
                ['xyz', 'foo'],
                '<div xmlns:xyz="http://typo3.org/ns/Some/Package/ViewHelpers"
							xmlns:bar="http://typo3.org/foo/bar"
							xmlns:foo="http://domain.tld/ns/foo/viewhelpers">

					Content
				</div>',
                ['http://domain.tld/ns/foo/viewhelpers' => 'My\\Namespace']
            ],
            'fluidNamespaceIsFound' => [
                ['f'],
                '<div xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">Content</div>',
                []
            ],
            'xmlnsWithoutIdentifierIsIgnored' => [
                [],
                '<div xmlns="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">Content</div>',
                []
            ],
            'htmlTagAlsoFindsIdentifiers' => [
                ['f', 'xyz'],
                '<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
								xmlns:xyz="http://typo3.org/ns/Some/Package/ViewHelpers">

					Content
				</html>',
                []
            ],
            'htmlTagWithNamespaceTypo3FluidAttributeTagAlsoFindsIdentifiers' => [
                ['f', 'xyz'],
                '<html data-namespace-typo3-fluid="true"
					xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
					xmlns:xyz="http://typo3.org/ns/Some/Package/ViewHelpers">

					Content
				</html>',
                []
            ],
            'nonHtmlTagAlsoFindsIdentifiers' => [
                ['f', 'xyz'],
                '<typo3-root
					xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
					xmlns:xyz="http://typo3.org/ns/Some/Package/ViewHelpers">

					Content
				</typo3-root>',
                []
            ],
        ];
    }

    /**
     * @param string $expectedOut
     * @param string $templateString
     * @param array $foundIdentifiers
     * @test
     * @dataProvider removeXmlnsViewHelperNamespaceDeclarationsDataProvider
     */
    public function removeXmlnsViewHelperNamespaceDeclarationsWorks($expectedOut, array $foundIdentifiers, $templateString)
    {
        $templateParser = $this->getAccessibleMock(TemplateParser::class, ['dummy']);
        $templateString = $templateParser->_call('removeXmlnsViewHelperNamespaceDeclarations', $templateString, $foundIdentifiers);

        // remove tabs and trim because expected result and given have a different tab count in dataProvider which is not relevant for the parser (xml and html)
        $this->assertSame(trim(str_replace("\t", '', $expectedOut)), trim(str_replace("\t", '', $templateString)));
    }

    /**
     * DataProvider for removeXmlnsViewHelperNamespaceDeclarationsWorks test
     *
     * @return array
     */
    public function removeXmlnsViewHelperNamespaceDeclarationsDataProvider()
    {
        return [
            'onlyViewHelperXmlns' => [
                '
					<div >
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                ['f', 'fe'],
                '<div xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
							xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers">
					<f:if condition="{demo}">Hello World</f:if>
				</div>'
            ],
            'xmlnsViewHelpersFoundWithNonViewHelperXmlnsAtBeginning' => [
                '
					<div xmlns:z="http://www.typo3.org/foo" >
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                ['f', 'fe'],
                '
					<div xmlns:z="http://www.typo3.org/foo"
							xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
							xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ],
            'xmlnsViewHelpersFoundWithNonViewHelperXmlnsAtEnd' => [
                '
					<div xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                ['f', 'fe'],
                '
					<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ],
            'xmlnsViewHelpersFoundWithMultipleNonViewHelperXmlns' => [
                '
					<div xmlns:y="http://www.typo3.org/bar" xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                ['f', 'fe'],
                '
					<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:y="http://www.typo3.org/bar"
							xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ],
            'xmlnsViewHelpersFoundWithNonViewHelperXmlnsBetween' => [
                '
					<div xmlns:z="http://www.typo3.org/foo" >
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                ['fe', 'f'],
                '
					<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo"
							xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ],
            'removeHtmlTagWithAttributeButNoXmlnsViewHelpersFound' => [
                '<f:if condition="{demo}">Hello World</f:if>',
                [],
                '
					<html data-namespace-typo3-fluid="true">
						<f:if condition="{demo}">Hello World</f:if>
					</html>
				'
            ],
            'doNotRemoveHtmlTagBecauseHtmlTagNotMarkedAsFluidNamespaceDefinitionTag' => [
                '
					<html xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</html>
				',
                ['fe', 'f'],
                '
					<html xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</html>
				'
            ],
            'doNotModifyHtmlTagBecauseViewHelperXmlnsNotFoundInTagAndNotMarkedForRemoval' => [
                '
					<html xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</html>
				',
                ['f'], // required because without any found namespaces the method will not do any replacements
                '
					<html xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</html>
				'
            ],
            'removeHtmlTagBecauseXmlnsFoundInTagAndMarkedAsFluidViewHelperDefinitionTag' => [
                '<f:if condition="{demo}">Hello World</f:if>',
                ['fe'],
                '
					<html data-namespace-typo3-fluid="true"
							xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</html>
				'
            ],
            'removeHtmlTagBecauseXmlnsFoundInDifferentTagAndMarkedAsFluidViewHelperDefinitionTag' => [
                '<f:if condition="{demo}">Hello World</f:if>',
                ['f'],
                '
					<html data-namespace-typo3-fluid="true" xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</html>
				'
            ],
            'producesExcpedtedOutputIfFouundIdentifiersAreWrongButContainNoExistingNonViewHelperXmlns' => [
                '
					<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers" xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                ['f', 'i'],
                '
					<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ],
            // this test verifies that the expected output can be wrong if the foundNameSpaces are incorrect
            // which is why extractNamespaceDefinitionsCallsRemoveXmlnsViewHelperNamespaceDeclarationsWithCorrectFoundIdentifiers
            // tests if the correct identifiers are found
            'removesNonViewHelperNamespaceIfFoundIdentifiersAreWrong' => [
                '
					<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers" >
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                ['f', 'z'],
                '
					<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ],
            // this verifies that the scan pattern was correctly quoted for the regular expression
            // because if the regular expression delimiter were to be modified in the pattern,
            // the corresponding preg_quote will fail without adaptions
            'xmlnsWithScanPatternAsIdentifier' => [
                '
					<div >
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                [TemplateParser::$SCAN_PATTERN_REMOVE_VIEWHELPERS_XMLNSDECLARATIONS],
                '
					<div xmlns:' . TemplateParser::$SCAN_PATTERN_REMOVE_VIEWHELPERS_XMLNSDECLARATIONS . '="http://typo3.org/ns/TYPO3/CMS\Demo/ViewHelpers">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ],
            // these scenarios also need to because even if the foundIdentifiers are
            // invalid the method should still work as expected,
            // Furthermore, currently these patterns are allowed for foundIdentifiers
            // see also test extractNamespaceDefinitionsCallsRemoveXmlnsViewHelperNamespaceDeclarationsWithCorrectFoundIdentifiers
            'xmlnsIdentifiersWithWhitespaces' => [
                '
					<div xmlns:none xyz="http://domain.tld/ns/NoneXyz/viewhelpers" >

						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                [' ', 'foo bar', '"x y z"'],
                '
					<div xmlns: ="http://typo3.org/ns/Some/Package/ViewHelpers"
							xmlns:foo bar="http://domain.tld/ns/foobar/viewhelpers"
							xmlns:none xyz="http://domain.tld/ns/NoneXyz/viewhelpers"
							xmlns:"x y z"="http://typo3.org/ns/My/Xyz/ViewHelpers">

						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ],
            'xmlnsWithRegularExpressionAsIdentifier' => [
                '
					<div xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                ['f', 'fe', '.*.?\\s'],
                '
					<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
							xmlns:.*.?\\s="http://typo3.org/ns/TYPO3/CMS\Demo/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ],
            'xmlnsWithRegularExpressionDelimiterAsIdentifier' => [
                '
					<div xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                ['f', 'fe', '/'],
                '
					<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"
							xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
							xmlns:/="http://typo3.org/ns/TYPO3/CMS\Demo/ViewHelpers"
							xmlns:z="http://www.typo3.org/foo">
						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ],
            'xmlnsWithEqualsSign' => [
                '
					<div xmlns:none=xyz="http://domain.tld/ns/NoneXyz/viewhelpers" >

						<f:if condition="{demo}">Hello World</f:if>
					</div>
				',
                ['=', 'foo=bar', '"x=y=z"'],
                '
					<div xmlns:=="http://typo3.org/ns/Some/Package/ViewHelpers"
							xmlns:foo=bar="http://domain.tld/ns/foobar/viewhelpers"
							xmlns:none=xyz="http://domain.tld/ns/NoneXyz/viewhelpers"
							xmlns:"x=y=z"="http://typo3.org/ns/My/Xyz/ViewHelpers">

						<f:if condition="{demo}">Hello World</f:if>
					</div>
				'
            ]
        ];
    }

    /**
     * @test
     */
    public function viewHelperNameWithMultipleLevelsCanBeResolvedByResolveViewHelperName()
    {
        $mockTemplateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy'], [], '', false);
        $result = $mockTemplateParser->_call('resolveViewHelperName', 'f', 'foo.bar.baz');
        $expected = 'TYPO3\CMS\Fluid\ViewHelpers\Foo\Bar\BazViewHelper';
        $this->assertEquals($expected, $result, 'The name of the View Helper Name could not be resolved.');
    }

    /**
     * @test
     */
    public function viewHelperNameWithOneLevelCanBeResolvedByResolveViewHelperName()
    {
        $mockTemplateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy'], [], '', false);
        $actual = $mockTemplateParser->_call('resolveViewHelperName', 'f', 'myown');
        $expected = 'TYPO3\CMS\Fluid\ViewHelpers\MyownViewHelper';
        $this->assertEquals($expected, $actual);
    }

    /**
     */
    public function quotedStrings()
    {
        return [
            ['"no quotes here"', 'no quotes here'],
            ["'no quotes here'", 'no quotes here'],
            ["'this \"string\" had \\'quotes\\' in it'", 'this "string" had \'quotes\' in it'],
            ['"this \\"string\\" had \'quotes\' in it"', 'this "string" had \'quotes\' in it'],
            ['"a weird \"string\" \'with\' *freaky* \\\\stuff', 'a weird "string" \'with\' *freaky* \\stuff'],
            ['\'\\\'escaped quoted string in string\\\'\'', '\'escaped quoted string in string\'']
        ];
    }

    /**
     * @dataProvider quotedStrings
     * @test
     */
    public function unquoteStringReturnsUnquotedStrings($quoted, $unquoted)
    {
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $this->assertEquals($unquoted, $templateParser->_call('unquoteString', $quoted));
    }

    /**
     */
    public function templatesToSplit()
    {
        return [
            ['TemplateParserTestFixture01-shorthand'],
            ['TemplateParserTestFixture06'],
            ['TemplateParserTestFixture14']
        ];
    }

    /**
     * @dataProvider templatesToSplit
     * @test
     */
    public function splitTemplateAtDynamicTagsReturnsCorrectlySplitTemplate($templateName)
    {
        $template = file_get_contents(__DIR__ . '/Fixtures/' . $templateName . '.html', FILE_TEXT);
        $expectedResult = require(__DIR__ . '/Fixtures/' . $templateName . '-split.php');
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $this->assertSame($expectedResult, $templateParser->_call('splitTemplateAtDynamicTags', $template), 'Filed for ' . $templateName);
    }

    /**
     * @test
     */
    public function buildObjectTreeCreatesRootNodeAndSetsUpParsingState()
    {
        $mockRootNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class);

        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('setRootNode')->with($mockRootNode);
        $mockState->expects($this->once())->method('pushNodeToStack')->with($mockRootNode);
        $mockState->expects($this->once())->method('countNodeStack')->will($this->returnValue(1));

        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class)->will($this->returnValue($mockState));
        $mockObjectManager->expects($this->at(1))->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class)->will($this->returnValue($mockRootNode));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->_set('objectManager', $mockObjectManager);
        $templateParser->_call('buildObjectTree', [], \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::CONTEXT_OUTSIDE_VIEWHELPER_ARGUMENTS);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function buildObjectTreeThrowsExceptionIfOpenTagsRemain()
    {
        $mockRootNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class);

        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('countNodeStack')->will($this->returnValue(2));

        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class)->will($this->returnValue($mockState));
        $mockObjectManager->expects($this->at(1))->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class)->will($this->returnValue($mockRootNode));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->_set('objectManager', $mockObjectManager);
        $templateParser->_call('buildObjectTree', [], \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::CONTEXT_OUTSIDE_VIEWHELPER_ARGUMENTS);
    }

    /**
     * @test
     */
    public function buildObjectTreeDelegatesHandlingOfTemplateElements()
    {
        $mockRootNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class);
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('countNodeStack')->will($this->returnValue(1));
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class)->will($this->returnValue($mockState));
        $mockObjectManager->expects($this->at(1))->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode::class)->will($this->returnValue($mockRootNode));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['textHandler', 'openingViewHelperTagHandler', 'closingViewHelperTagHandler', 'textAndShorthandSyntaxHandler']);
        $templateParser->_set('objectManager', $mockObjectManager);
        $templateParser->expects($this->at(0))->method('textAndShorthandSyntaxHandler')->with($mockState, 'The first part is simple');
        $templateParser->expects($this->at(1))->method('textHandler')->with($mockState, '<f:for each="{a: {a: 0, b: 2, c: 4}}" as="array"><f:for each="{array}" as="value">{value} </f:for>');
        $templateParser->expects($this->at(2))->method('openingViewHelperTagHandler')->with($mockState, 'f', 'format.printf', ' arguments="{number : 362525200}"', false);
        $templateParser->expects($this->at(3))->method('textAndShorthandSyntaxHandler')->with($mockState, '%.3e');
        $templateParser->expects($this->at(4))->method('closingViewHelperTagHandler')->with($mockState, 'f', 'format.printf');
        $templateParser->expects($this->at(5))->method('textAndShorthandSyntaxHandler')->with($mockState, 'and here goes some {text} that could have {shorthand}');

        $splitTemplate = $templateParser->_call('splitTemplateAtDynamicTags', 'The first part is simple<![CDATA[<f:for each="{a: {a: 0, b: 2, c: 4}}" as="array"><f:for each="{array}" as="value">{value} </f:for>]]><f:format.printf arguments="{number : 362525200}">%.3e</f:format.printf>and here goes some {text} that could have {shorthand}');
        $templateParser->_call('buildObjectTree', $splitTemplate, \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::CONTEXT_OUTSIDE_VIEWHELPER_ARGUMENTS);
    }

    /**
     * @test
     */
    public function openingViewHelperTagHandlerDelegatesViewHelperInitialization()
    {
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->never())->method('popNodeFromStack');

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['parseArguments', 'initializeViewHelperAndAddItToStack']);
        $templateParser->expects($this->once())->method('parseArguments')->with(['arguments'])->will($this->returnValue(['parsedArguments']));
        $templateParser->expects($this->once())->method('initializeViewHelperAndAddItToStack')->with($mockState, 'namespaceIdentifier', 'methodIdentifier', ['parsedArguments']);

        $templateParser->_call('openingViewHelperTagHandler', $mockState, 'namespaceIdentifier', 'methodIdentifier', ['arguments'], false);
    }

    /**
     * @test
     */
    public function openingViewHelperTagHandlerPopsNodeFromStackForSelfClosingTags()
    {
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('popNodeFromStack')->will($this->returnValue($this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface::class)));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['parseArguments', 'initializeViewHelperAndAddItToStack']);

        $templateParser->_call('openingViewHelperTagHandler', $mockState, '', '', [], true);
    }

    /**
     * @test
     */
    public function initializeViewHelperAndAddItToStackCreatesRequestedViewHelperAndViewHelperNode()
    {
        $mockViewHelper = $this->getMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class);
        $mockViewHelperNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class, [], [], '', false);

        $mockNodeOnStack = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface::class);
        $mockNodeOnStack->expects($this->once())->method('addChildNode')->with($mockViewHelperNode);

        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\MyownViewHelper')->will($this->returnValue($mockViewHelper));
        $mockObjectManager->expects($this->at(1))->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class)->will($this->returnValue($mockViewHelperNode));

        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));
        $mockState->expects($this->once())->method('pushNodeToStack')->with($mockViewHelperNode);

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['abortIfUnregisteredArgumentsExist', 'abortIfRequiredArgumentsAreMissing', 'rewriteBooleanNodesInArgumentsObjectTree']);
        $templateParser->_set('objectManager', $mockObjectManager);

        $templateParser->_call('initializeViewHelperAndAddItToStack', $mockState, 'f', 'myown', ['arguments']);
    }

    /**
     * @test
     */
    public function initializeViewHelperAndAddItToStackChecksViewHelperArguments()
    {
        $expectedArguments = ['expectedArguments'];
        $argumentsObjectTree = ['arguments'];

        $mockViewHelper = $this->getMock(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class);
        $mockViewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue($expectedArguments));
        $mockViewHelperNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class, [], [], '', false);

        $mockNodeOnStack = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface::class);

        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\MyownViewHelper')->will($this->returnValue($mockViewHelper));
        $mockObjectManager->expects($this->at(1))->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class)->will($this->returnValue($mockViewHelperNode));

        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['abortIfUnregisteredArgumentsExist', 'abortIfRequiredArgumentsAreMissing', 'rewriteBooleanNodesInArgumentsObjectTree']);
        $templateParser->_set('objectManager', $mockObjectManager);
        $templateParser->expects($this->once())->method('abortIfUnregisteredArgumentsExist')->with($expectedArguments, $argumentsObjectTree);
        $templateParser->expects($this->once())->method('abortIfRequiredArgumentsAreMissing')->with($expectedArguments, $argumentsObjectTree);

        $templateParser->_call('initializeViewHelperAndAddItToStack', $mockState, 'f', 'myown', $argumentsObjectTree);
    }

    /**
     * @test
     */
    public function initializeViewHelperAndAddItToStackHandlesPostParseFacets()
    {
        $mockViewHelper = $this->getMock(\TYPO3\CMS\Fluid\Tests\Unit\Core\Parser\Fixtures\PostParseFacetViewHelper::class, ['prepareArguments']);
        $mockViewHelperNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class, [], [], '', false);

        $mockNodeOnStack = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface::class);
        $mockNodeOnStack->expects($this->once())->method('addChildNode')->with($mockViewHelperNode);

        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\MyownViewHelper')->will($this->returnValue($mockViewHelper));
        $mockObjectManager->expects($this->at(1))->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class)->will($this->returnValue($mockViewHelperNode));

        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));
        $mockState->expects($this->once())->method('getVariableContainer')->will($this->returnValue($this->getMock(\TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer::class)));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['abortIfUnregisteredArgumentsExist', 'abortIfRequiredArgumentsAreMissing', 'rewriteBooleanNodesInArgumentsObjectTree']);
        $templateParser->_set('objectManager', $mockObjectManager);

        $templateParser->_call('initializeViewHelperAndAddItToStack', $mockState, 'f', 'myown', ['arguments']);
        $this->assertTrue(\TYPO3\CMS\Fluid\Tests\Unit\Core\Parser\Fixtures\PostParseFacetViewHelper::$wasCalled, 'PostParse was not called!');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function abortIfUnregisteredArgumentsExistThrowsExceptionOnUnregisteredArguments()
    {
        $expected = [new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('firstArgument', 'string', '', false)];
        $actual = ['firstArgument' => 'foo', 'secondArgument' => 'bar'];

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);

        $templateParser->_call('abortIfUnregisteredArgumentsExist', $expected, $actual);
    }

    /**
     * @test
     */
    public function abortIfUnregisteredArgumentsExistDoesNotThrowExceptionIfEverythingIsOk()
    {
        $expectedArguments = [
            new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('name1', 'string', 'desc', false),
            new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('name2', 'string', 'desc', true)
        ];
        $actualArguments = [
            'name1' => 'bla'
        ];

        $mockTemplateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);

        $mockTemplateParser->_call('abortIfUnregisteredArgumentsExist', $expectedArguments, $actualArguments);
        // dummy assertion to avoid "did not perform any assertions" error
        $this->assertTrue(true);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function abortIfRequiredArgumentsAreMissingThrowsException()
    {
        $expected = [
            new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('firstArgument', 'string', '', false),
            new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('secondArgument', 'string', '', true)
        ];

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);

        $templateParser->_call('abortIfRequiredArgumentsAreMissing', $expected, []);
    }

    /**
     * @test
     */
    public function abortIfRequiredArgumentsAreMissingDoesNotThrowExceptionIfRequiredArgumentExists()
    {
        $expectedArguments = [
            new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('name1', 'string', 'desc', false),
            new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition('name2', 'string', 'desc', true)
        ];
        $actualArguments = [
            'name2' => 'bla'
        ];

        $mockTemplateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);

        $mockTemplateParser->_call('abortIfRequiredArgumentsAreMissing', $expectedArguments, $actualArguments);
        // dummy assertion to avoid "did not perform any assertions" error
        $this->assertTrue(true);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function closingViewHelperTagHandlerThrowsExceptionBecauseOfClosingTagWhichWasNeverOpened()
    {
        $mockNodeOnStack = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface::class, [], [], '', false);
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('popNodeFromStack')->will($this->returnValue($mockNodeOnStack));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);

        $templateParser->_call('closingViewHelperTagHandler', $mockState, 'f', 'method');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\Parser\Exception
     */
    public function closingViewHelperTagHandlerThrowsExceptionBecauseOfWrongTagNesting()
    {
        $mockNodeOnStack = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class, [], [], '', false);
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('popNodeFromStack')->will($this->returnValue($mockNodeOnStack));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);

        $templateParser->_call('closingViewHelperTagHandler', $mockState, 'f', 'method');
    }

    /**
     * @test
     */
    public function objectAccessorHandlerCallsInitializeViewHelperAndAddItToStackIfViewHelperSyntaxIsPresent()
    {
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->exactly(2))->method('popNodeFromStack')->will($this->returnValue($this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface::class)));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['recursiveArrayHandler', 'postProcessArgumentsForObjectAccessor', 'initializeViewHelperAndAddItToStack']);
        $templateParser->expects($this->at(0))->method('recursiveArrayHandler')->with('format: "H:i"')->will($this->returnValue(['format' => 'H:i']));
        $templateParser->expects($this->at(1))->method('postProcessArgumentsForObjectAccessor')->with(['format' => 'H:i'])->will($this->returnValue(['processedArguments']));
        $templateParser->expects($this->at(2))->method('initializeViewHelperAndAddItToStack')->with($mockState, 'f', 'format.date', ['processedArguments']);
        $templateParser->expects($this->at(3))->method('initializeViewHelperAndAddItToStack')->with($mockState, 'f', 'base', []);

        $templateParser->_call('objectAccessorHandler', $mockState, '', '', 'f:base() f:format.date(format: "H:i")', '');
    }

    /**
     * @test
     */
    public function objectAccessorHandlerCreatesObjectAccessorNodeWithExpectedValueAndAddsItToStack()
    {
        $objectAccessorNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::class, [], [], '', false);

        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::class, 'objectAccessorString')->will($this->returnValue($objectAccessorNode));

        $mockNodeOnStack = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode::class, [], [], '', false);
        $mockNodeOnStack->expects($this->once())->method('addChildNode')->with($objectAccessorNode);
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->_set('objectManager', $mockObjectManager);

        $templateParser->_call('objectAccessorHandler', $mockState, 'objectAccessorString', '', '', '');
    }

    /**
     * @test
     */
    public function valuesFromObjectAccessorsAreRunThroughValueInterceptorsByDefault()
    {
        $objectAccessorNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode::class, [], [], '', false);
        $objectAccessorNodeInterceptor = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::class);
        $objectAccessorNodeInterceptor->expects($this->once())->method('process')->with($objectAccessorNode)->will($this->returnArgument(0));

        $parserConfiguration = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\Configuration::class);
        $parserConfiguration->expects($this->once())->method('getInterceptors')->with(\TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_OBJECTACCESSOR)->will($this->returnValue([$objectAccessorNodeInterceptor]));

        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->will($this->returnValue($objectAccessorNode));

        $mockNodeOnStack = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode::class, [], [], '', false);
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->_set('objectManager', $mockObjectManager);
        $templateParser->_set('configuration', $parserConfiguration);

        $templateParser->_call('objectAccessorHandler', $mockState, 'objectAccessorString', '', '', '');
    }

    /**
     */
    public function argumentsStrings()
    {
        return [
            ['a="2"', ['a' => '2']],
            ['a="2" b="foobar \' with \\" quotes"', ['a' => '2', 'b' => 'foobar \' with " quotes']],
            [' arguments="{number : 362525200}"', ['arguments' => '{number : 362525200}']]
        ];
    }

    /**
     * @test
     * @dataProvider argumentsStrings
     * @param string $argumentsString
     * @param array $expected
     */
    public function parseArgumentsWorksAsExpected($argumentsString, array $expected)
    {
        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['buildArgumentObjectTree']);
        $templateParser->expects($this->any())->method('buildArgumentObjectTree')->will($this->returnArgument(0));

        $this->assertSame($expected, $templateParser->_call('parseArguments', $argumentsString));
    }

    /**
     * @test
     */
    public function buildArgumentObjectTreeReturnsTextNodeForSimplyString()
    {
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode::class, 'a very plain string')->will($this->returnValue('theTextNode'));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['dummy']);
        $templateParser->_set('objectManager', $mockObjectManager);

        $this->assertEquals('theTextNode', $templateParser->_call('buildArgumentObjectTree', 'a very plain string'));
    }

    /**
     * @test
     */
    public function buildArgumentObjectTreeBuildsObjectTreeForComlexString()
    {
        $objectTree = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $objectTree->expects($this->once())->method('getRootNode')->will($this->returnValue('theRootNode'));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['splitTemplateAtDynamicTags', 'buildObjectTree']);
        $templateParser->expects($this->at(0))->method('splitTemplateAtDynamicTags')->with('a <very> {complex} string')->will($this->returnValue('split string'));
        $templateParser->expects($this->at(1))->method('buildObjectTree')->with('split string')->will($this->returnValue($objectTree));

        $this->assertEquals('theRootNode', $templateParser->_call('buildArgumentObjectTree', 'a <very> {complex} string'));
    }

    /**
     * @test
     */
    public function textAndShorthandSyntaxHandlerDelegatesAppropriately()
    {
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnArgument(1));
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['objectAccessorHandler', 'arrayHandler', 'textHandler']);
        $templateParser->_set('objectManager', $mockObjectManager);
        $templateParser->expects($this->at(0))->method('objectAccessorHandler')->with($mockState, 'someThing.absolutely', '', '', '');
        $templateParser->expects($this->at(1))->method('textHandler')->with($mockState, ' "fishy" is \'going\' ');
        $templateParser->expects($this->at(2))->method('arrayHandler')->with($mockState, 'on: "here"');

        $text = '{someThing.absolutely} "fishy" is \'going\' {on: "here"}';
        $templateParser->_call('textAndShorthandSyntaxHandler', $mockState, $text, \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::CONTEXT_INSIDE_VIEWHELPER_ARGUMENTS);
    }

    /**
     * @test
     */
    public function arrayHandlerAddsArrayNodeWithProperContentToStack()
    {
        $arrayNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ArrayNode::class, [], [[]]);
        $mockNodeOnStack = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode::class, [], [], '', false);
        $mockNodeOnStack->expects($this->once())->method('addChildNode')->with($arrayNode);
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ArrayNode::class, 'processedArrayText')->will($this->returnValue($arrayNode));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['recursiveArrayHandler']);
        $templateParser->_set('objectManager', $mockObjectManager);
        $templateParser->expects($this->once())->method('recursiveArrayHandler')->with('arrayText')->will($this->returnValue('processedArrayText'));

        $templateParser->_call('arrayHandler', $mockState, 'arrayText');
    }

    /**
     */
    public function arrayTexts()
    {
        return [
            [
                'key1: "foo", key2: \'bar\', key3: someVar, key4: 123, key5: { key6: "baz" }',
                ['key1' => 'foo', 'key2' => 'bar', 'key3' => 'someVar', 'key4' => 123.0, 'key5' => ['key6' => 'baz']]
            ],
            [
                'key1: "\'foo\'", key2: \'\\\'bar\\\'\'',
                ['key1' => '\'foo\'', 'key2' => '\'bar\'']
            ]
        ];
    }

    /**
     * @test
     * @dataProvider arrayTexts
     */
    public function recursiveArrayHandlerReturnsExpectedArray($arrayText, $expectedArray)
    {
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnArgument(1));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['buildArgumentObjectTree']);
        $templateParser->_set('objectManager', $mockObjectManager);
        $templateParser->expects($this->any())->method('buildArgumentObjectTree')->will($this->returnArgument(0));

        $this->assertSame($expectedArray, $templateParser->_call('recursiveArrayHandler', $arrayText));
    }

    /**
     * @test
     */
    public function textNodesAreRunThroughTextInterceptors()
    {
        $textNode = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode::class, [], [], '', false);
        $textInterceptor = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::class);
        $textInterceptor->expects($this->once())->method('process')->with($textNode)->will($this->returnArgument(0));

        $parserConfiguration = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\Configuration::class);
        $parserConfiguration->expects($this->once())->method('getInterceptors')->with(\TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface::INTERCEPT_TEXT)->will($this->returnValue([$textInterceptor]));

        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode::class, 'string')->will($this->returnValue($textNode));

        $mockNodeOnStack = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode::class, [], [], '', false);
        $mockNodeOnStack->expects($this->once())->method('addChildNode')->with($textNode);
        $mockState = $this->getMock(\TYPO3\CMS\Fluid\Core\Parser\ParsingState::class);
        $mockState->expects($this->once())->method('getNodeFromStack')->will($this->returnValue($mockNodeOnStack));

        $templateParser = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::class, ['splitTemplateAtDynamicTags', 'buildObjectTree']);
        $templateParser->_set('objectManager', $mockObjectManager);
        $templateParser->_set('configuration', $parserConfiguration);

        $templateParser->_call('textHandler', $mockState, 'string');
    }
}
