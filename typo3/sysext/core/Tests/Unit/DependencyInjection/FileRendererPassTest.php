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

namespace TYPO3\CMS\Core\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TYPO3\CMS\Core\Attribute\AsFileRenderer;
use TYPO3\CMS\Core\DependencyInjection\FileRendererPass;
use TYPO3\CMS\Core\Resource\Rendering\AudioTagRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileRendererPassTest extends UnitTestCase
{
    #[Test]
    public function taggedServiceImplementingFileRendererInterfaceIsAccepted(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(AudioTagRenderer::class);
        $definition->addTag(AsFileRenderer::TAG_NAME);
        $container->setDefinition(AudioTagRenderer::class, $definition);

        (new FileRendererPass(AsFileRenderer::TAG_NAME))->process($container);

        self::assertTrue($container->hasDefinition(AudioTagRenderer::class));
    }

    #[Test]
    public function taggedServiceNotImplementingFileRendererInterfaceThrowsException(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(\stdClass::class);
        $definition->addTag(AsFileRenderer::TAG_NAME);
        $container->setDefinition(\stdClass::class, $definition);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1784818672);

        (new FileRendererPass(AsFileRenderer::TAG_NAME))->process($container);
    }
}
