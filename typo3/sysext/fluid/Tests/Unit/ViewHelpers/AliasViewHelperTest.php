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

use TYPO3\CMS\Fluid\ViewHelpers\AliasViewHelper;

/**
 * Test case
 */
class AliasViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var AliasViewHelper
     */
    protected $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMock(AliasViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->subject);
    }

    /**
     * @test
     */
    public function renderAddsSingleValueToTemplateVariableContainerAndRemovesItAfterRendering()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('someAlias', 'someValue');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('someAlias');
        $this->subject->render(['someAlias' => 'someValue']);
    }

    /**
     * @test
     */
    public function renderAddsMultipleValuesToTemplateVariableContainerAndRemovesThemAfterRendering()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('someAlias', 'someValue');
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('someOtherAlias', 'someOtherValue');
        $this->templateVariableContainer->expects($this->at(2))->method('remove')->with('someAlias');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('someOtherAlias');
        $this->subject->render(['someAlias' => 'someValue', 'someOtherAlias' => 'someOtherValue']);
    }

    /**
     * @test
     */
    public function renderDoesNotTouchTemplateVariableContainerAndReturnsChildNodesIfMapIsEmpty()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
        $this->templateVariableContainer->expects($this->never())->method('add');
        $this->templateVariableContainer->expects($this->never())->method('remove');
        $this->assertEquals('foo', $this->subject->render([]));
    }
}
