<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

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

use TYPO3\CMS\Fluid\ViewHelpers\Form\HiddenViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the "Hidden" Form view helper
 */
class HiddenViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\HiddenViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(HiddenViewHelper::class, ['setErrorClassAttribute', 'getName', 'getValueAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $tagBuilder = $this->prophesize(TagBuilder::class);
        $tagBuilder->render()->shouldBeCalled();
        // @todo remove condition once typo3fluid/fluid version 2.6.0 will be the minimum version
        if (class_exists(\TYPO3Fluid\Fluid\ViewHelpers\InlineViewHelper::class)) {
            $tagBuilder->reset()->shouldBeCalled();
        }
        $tagBuilder->addAttribute('type', 'hidden')->shouldBeCalled();
        $tagBuilder->addAttribute('name', 'foo')->shouldBeCalled();
        $tagBuilder->addAttribute('value', 'bar')->shouldBeCalled();
        $tagBuilder->setTagName('input')->shouldBeCalled();
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');

        $this->viewHelper->expects($this->once())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->once())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->setTagBuilder($tagBuilder->reveal());

        $this->viewHelper->initializeArgumentsAndRender();
    }
}
