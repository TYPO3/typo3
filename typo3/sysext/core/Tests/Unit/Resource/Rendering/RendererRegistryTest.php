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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Rendering;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RendererRegistryTest extends UnitTestCase
{
    #[Test]
    public function getRendererReturnsFirstMatchingFileRenderer(): void
    {
        $rendererObject1 = $this->createMock(FileRendererInterface::class);
        $rendererObject1->expects($this->once())->method('canRender')->willReturn(false);
        $rendererObject2 = $this->createMock(FileRendererInterface::class);
        $rendererObject2->expects($this->once())->method('canRender')->willReturn(true);
        $rendererObject3 = $this->createMock(FileRendererInterface::class);
        $rendererObject3->expects($this->never())->method('canRender');

        $fileResourceStub = self::createStub(File::class);

        $rendererRegistry = new RendererRegistry([$rendererObject1, $rendererObject2, $rendererObject3]);

        self::assertSame($rendererObject2, $rendererRegistry->getRenderer($fileResourceStub));
    }

    #[Test]
    public function getRendererReturnsNullIfNoFileRendererMatches(): void
    {
        $rendererObject = $this->createMock(FileRendererInterface::class);
        $rendererObject->method('canRender')->willReturn(false);

        $fileResourceMock = $this->createMock(File::class);

        $rendererRegistry = new RendererRegistry([$rendererObject]);

        self::assertNull($rendererRegistry->getRenderer($fileResourceMock));
    }
}
