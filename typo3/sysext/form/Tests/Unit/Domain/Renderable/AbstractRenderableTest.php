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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Tests\Unit\Domain\Renderable\Fixtures\TestingRenderable;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractRenderableTest extends UnitTestCase
{
    #[Test]
    public function setRenderingOptionSetStringValueIfKeyDoesNotExists(): void
    {
        $renderable = new TestingRenderable();

        $expected = ['foo' => 'bar'];
        $renderable->setRenderingOption('foo', 'bar');

        self::assertSame($expected, $renderable->getRenderingOptions());
    }

    #[Test]
    public function setRenderingOptionSetArrayValueIfKeyDoesNotExists(): void
    {
        $renderable = new TestingRenderable();

        $expected = ['foo-1' => ['bar' => 'foo-2']];
        $renderable->setRenderingOption('foo-1', ['bar' => 'foo-2']);

        self::assertSame($expected, $renderable->getRenderingOptions());
    }

    #[Test]
    public function setRenderingOptionUnsetIfValueIsNull(): void
    {
        $renderable = new TestingRenderable();

        $expected = ['foo-1' => ['bar-1' => 'foo-2']];
        $renderable->setRenderingOption('foo-1', ['bar-1' => 'foo-2']);
        $renderable->setRenderingOption('foo-2', ['bar-2' => 'foo-3']);
        $renderable->setRenderingOption('foo-2', null);

        self::assertSame($expected, $renderable->getRenderingOptions());
    }

    #[Test]
    public function setRenderingOptionUnsetIfValueIsArrayWithSomeNullVales(): void
    {
        $renderable = new TestingRenderable();

        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2',
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
            ],
        ];
        $renderable->setRenderingOption('foo-1', ['bar-1' => 'foo-2']);
        $renderable->setRenderingOption('foo-2', ['bar-2' => 'foo-3', 'bar-3' => 'foo-4']);
        $renderable->setRenderingOption('foo-2', ['bar-3' => null]);

        self::assertSame($expected, $renderable->getRenderingOptions());
    }

    #[Test]
    public function setRenderingOptionAddValueIfValueIsArray(): void
    {
        $renderable = new TestingRenderable();

        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2',
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
                'bar-3' => 'foo-4',
            ],
        ];
        $renderable->setRenderingOption('foo-1', ['bar-1' => 'foo-2']);
        $renderable->setRenderingOption('foo-2', ['bar-2' => 'foo-3']);
        $renderable->setRenderingOption('foo-2', ['bar-3' => 'foo-4']);

        self::assertSame($expected, $renderable->getRenderingOptions());
    }
}
