<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

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
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlentitiesViewHelper;

/**
 * Test case
 */
class HtmlentitiesViewHelperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var ReflectionService|ObjectProphecy
     */
    protected $reflectionServiceProphecy;

    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlentitiesViewHelper
     */
    protected $viewHelper;

    /**
     * shortcut for default Arguments which would be prepared by initializeArguments()
     *
     * @var array
     */
    protected $defaultArguments;

    protected function setUp()
    {
        $this->reflectionServiceProphecy = $this->prophesize(ReflectionService::class);
        $this->viewHelper = new HtmlentitiesViewHelper();
        $this->viewHelper->injectReflectionService($this->reflectionServiceProphecy->reveal());
    }

    /**
     * @test
     */
    public function renderUsesValueAsSourceIfSpecified()
    {
        $this->setArgumentsUnderTest(
            [
                'value' => 'Some string',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('Some string', $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'Some string';
            }
        );
        $this->setArgumentsUnderTest();
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals('Some string', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters()
    {
        $source = 'This is a sample text without special characters.';
        $this->setArgumentsUnderTest(
            [
                'value' => $source,
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertSame($source, $actualResult);
    }

    /**
     * @test
     */
    public function renderDecodesSimpleString()
    {
        $source = 'Some special characters: &©"\'';
        $this->setArgumentsUnderTest(
            ['value' => $source]
        );
        $expectedResult = 'Some special characters: &amp;&copy;&quot;\'';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsKeepQuoteArgument()
    {
        $source = 'Some special characters: &©"\'';
        $this->setArgumentsUnderTest(
            [
                'value' => $source,
                'keepQuotes' => true,
            ]
        );
        $expectedResult = 'Some special characters: &amp;&copy;"\'';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsEncodingArgument()
    {
        $source = utf8_decode('Some special characters: &©"\'');
        $this->setArgumentsUnderTest(
            [
                'value' => $source,
                'encoding' => 'ISO-8859-1',
            ]
        );
        $expectedResult = 'Some special characters: &amp;&copy;&quot;\'';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderConvertsAlreadyConvertedEntitiesByDefault()
    {
        $source = 'already &quot;encoded&quot;';
        $this->setArgumentsUnderTest(
            ['value' => $source]
        );
        $expectedResult = 'already &amp;quot;encoded&amp;quot;';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotConvertAlreadyConvertedEntitiesIfDoubleQuoteIsFalse()
    {
        $source = 'already &quot;encoded&quot;';
        $this->setArgumentsUnderTest(
            [
                'value' => $source,
                'doubleEncode' => false,
            ]
        );
        $expectedResult = 'already &quot;encoded&quot;';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * This test tests the behaviour of render without relying on the validation of registerArguments
     * In the normal course of things "value" can't be anything but a string as it is registered that way
     *
     * @test
     */
    public function renderReturnsUnmodifiedSourceIfItIsNoString()
    {
        $source = new \stdClass();
        $this->setArgumentsUnderTest(
            ['value' => $source]
        );
        $actualResult = $this->viewHelper->render();
        $this->assertSame($source, $actualResult);
    }

    /**
     * Helper function to merge arguments with default arguments according to their registration
     * This usually happens in ViewHelperInvoker before the view helper methods are called
     *
     * @param array $arguments
     */
    protected function setArgumentsUnderTest(array $arguments = [])
    {
        $argumentDefinitions = $this->viewHelper->prepareArguments();
        foreach ($argumentDefinitions as $argumentName => $argumentDefinition) {
            if (!isset($arguments[$argumentName])) {
                $arguments[$argumentName] = $argumentDefinition->getDefaultValue();
            }
        }
        $this->viewHelper->setArguments($arguments);
    }
}
