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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Renderable;

use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableVariant;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RenderableVariantTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * Set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
    }

    /**
     * @test
     */
    public function formVariantOverridesArrayValues(): void
    {
        $formElementIdentifier = 'form-element-identifier';
        $variantIdentifier = 'variant-identifier';
        $formElementProperties = [
            'options' => [
                1 => 'option 1',
                2 => 'option 2',
                3 => 'option 3',
            ],
        ];
        $variantProperties = [
            'options' => [
                1 => 'option 1',
                2 => 'option 2',
                3 => '__UNSET',
            ],
        ];

        $mockFormElement = $this->getAccessibleMock(GenericFormElement::class, ['dummy'], [], '', false);

        $mockFormElement->_set('type', 'SingleSelect');
        $mockFormElement->_set('identifier', $formElementIdentifier);
        $mockFormElement->_set('label', 'some label');
        $mockFormElement->_set('properties', $formElementProperties);

        $options = [
            'identifier' => $variantIdentifier,
            'label' => 'some label',
            'properties' => $variantProperties
        ];
        $mockVariant = $this->getMockBuilder(RenderableVariant::class)->onlyMethods(['getIdentifier'])->setConstructorArgs([$variantIdentifier, $options, $mockFormElement])->getMock();
        $mockFormElement->addVariant($mockVariant);
        $mockFormElement->applyVariant($mockVariant);

        $expected = [
            'options' => [
                1 => 'option 1',
                2 => 'option 2',
            ],
        ];

        self::assertEquals($expected, $mockFormElement->_get('properties'));
    }
}
