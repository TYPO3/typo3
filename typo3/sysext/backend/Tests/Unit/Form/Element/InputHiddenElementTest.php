<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Element;

use TYPO3\CMS\Backend\Form\Element\InputHiddenElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for InputHiddenElement Form
 */
final class InputHiddenElementTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderReturnsElementsAsAdditionalHiddenFields(): void
    {
        $data = [
            'parameterArray' => [
                'itemFormElName' => 'foo',
                'itemFormElValue' => 'bar',
            ],
        ];
        $subject = new InputHiddenElement();
        $subject->setData($data);
        $result = $subject->render();
        $additionalHiddenFieldsResult = array_pop($result['additionalHiddenFields']);
        self::assertStringContainsString('name="foo"', $additionalHiddenFieldsResult);
        self::assertStringContainsString('value="bar"', $additionalHiddenFieldsResult);
        self::assertStringContainsString('type="hidden"', $additionalHiddenFieldsResult);
    }
}
