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

/**
 * Test case for Regular expressions in parser
 */
class TemplateParserPatternTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function testSCAN_PATTERN_LEGACYNAMESPACEDECLARATION()
    {
        $pattern = str_replace('FLUID_NAMESPACE_SEPARATOR', preg_quote(\TYPO3\CMS\Fluid\Fluid::LEGACY_NAMESPACE_SEPARATOR), \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SCAN_PATTERN_NAMESPACEDECLARATION);
        $this->assertEquals(preg_match($pattern, '{namespace acme=Tx_AcmeMyPackage_Bla_blubb}'), 1, 'The SCAN_PATTERN_NAMESPACEDECLARATION pattern did not match a namespace declaration (1).');
        $this->assertEquals(preg_match($pattern, '{namespace acme=Tx_AcmeMyPackage_Bla_Blubb }'), 1, 'The SCAN_PATTERN_NAMESPACEDECLARATION pattern did not match a namespace declaration (2).');
        $this->assertEquals(preg_match($pattern, '    {namespace foo = Tx_Foo_Bla3_Blubb }    '), 1, 'The SCAN_PATTERN_NAMESPACEDECLARATION pattern did not match a namespace declaration (3).');
        $this->assertEquals(preg_match($pattern, ' \\{namespace fblubb = Tx_Fluid_Bla3_Blubb }'), 0, 'The SCAN_PATTERN_NAMESPACEDECLARATION pattern did match a namespace declaration even if it was escaped. (1)');
        $this->assertEquals(preg_match($pattern, '\\{namespace typo3 = Tx_TYPO3_Bla3_Blubb }'), 0, 'The SCAN_PATTERN_NAMESPACEDECLARATION pattern did match a namespace declaration even if it was escaped. (2)');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_NAMESPACEDECLARATION()
    {
        $pattern = str_replace('FLUID_NAMESPACE_SEPARATOR', preg_quote(\TYPO3\CMS\Fluid\Fluid::NAMESPACE_SEPARATOR), \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SCAN_PATTERN_NAMESPACEDECLARATION);
        $this->assertEquals(preg_match($pattern, '{namespace acme=Acme.MyPackage\Bla\blubb}'), 1, 'The SCAN_PATTERN_NAMESPACEDECLARATION pattern did not match a namespace declaration (1).');
        $this->assertEquals(preg_match($pattern, '{namespace acme=Acme.MyPackage\Bla\Blubb }'), 1, 'The SCAN_PATTERN_NAMESPACEDECLARATION pattern did not match a namespace declaration (2).');
        $this->assertEquals(preg_match($pattern, '    {namespace foo = Foo\Bla3\Blubb }    '), 1, 'The SCAN_PATTERN_NAMESPACEDECLARATION pattern did not match a namespace declaration (3).');
        $this->assertEquals(preg_match($pattern, ' \\{namespace fblubb = TYPO3.Fluid\Bla3\Blubb }'), 0, 'The SCAN_PATTERN_NAMESPACEDECLARATION pattern did match a namespace declaration even if it was escaped. (1)');
        $this->assertEquals(preg_match($pattern, '\\{namespace typo3 = TYPO3.TYPO3\Bla3\Blubb }'), 0, 'The SCAN_PATTERN_NAMESPACEDECLARATION pattern did match a namespace declaration even if it was escaped. (2)');
    }

    /**
     * @test
     */
    public function testSPLIT_PATTERN_DYNAMICTAGS()
    {
        $pattern = $this->insertNamespaceIntoRegularExpression(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SPLIT_PATTERN_TEMPLATE_DYNAMICTAGS, ['typo3', 't3', 'f']);

        $source = '<html><head> <f:a.testing /> <f:blablubb> {testing}</f4:blz> </t3:hi.jo>';
        $expected = ['<html><head> ', '<f:a.testing />', ' ', '<f:blablubb>', ' {testing}</f4:blz> ', '</t3:hi.jo>'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_DYNAMICTAGS pattern did not split the input string correctly with simple tags.');

        $source = 'hi<f:testing attribute="Hallo>{yep}" nested:attribute="jup" />ja';
        $expected = ['hi', '<f:testing attribute="Hallo>{yep}" nested:attribute="jup" />', 'ja'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_DYNAMICTAGS pattern did not split the input string correctly with  > inside an attribute.');

        $source = 'hi<f:testing attribute="Hallo\\"{yep}" nested:attribute="jup" />ja';
        $expected = ['hi', '<f:testing attribute="Hallo\\"{yep}" nested:attribute="jup" />', 'ja'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_DYNAMICTAGS pattern did not split the input string correctly if a " is inside a double-quoted string.');

        $source = 'hi<f:testing attribute=\'Hallo>{yep}\' nested:attribute="jup" />ja';
        $expected = ['hi', '<f:testing attribute=\'Hallo>{yep}\' nested:attribute="jup" />', 'ja'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_DYNAMICTAGS pattern did not split the input string correctly with single quotes as attribute delimiters.');

        $source = 'hi<f:testing attribute=\'Hallo\\\'{yep}\' nested:attribute="jup" />ja';
        $expected = ['hi', '<f:testing attribute=\'Hallo\\\'{yep}\' nested:attribute="jup" />', 'ja'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_DYNAMICTAGS pattern did not split the input string correctly if \' is inside a single-quoted attribute.');

        $source = 'Hallo <f:testing><![CDATA[<f:notparsed>]]></f:testing>';
        $expected = ['Hallo ', '<f:testing>', '<![CDATA[<f:notparsed>]]>', '</f:testing>'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_DYNAMICTAGS pattern did not split the input string correctly if there is a CDATA section the parser should ignore.');

        $veryLongViewHelper ='<f:form enctype="multipart/form-data" onsubmit="void(0)" onreset="void(0)" action="someAction" arguments="{arg1: \'val1\', arg2: \'val2\'}" controller="someController" package="YourCompanyName.somePackage" subpackage="YourCompanyName.someSubpackage" section="someSection" format="txt" additionalParams="{param1: \'val1\', param2: \'val2\'}" absolute="true" addQueryString="true" argumentsToBeExcludedFromQueryString="{0: \'foo\'}" />';
        $source = $veryLongViewHelper . 'Begin' . $veryLongViewHelper . 'End';
        $expected = [$veryLongViewHelper, 'Begin', $veryLongViewHelper, 'End'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_DYNAMICTAGS pattern did not split the input string correctly if the VH has lots of arguments.');

        $source = '<f:a.testing data-bar="foo"> <f:a.testing>';
        $expected = ['<f:a.testing data-bar="foo">', ' ', '<f:a.testing>'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_DYNAMICTAGS pattern did not split the input string correctly with data- attribute.');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_DYNAMICTAG()
    {
        $pattern = $this->insertNamespaceIntoRegularExpression(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SCAN_PATTERN_TEMPLATE_VIEWHELPERTAG, ['f']);
        $source = '<f:crop attribute="Hallo">';
        $expected = [
            0 => '<f:crop attribute="Hallo">',
            'NamespaceIdentifier' => 'f',
            1 => 'f',
            'MethodIdentifier' => 'crop',
            2 => 'crop',
            'Attributes' => ' attribute="Hallo"',
            3 => ' attribute="Hallo"',
            'Selfclosing' => '',
            4 => ''
        ];
        preg_match($pattern, $source, $matches);
        $this->assertEquals($expected, $matches, 'The SCAN_PATTERN_DYNAMICTAG does not match correctly.');

        $pattern = $this->insertNamespaceIntoRegularExpression(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SCAN_PATTERN_TEMPLATE_VIEWHELPERTAG, ['f']);
        $source = '<f:crop data-attribute="Hallo">';
        $expected = [
            0 => '<f:crop data-attribute="Hallo">',
            'NamespaceIdentifier' => 'f',
            1 => 'f',
            'MethodIdentifier' => 'crop',
            2 => 'crop',
            'Attributes' => ' data-attribute="Hallo"',
            3 => ' data-attribute="Hallo"',
            'Selfclosing' => '',
            4 => ''
        ];
        preg_match($pattern, $source, $matches);
        $this->assertEquals($expected, $matches, 'The SCAN_PATTERN_DYNAMICTAG does not match correctly with data- attributes.');

        $source = '<f:base />';
        $expected = [
            0 => '<f:base />',
            'NamespaceIdentifier' => 'f',
            1 => 'f',
            'MethodIdentifier' => 'base',
            2 => 'base',
            'Attributes' => '',
            3 => '',
            'Selfclosing' => '/',
            4 => '/'
        ];
        preg_match($pattern, $source, $matches);
        $this->assertEquals($expected, $matches, 'The SCAN_PATTERN_DYNAMICTAG does not match correctly when there is a space before the self-closing tag.');

        $source = '<f:crop attribute="Ha\\"llo"/>';
        $expected = [
            0 => '<f:crop attribute="Ha\\"llo"/>',
            'NamespaceIdentifier' => 'f',
            1 => 'f',
            'MethodIdentifier' => 'crop',
            2 => 'crop',
            'Attributes' => ' attribute="Ha\\"llo"',
            3 => ' attribute="Ha\\"llo"',
            'Selfclosing' => '/',
            4 => '/'
        ];
        preg_match($pattern, $source, $matches);
        $this->assertEquals($expected, $matches, 'The SCAN_PATTERN_DYNAMICTAG does not match correctly with self-closing tags.');

        $source = '<f:link.uriTo complex:attribute="Ha>llo" a="b" c=\'d\'/>';
        $expected = [
            0 => '<f:link.uriTo complex:attribute="Ha>llo" a="b" c=\'d\'/>',
            'NamespaceIdentifier' => 'f',
            1 => 'f',
            'MethodIdentifier' => 'link.uriTo',
            2 => 'link.uriTo',
            'Attributes' => ' complex:attribute="Ha>llo" a="b" c=\'d\'',
            3 => ' complex:attribute="Ha>llo" a="b" c=\'d\'',
            'Selfclosing' => '/',
            4 => '/'
        ];
        preg_match($pattern, $source, $matches);
        $this->assertEquals($expected, $matches, 'The SCAN_PATTERN_DYNAMICTAG does not match correctly with complex attributes and > inside the attributes.');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_CLOSINGDYNAMICTAG()
    {
        $pattern = $this->insertNamespaceIntoRegularExpression(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SCAN_PATTERN_TEMPLATE_CLOSINGVIEWHELPERTAG, ['f']);
        $this->assertEquals(preg_match($pattern, '</f:bla>'), 1, 'The SCAN_PATTERN_CLOSINGDYNAMICTAG does not match a tag it should match.');
        $this->assertEquals(preg_match($pattern, '</f:bla.a    >'), 1, 'The SCAN_PATTERN_CLOSINGDYNAMICTAG does not match a tag (with spaces included) it should match.');
        $this->assertEquals(preg_match($pattern, '</t:bla>'), 0, 'The SCAN_PATTERN_CLOSINGDYNAMICTAG does match match a tag it should not match.');
    }

    /**
     * @test
     */
    public function testSPLIT_PATTERN_TAGARGUMENTS()
    {
        $pattern = \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SPLIT_PATTERN_TAGARGUMENTS;
        $source = ' test="Hallo" argument:post="\'Web" other=\'Single"Quoted\' data-foo="bar"';
        $this->assertEquals(preg_match_all($pattern, $source, $matches, PREG_SET_ORDER), 4, 'The SPLIT_PATTERN_TAGARGUMENTS does not match correctly.');
        $this->assertEquals('data-foo', $matches[3]['Argument']);
    }

    /**
     * @test
     */
    public function testSPLIT_PATTERN_SHORTHANDSYNTAX()
    {
        $pattern = $this->insertNamespaceIntoRegularExpression(\TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SPLIT_PATTERN_SHORTHANDSYNTAX, ['f']);

        $source = 'some string{Object.bla}here as well';
        $expected = ['some string', '{Object.bla}', 'here as well'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_SHORTHANDSYNTAX pattern did not split the input string correctly with a simple example.');

        $source = 'some {}string\\{Object.bla}here as well';
        $expected = ['some {}string\\', '{Object.bla}', 'here as well'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_SHORTHANDSYNTAX pattern did not split the input string correctly with an escaped example. (1)');

        $source = 'some {f:viewHelper()} as well';
        $expected = ['some ', '{f:viewHelper()}', ' as well'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_SHORTHANDSYNTAX pattern did not split the input string correctly with an escaped example. (2)');

        $source = 'abc {f:for(arg1: post)} def';
        $expected = ['abc ', '{f:for(arg1: post)}', ' def'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_SHORTHANDSYNTAX pattern did not split the input string correctly with an escaped example.(3)');

        $source = 'abc {bla.blubb->f:for(param:42)} def';
        $expected = ['abc ', '{bla.blubb->f:for(param:42)}', ' def'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_SHORTHANDSYNTAX pattern did not split the input string correctly with an escaped example.(4)');

        $source = 'abc {f:for(bla:"post{{")} def';
        $expected = ['abc ', '{f:for(bla:"post{{")}', ' def'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_SHORTHANDSYNTAX pattern did not split the input string correctly with an escaped example.(5)');

        $source = 'abc {f:for(param:"abc\\"abc")} def';
        $expected = ['abc ', '{f:for(param:"abc\\"abc")}', ' def'];
        $this->assertEquals(preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY), $expected, 'The SPLIT_PATTERN_SHORTHANDSYNTAX pattern did not split the input string correctly with an escaped example.(6)');
    }

    /**
     * @test
     */
    public function testSPLIT_PATTERN_SHORTHANDSYNTAX_VIEWHELPER()
    {
        $pattern = \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SPLIT_PATTERN_SHORTHANDSYNTAX_VIEWHELPER;

        $source = 'f:for(each: bla)';
        $expected = [
            0 => [
                0 => 'f:for(each: bla)',
                1 => 'f',
                'NamespaceIdentifier' => 'f',
                2 => 'for',
                'MethodIdentifier' => 'for',
                3 => 'each: bla',
                'ViewHelperArguments' => 'each: bla'
            ]
        ];
        preg_match_all($pattern, $source, $matches, PREG_SET_ORDER);
        $this->assertEquals($matches, $expected, 'The SPLIT_PATTERN_SHORTHANDSYNTAX_VIEWHELPER');

        $source = 'f:for(each: bla)->g:bla(a:"b\\"->(f:a()", cd: {a:b})';
        $expected = [
            0 => [
                0 => 'f:for(each: bla)',
                1 => 'f',
                'NamespaceIdentifier' => 'f',
                2 => 'for',
                'MethodIdentifier' => 'for',
                3 => 'each: bla',
                'ViewHelperArguments' => 'each: bla'
            ],
            1 => [
                0 => 'g:bla(a:"b\\"->(f:a()", cd: {a:b})',
                1 => 'g',
                'NamespaceIdentifier' => 'g',
                2 => 'bla',
                'MethodIdentifier' => 'bla',
                3 => 'a:"b\\"->(f:a()", cd: {a:b}',
                'ViewHelperArguments' => 'a:"b\\"->(f:a()", cd: {a:b}'
            ]
        ];
        preg_match_all($pattern, $source, $matches, PREG_SET_ORDER);
        $this->assertEquals($matches, $expected, 'The SPLIT_PATTERN_SHORTHANDSYNTAX_VIEWHELPER');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_SHORTHANDSYNTAX_OBJECTACCESSORS()
    {
        $pattern = \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SCAN_PATTERN_SHORTHANDSYNTAX_OBJECTACCESSORS;
        $this->assertEquals(preg_match($pattern, '{object}'), 1, 'Object accessor not identified!');
        $this->assertEquals(preg_match($pattern, '{oBject1}'), 1, 'Object accessor not identified if there is a number and capitals inside!');
        $this->assertEquals(preg_match($pattern, '{object.recursive}'), 1, 'Object accessor not identified if there is a dot inside!');
        $this->assertEquals(preg_match($pattern, '{object-with-dash.recursive_value}'), 1, 'Object accessor not identified if there is a _ or - inside!');
        $this->assertEquals(preg_match($pattern, '{f:for()}'), 1, 'Object accessor not identified if it contains only of a ViewHelper.');
        $this->assertEquals(preg_match($pattern, '{f:for()->f:for2()}'), 1, 'Object accessor not identified if it contains only of a ViewHelper (nested).');
        $this->assertEquals(preg_match($pattern, '{abc->f:for()}'), 1, 'Object accessor not identified if there is a ViewHelper inside!');
        $this->assertEquals(preg_match($pattern, '{bla-blubb.recursive_value->f:for()->f:for()}'), 1, 'Object accessor not identified if there is a recursive ViewHelper inside!');
        $this->assertEquals(preg_match($pattern, '{f:for(arg1:arg1value, arg2: "bla\\"blubb")}'), 1, 'Object accessor not identified if there is an argument inside!');
        $this->assertEquals(preg_match($pattern, '{dash:value}'), 0, 'Object accessor identified, but was array!');
        //$this->assertEquals(preg_match($pattern, '{}'), 0, 'Object accessor identified, and it was empty!');
    }

    /**
     * @test
     */
    public function testSCAN_PATTERN_SHORTHANDSYNTAX_ARRAYS()
    {
        $pattern = \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SCAN_PATTERN_SHORTHANDSYNTAX_ARRAYS;
        $this->assertEquals(preg_match($pattern, '{a:b}'), 1, 'Array syntax not identified!');
        $this->assertEquals(preg_match($pattern, '{a:b, c :   d}'), 1, 'Array syntax not identified in case there are multiple properties!');
        $this->assertEquals(preg_match($pattern, '{a : 123}'), 1, 'Array syntax not identified when a number is passed as argument!');
        $this->assertEquals(preg_match($pattern, '{a:"String"}'), 1, 'Array syntax not identified in case of a double quoted string!');
        $this->assertEquals(preg_match($pattern, '{a:\'String\'}'), 1, 'Array syntax not identified in case of a single quoted string!');

        $expected = '{a:{bla:{x:z}, b: a}}';
        preg_match($pattern, $expected, $match);
        $this->assertEquals($match[0], $expected, 'If nested arrays appear, the string is not parsed correctly.');

        $expected = '{a:"{bla{{}"}';
        preg_match($pattern, $expected, $match);
        $this->assertEquals($match[0], $expected, 'If nested strings with {} inside appear, the string is not parsed correctly.');
    }

    /**
     * @test
     */
    public function testSPLIT_PATTERN_SHORTHANDSYNTAX_ARRAY_PARTS()
    {
        $pattern = \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SPLIT_PATTERN_SHORTHANDSYNTAX_ARRAY_PARTS;

        $source = '{a: b, e: {c:d, e:f}}';
        preg_match_all($pattern, $source, $matches, PREG_SET_ORDER);

        $expected = [
            0 => [
                0 => 'a: b',
                'ArrayPart' => 'a: b',
                1 => 'a: b',
                'Key' => 'a',
                2 => 'a',
                'QuotedString' => '',
                3 => '',
                'VariableIdentifier' => 'b',
                4 => 'b'
            ],
            1 => [
                0 => 'e: {c:d, e:f}',
                'ArrayPart' => 'e: {c:d, e:f}',
                1 => 'e: {c:d, e:f}',
                'Key' => 'e',
                2 => 'e',
                'QuotedString' => '',
                3 => '',
                'VariableIdentifier' => '',
                4 => '',
                'Number' => '',
                5 => '',
                'Subarray' => 'c:d, e:f',
                6 => 'c:d, e:f'
            ]
        ];
        $this->assertEquals($matches, $expected, 'The regular expression splitting the array apart does not work!');
    }

    /**
     * Test the SCAN_PATTERN_CDATA which should detect <![CDATA[...]]> (with no leading or trailing spaces!)
     *
     * @test
     */
    public function testSCAN_PATTERN_CDATA()
    {
        $pattern = \TYPO3\CMS\Fluid\Core\Parser\TemplateParser::$SCAN_PATTERN_CDATA;
        $this->assertEquals(preg_match($pattern, '<!-- Test -->'), 0, 'The SCAN_PATTERN_CDATA matches a comment, but it should not.');
        $this->assertEquals(preg_match($pattern, '<![CDATA[This is some ]]>'), 1, 'The SCAN_PATTERN_CDATA does not match a simple CDATA string.');
        $this->assertEquals(preg_match($pattern, '<![CDATA[This is<bla:test> some ]]>'), 1, 'The SCAN_PATTERN_CDATA does not match a CDATA string with tags inside..');
    }

    /**
     * Helper method which replaces NAMESPACE in the regular expression with the real namespace used.
     *
     * @param string $regularExpression The regular expression in which to replace NAMESPACE
     * @param array $namespace List of namespace identifiers.
     * @return string working regular expression with NAMESPACE replaced.
     */
    protected function insertNamespaceIntoRegularExpression($regularExpression, $namespace)
    {
        return str_replace('NAMESPACE', implode('|', $namespace), $regularExpression);
    }
}
