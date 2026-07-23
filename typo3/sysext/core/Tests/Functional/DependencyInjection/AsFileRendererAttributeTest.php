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

namespace TYPO3\CMS\Core\Tests\Functional\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestFileRenderer\Rendering\HighPriorityVideoRenderer;
use TYPO3Tests\TestFileRenderer\Rendering\TextRenderer;

final class AsFileRendererAttributeTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_file_renderer',
    ];

    #[Test]
    public function fileRenderersAreRegisteredViaAttribute(): void
    {
        $rendererRegistry = $this->get(RendererRegistry::class);

        self::assertInstanceOf(TextRenderer::class, $rendererRegistry->getRenderer($this->createFileMock('text/plain')));
    }

    #[Test]
    public function fileRendererWithHigherPriorityTakesPrecedenceOverCoreRenderers(): void
    {
        $rendererRegistry = $this->get(RendererRegistry::class);

        self::assertInstanceOf(HighPriorityVideoRenderer::class, $rendererRegistry->getRenderer($this->createFileMock('video/mp4')));
    }

    private function createFileMock(string $mimeType): File
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->method('getMimeType')->willReturn($mimeType);
        return $fileMock;
    }
}
