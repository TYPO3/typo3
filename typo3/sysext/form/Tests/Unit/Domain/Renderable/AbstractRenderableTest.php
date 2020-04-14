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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Renderable;

use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractRenderableTest extends UnitTestCase
{

    /**
     * @test
     */
    public function setRenderingOptionSetStringValueIfKeyDoesNotExists()
    {
        $abstractRenderableMock = $this->getMockForAbstractClass(AbstractRenderable::class);

        $expected = ['foo' => 'bar'];
        $abstractRenderableMock->setRenderingOption('foo', 'bar');

        self::assertSame($expected, $abstractRenderableMock->getRenderingOptions());
    }

    /**
     * @test
     */
    public function setRenderingOptionSetArrayValueIfKeyDoesNotExists()
    {
        $abstractRenderableMock = $this->getMockForAbstractClass(AbstractRenderable::class);

        $expected = ['foo-1' => ['bar' => 'foo-2']];
        $abstractRenderableMock->setRenderingOption('foo-1', ['bar' => 'foo-2']);

        self::assertSame($expected, $abstractRenderableMock->getRenderingOptions());
    }

    /**
     * @test
     */
    public function setRenderingOptionUnsetIfValueIsNull()
    {
        $abstractRenderableMock = $this->getMockForAbstractClass(AbstractRenderable::class);

        $expected = ['foo-1' => ['bar-1' => 'foo-2']];
        $abstractRenderableMock->setRenderingOption('foo-1', ['bar-1' => 'foo-2']);
        $abstractRenderableMock->setRenderingOption('foo-2', ['bar-2' => 'foo-3']);
        $abstractRenderableMock->setRenderingOption('foo-2', null);

        self::assertSame($expected, $abstractRenderableMock->getRenderingOptions());
    }

    /**
     * @test
     */
    public function setRenderingOptionUnsetIfValueIsArrayWithSomeNullVales()
    {
        $abstractRenderableMock = $this->getMockForAbstractClass(AbstractRenderable::class);

        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2'
            ],
            'foo-2' => [
                'bar-2' => 'foo-3'
            ]
        ];
        $abstractRenderableMock->setRenderingOption('foo-1', ['bar-1' => 'foo-2']);
        $abstractRenderableMock->setRenderingOption('foo-2', ['bar-2' => 'foo-3', 'bar-3' => 'foo-4']);
        $abstractRenderableMock->setRenderingOption('foo-2', ['bar-3' => null]);

        self::assertSame($expected, $abstractRenderableMock->getRenderingOptions());
    }

    /**
     * @test
     */
    public function setRenderingOptionAddValueIfValueIsArray()
    {
        $abstractRenderableMock = $this->getMockForAbstractClass(AbstractRenderable::class);

        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2'
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
                'bar-3' => 'foo-4'
            ]
        ];
        $abstractRenderableMock->setRenderingOption('foo-1', ['bar-1' => 'foo-2']);
        $abstractRenderableMock->setRenderingOption('foo-2', ['bar-2' => 'foo-3']);
        $abstractRenderableMock->setRenderingOption('foo-2', ['bar-3' => 'foo-4']);

        self::assertSame($expected, $abstractRenderableMock->getRenderingOptions());
    }
}
