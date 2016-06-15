<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Parser\PreProcessor;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Parser\PreProcessor\XmlnsNamespaceTemplatePreProcessor;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolver;
use TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture;

/**
 * Class XmlnsNamespaceTemplatePreProcessorTest
 */
class XmlnsNamespaceTemplatePreProcessorTest extends UnitTestCase
{
    /**
     * @param string $source
     * @param array $expectedNamespaces
     * @param string $expectedSource
     * @test
     * @dataProvider preProcessSourceExtractsNamespacesAndRemovesTagsAndAttributesDataProvider
     */
    public function preProcessSourceExtractsNamespacesAndRemovesTagsAndAttributes($source, array $expectedNamespaces, $expectedSource)
    {
        $subject = new XmlnsNamespaceTemplatePreProcessor();
        $resolver = $this->getMockBuilder(ViewHelperResolver::class)
            ->setMethods(array('addNamespace'))
            ->getMock();
        $context = $this->getMockBuilder(RenderingContextFixture::class)
            ->setMethods(array('getViewHelperResolver'))
            ->getMock();
        if (empty($expectedNamespaces)) {
            $context->expects($this->never())->method('getViewHelperResolver');
            $resolver->expects($this->never())->method('addNamespace');
        } else {
            $context->expects($this->exactly(count($expectedNamespaces)))->method('getViewHelperResolver')->willReturn($resolver);
            foreach ($expectedNamespaces as $index => $expectedNamespaceParts) {
                list($prefix, $phpNamespace) = $expectedNamespaceParts;
                $resolver->expects($this->at($index))->method('addNamespace')->with($prefix, $phpNamespace);
            }
        }
        $subject->setRenderingContext($context);
        $result = $subject->preProcessSource($source);
        if ($expectedSource === null) {
            $this->assertEquals($source, $result);
        } else {
            $this->assertEquals($expectedSource, $result);
        }
    }

