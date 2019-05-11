<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\Element;

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

use TYPO3\CMS\Backend\Form\Element\InputHiddenElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for InputHiddenElement Form
 */
class InputHiddenElementTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderReturnsElementsAsAdditionalHiddenFields()
    {
        $data = [
                'parameterArray' => [
                'itemFormElName' => 'foo',
                'itemFormElValue' => 'bar'
            ]
        ];
        $subject = $this->getAccessibleMock(InputHiddenElement::class, ['dummy'], [], '', false);
        $subject->_set('data', $data);
        $result = $subject->render();
        $additionalHiddenFieldsResult = array_pop($result['additionalHiddenFields']);
        $this->assertStringContainsString('name="foo"', $additionalHiddenFieldsResult);
        $this->assertStringContainsString('value="bar"', $additionalHiddenFieldsResult);
        $this->assertStringContainsString('type="hidden"', $additionalHiddenFieldsResult);
    }
}
