<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

use TYPO3\CMS\Fluid\ViewHelpers\Form\SubmitViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for the "Submit" Form view helper
 */
class SubmitViewHelperTest extends FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\SubmitViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(SubmitViewHelper::class, ['dummy']);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $tagBuilder = $this->prophesize(TagBuilder::class);
        $tagBuilder->render()->shouldBeCalled();
        $tagBuilder->reset()->shouldBeCalled();
        $tagBuilder->addAttribute('type', 'submit')->shouldBeCalled();
        $tagBuilder->addAttribute('value', null)->shouldBeCalled();
        $tagBuilder->setTagName('input')->shouldBeCalled();

        $this->viewHelper->setTagBuilder($tagBuilder->reveal());

        $this->viewHelper->initializeArgumentsAndRender();
    }
}
