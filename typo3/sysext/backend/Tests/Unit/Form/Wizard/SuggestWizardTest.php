<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\Wizard;

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

use TYPO3\CMS\Backend\Form\Wizard\SuggestWizard;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Test case
 */
class SuggestWizardTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderSuggestSelectorThrowsExceptionIfFlexFieldDoesNotContainDataStructureIdentifier()
    {
        $viewProphecy = $this->prophesize(StandaloneView::class);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478604742);
        (new SuggestWizard($viewProphecy->reveal()))->renderSuggestSelector(
            'aFieldName',
            'aTable',
            'aField',
            ['uid' => 42],
            [],
            [
                'config' => [
                        'type' => 'flex',
                        // there should be a 'dataStructureIdentifier' here
                ],
            ]
        );
    }
}
