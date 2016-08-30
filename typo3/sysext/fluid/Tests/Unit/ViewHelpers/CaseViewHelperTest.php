<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for CaseViewHelper
 */
class CaseViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\CaseViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMock(\TYPO3\CMS\Fluid\ViewHelpers\CaseViewHelper::class, ['buildRenderChildrenClosure']);
        $this->viewHelper->expects($this->any())->method('buildRenderChildrenClosure')->will($this->returnValue(function () {
            return 'ChildNodes';
        }));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfSwitchExpressionIsNotSetInViewHelperVariableContainer()
    {
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')->will($this->returnValue(false));
        $this->viewHelper->render('foo');
    }

    /**
     * @test
     */
    public function renderReturnsChildNodesIfTheSpecifiedValueIsEqualToTheSwitchExpression()
    {
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')->will($this->returnValue([[
            'break' => false,
            'expression' => 'someValue'
        ]]));

        $renderedChildNodes = 'ChildNodes';

        $this->assertSame($renderedChildNodes, $this->viewHelper->render('someValue'));
    }

    /**
     * @test
     */
    public function renderSetsBreakStateInViewHelperVariableContainerIfTheSpecifiedValueIsEqualToTheSwitchExpression()
    {
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')->will($this->returnValue([[
            'break' => false,
            'expression' => 'someValue'
        ]]));

        $this->viewHelperVariableContainer->expects($this->once())->method('addOrUpdate')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack', [[
            'break' => true,
            'expression' => 'someValue'
        ]]);

        $this->viewHelper->render('someValue');
    }

    /**
     * @test
     */
    public function renderWeaklyComparesSpecifiedValueWithSwitchExpression()
    {
        $numericValue = 123;
        $stringValue = '123';

        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')->will($this->returnValue([[
            'break' => false,
            'expression' => $numericValue
        ]]));

        $this->viewHelperVariableContainer->expects($this->once())->method('addOrUpdate')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack', [[
            'break' => true,
            'expression' => $numericValue
        ]]);

        $this->viewHelper->render($stringValue);
    }

    /**
     * @test
     */
    public function renderReturnsAnEmptyStringIfTheSpecifiedValueIsNotEqualToTheSwitchExpression()
    {
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')->will($this->returnValue([[
            'break' => false,
            'expression' => 'someValue'
        ]]));

        $this->assertSame('', $this->viewHelper->render('someOtherValue'));
    }

    /**
     * @test
     */
    public function renderReturnsChildNodesIfDefaultIsTrue()
    {
        $this->viewHelperVariableContainer->expects(
            $this->atLeastOnce())
            ->method('exists')
            ->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')
            ->will($this->returnValue(true)
            );
        $this->viewHelperVariableContainer->expects(
            $this->atLeastOnce())->method('get')
            ->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')
            ->will($this->returnValue([[
                'break' => false,
                'expression' => 'someExpression'
            ]])
            );

        $renderedChildNodes = 'ChildNodes';
        $this->viewHelper->expects($this->once())->method('buildRenderChildrenClosure')->will($this->returnValue(function () {
            return 'ChildNodes';
        }));

        $this->assertSame($renderedChildNodes, $this->viewHelper->render(null, true));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfNeighterValueNorDefaultAreGiven()
    {
        $this->viewHelperVariableContainer->expects(
            $this->atLeastOnce())
            ->method('exists')
            ->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')
            ->will($this->returnValue(true)
            );
        $this->viewHelperVariableContainer->expects($this->never())->method('get');

        $this->viewHelper->render(null, false);
    }

    /**
     * @test
     */
    public function renderReturnsChildNodesEvenIfValueIsFalseButDefaultIsTrue()
    {
        $this->viewHelperVariableContainer->expects(
            $this->atLeastOnce())
            ->method('exists')
            ->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')
            ->will($this->returnValue(true)
            );
        $this->viewHelperVariableContainer->expects(
            $this->atLeastOnce())->method('get')
            ->with(\TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper::class, 'stateStack')
            ->will($this->returnValue([[
                'break' => false,
                'expression' => 'someValue'
            ]])
            );

        $renderedChildNodes = 'ChildNodes';

        $this->assertSame($renderedChildNodes, $this->viewHelper->render('someOtherValue', true));
    }
}
