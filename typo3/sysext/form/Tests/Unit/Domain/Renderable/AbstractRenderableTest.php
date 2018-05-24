<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\Renderable;

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

use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;

/**
 * Test case
 */
class AbstractRenderableTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @test
     */
    public function setRenderingOptionSetStringValueIfKeyDoesNotExists()
    {
        $abstractRenderableMock = $this->getMockForAbstractClass(AbstractRenderable::class);

        $expected = ['foo' => 'bar'];
        $abstractRenderableMock->setRenderingOption('foo', 'bar');

        $this->assertSame($expected, $abstractRenderableMock->getRenderingOptions());
    }

    /**
     * @test
     */
    public function setRenderingOptionSetArrayValueIfKeyDoesNotExists()
    {
        $abstractRenderableMock = $this->getMockForAbstractClass(AbstractRenderable::class);

        $expected = ['foo-1' => ['bar' => 'foo-2']];
        $abstractRenderableMock->setRenderingOption('foo-1', ['bar' => 'foo-2']);

        $this->assertSame($expected, $abstractRenderableMock->getRenderingOptions());
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

        $this->assertSame($expected, $abstractRenderableMock->getRenderingOptions());
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

        $this->assertSame($expected, $abstractRenderableMock->getRenderingOptions());
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

        $this->assertSame($expected, $abstractRenderableMock->getRenderingOptions());
    }
}