    /**
     * DataProvider for preProcessSourceExtractsNamespacesAndRemovesTagsAndAttributes test
     *
     * @return array
     */
    public function preProcessSourceExtractsNamespacesAndRemovesTagsAndAttributesDataProvider()
    {
        return [
            'Empty source raises no errors' => array(
                '',
                [],
                null,
            ),
            'Tags without xmlns remain untouched' => array(
                '<div class="not-touched">...</div>',
                [],
                null
            ),
            'Third-party namespace not detected' => array(
                '<html xmlns:notdetected="http://thirdparty.org/ns/Foo/Bar/ViewHelpers">...</html>',
                [],
                null
            ),
            'Detects and removes Fluid namespaces by namespace URL' => array(
                '<html xmlns:detected="http://typo3.org/ns/Foo/Bar/ViewHelpers" data-namespace-typo3-fluid="true">...</html>',
                [
                    ['detected', 'Foo\\Bar\\ViewHelpers']
                ],
                '...'
            ),
            'Skips fluid namespace if namespace URL is not the correct case' => array(
                '<html xmlns:detected="http://typo3.org/Ns/Foo/Bar/ViewHelpers" data-namespace-typo3-fluid="true">...</html>',
                [],
                null
            ),
            'Skips fluid namespace if attribute is not the correct case' => array(
                '<html xmlNS:detected="http://typo3.org/ns/Foo/Bar/ViewHelpers" data-namespace-typo3-fluid="true">...</html>',
                [],
                null
            ),
            'Skips namespace if attribute before xmlns attribute without space in between' => array(
                '<html lang="de"xmlns:detected="http://typo3.org/ns/Foo/Bar/ViewHelpers" data-namespace-typo3-fluid="true">...</html>',
                [],
                null
            ),
            'Removes tag if data attribute set and non xmlns attributes are used prior to xmlns' => array(
                '<html lang="de" xmlns:detected="http://typo3.org/ns/Foo/Bar/ViewHelpers" data-namespace-typo3-fluid="true">...</html>',
                [
                    ['detected', 'Foo\\Bar\\ViewHelpers']
                ],
                '...'
            ),
            'Skips invalid namespace prefixes' => array(
                '<html xmlns:bad-prefix="http://typo3.org/ns/Foo/Bar/ViewHelpers">...</html>',
                [],
                null
            ),
            'Detect and remove multiple ViewHelper attributes' => array(
                '<div xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"' . "\n"
                    . "\t" . 'xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers">' . "\n"
                        . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</div>',
                [
                    ['f', 'TYPO3\\CMS\\Fluid\\ViewHelpers'],
                    ['fe', 'TYPO3\\CMS\\Frontend\\ViewHelpers']
                ],
                '<div >' . "\n"
                    . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</div>'
            ),
            'ViewHelpers found with non ViewHelper xmlns at beginning' => array(
                '<div xmlns:z="http://www.typo3.org/foo"' . "\n"
                    . "\t" . 'xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"' . "\n"
                    . "\t" . 'xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers">' . "\n"
                        . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</div>',
                [
                    ['f', 'TYPO3\\CMS\\Fluid\\ViewHelpers'],
                    ['fe', 'TYPO3\\CMS\\Frontend\\ViewHelpers']
                ],
                '<div xmlns:z="http://www.typo3.org/foo" >' . "\n"
                    . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</div>'
            ),
            'ViewHelpers found with non ViewHelper xmlns at end' => array(
                '<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"' . "\n"
                    . "\t" . 'xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"' . "\n"
                    . "\t" . 'xmlns:z="http://www.typo3.org/foo">' . "\n"
                        . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</div>',
                [
                    ['fe', 'TYPO3\\CMS\\Frontend\\ViewHelpers'],
                    ['f', 'TYPO3\\CMS\\Fluid\\ViewHelpers']
                ],
                '<div xmlns:z="http://www.typo3.org/foo">' . "\n"
                    . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</div>',
            ),
            'Xmlns ViewHelpers found with multiple non ViewHelper xmlns attributes' => array(
                '<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"' . "\n"
                    . "\t" . 'xmlns:y="http://www.typo3.org/bar"' . "\n"
                    . "\t" . 'xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"' . "\n"
                    . "\t" . 'xmlns:z="http://www.typo3.org/foo">' . "\n"
                        . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</div>',
                [
                    ['fe', 'TYPO3\\CMS\\Frontend\\ViewHelpers'],
                    ['f', 'TYPO3\\CMS\\Fluid\\ViewHelpers']
                ],
                '<div xmlns:y="http://www.typo3.org/bar" xmlns:z="http://www.typo3.org/foo">' . "\n"
                    . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</div>'
            ),
            'Xmlns ViewHelpers found with non ViewHelpers between' => array(
                '<div xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"' . "\n"
                . "\t" . 'xmlns:z="http://www.typo3.org/foo"' . "\n"
                . "\t" . 'xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">' . "\n"
                    . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</div>',
                [
                    ['fe', 'TYPO3\\CMS\\Frontend\\ViewHelpers'],
                    ['f', 'TYPO3\\CMS\\Fluid\\ViewHelpers']
                ],
                '<div xmlns:z="http://www.typo3.org/foo" >' . "\n"
                    . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</div>'
            ),
            'Do not remove Html tag with data attribute but no xmlns ViewHelpers found' => array(
                '<html data-namespace-typo3-fluid="true">' . "\n"
                    . "\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</html>',
                [],
                null
            ),
            'Keep html tag if data attribute is not set and remove ViewHelper attributes' => array(
                '<html xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"' . "\n"
                    . "\t" . 'xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"' . "\n"
                    . "\t" . 'xmlns:z="http://www.typo3.org/foo">' . "\n"
                        . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</html>',
                [
                    ['fe', 'TYPO3\\CMS\\Frontend\\ViewHelpers'],
                    ['f', 'TYPO3\\CMS\\Fluid\\ViewHelpers']
                ],
                '<html xmlns:z="http://www.typo3.org/foo">' . "\n"
                    . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</html>',
            ),
            'Remove html tag because xmlns ViewHelpers found and data attribute set' => array(
                '<html data-namespace-typo3-fluid="true"' . "\n"
                    . "\t" . 'xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"' . "\n"
                    . "\t" . 'xmlns:z="http://www.typo3.org/foo">' . "\n"
                        . "\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                . '</html>',
                [
                    ['fe', 'TYPO3\\CMS\\Frontend\\ViewHelpers']
                ],
                "\n\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
            ),
            'Test with big markup template' => array(
                file_get_contents(GeneralUtility::getFileAbsFileName('EXT:fluid/Tests/Unit/Core/Fixtures/TestNamespaceTemplateBig.html')),
                [
                    ['f', 'TYPO3\\CMS\\Fluid\\ViewHelpers'],
                    ['core', 'TYPO3\\CMS\\Core\\ViewHelpers'],
                    ['fl', 'TYPO3\\CMS\\Filelist\\ViewHelpers']
                ],
                file_get_contents(GeneralUtility::getFileAbsFileName('EXT:fluid/Tests/Unit/Core/Fixtures/TestNamespaceTemplateBigExpectedResult.html'))
            ),
            'Only handle first tag with xmlns ViewHelpers found' => array(
                '<div xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers">' . "\n"
                    . "\t" . '<div data-namespace-typo3-fluid="true" xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"' . "\n"
                        . "\t\t" . 'xmlns:z="http://www.typo3.org/foo">' . "\n"
                            . "\t\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                    . "\t" . '</div>' . "\n"
                . '</div>',
                [
                    ['f', 'TYPO3\\CMS\\Fluid\\ViewHelpers']
                ],
                '<div >' . "\n"
                    . "\t" . '<div data-namespace-typo3-fluid="true" xmlns:fe="http://typo3.org/ns/TYPO3/CMS/Frontend/ViewHelpers"' . "\n"
                        . "\t\t" . 'xmlns:z="http://www.typo3.org/foo">' . "\n"
                            . "\t\t\t" . '<f:if condition="{demo}">Hello World</f:if>' . "\n"
                    . "\t" . '</div>' . "\n"
                . '</div>'
            )
        ];
    }
}
