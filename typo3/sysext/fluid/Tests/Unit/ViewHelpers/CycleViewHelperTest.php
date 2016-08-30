<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

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
 * Testcase for CycleViewHelper
 */
class CycleViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\CycleViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMock(\TYPO3\CMS\Fluid\ViewHelpers\CycleViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderAddsCurrentValueToTemplateVariableContainerAndRemovesItAfterRendering()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');

        $values = ['bar', 'Fluid'];
        $this->viewHelper->render($values, 'innerVariable');
    }

    /**
     * @test
     */
    public function renderAddsFirstValueToTemplateVariableContainerAfterLastValue()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'Fluid');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'bar');
        $this->templateVariableContainer->expects($this->at(5))->method('remove')->with('innerVariable');

        $values = ['bar', 'Fluid'];
        $this->viewHelper->render($values, 'innerVariable');
        $this->viewHelper->render($values, 'innerVariable');
        $this->viewHelper->render($values, 'innerVariable');
    }

    /**
     * @test
     */
    public function viewHelperSupportsAssociativeArrays()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'FLOW3');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'Fluid');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'FLOW3');
        $this->templateVariableContainer->expects($this->at(5))->method('remove')->with('innerVariable');

        $values = ['foo' => 'FLOW3', 'bar' => 'Fluid'];
        $this->viewHelper->render($values, 'innerVariable');
        $this->viewHelper->render($values, 'innerVariable');
        $this->viewHelper->render($values, 'innerVariable');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionWhenPassingObjectsToValuesThatAreNotTraversable()
    {
        $object = new \stdClass();

        $this->viewHelper->render($object, 'innerVariable');
    }

    /**
     * @test
     */
    public function renderReturnsChildNodesIfValuesIsNull()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Child nodes'));

        $this->assertEquals('Child nodes', $this->viewHelper->render(null, 'foo'));
    }

    /**
     * @test
     */
    public function renderReturnsChildNodesIfValuesIsAnEmptyArray()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('foo', null);
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('foo');

        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Child nodes'));

        $this->assertEquals('Child nodes', $this->viewHelper->render([], 'foo'));
    }

    /**
     * @test
     */
    public function renderIteratesThroughElementsOfTraversableObjects()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'value1');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'value2');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'value1');
        $this->templateVariableContainer->expects($this->at(5))->method('remove')->with('innerVariable');

        $traversableObject = new \ArrayObject(['key1' => 'value1', 'key2' => 'value2']);
        $this->viewHelper->render($traversableObject, 'innerVariable');
        $this->viewHelper->render($traversableObject, 'innerVariable');
        $this->viewHelper->render($traversableObject, 'innerVariable');
    }
}
