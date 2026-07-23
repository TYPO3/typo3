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

namespace TYPO3\CMS\Core\Resource\Rendering;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Registry for file renderers, which are registered as tagged services
 * via the #[AsFileRenderer] attribute or the 'fal.file_renderer' service
 * tag. Renderers are ordered by their tag priority, a renderer with a
 * higher priority is asked first whether it can render a file.
 *
 * @internal not part of TYPO3's Core API. Register file renderers via the #[AsFileRenderer] attribute instead.
 */
class RendererRegistry implements SingletonInterface
{
    /**
     * Instance cache for renderer classes
     *
     * @var FileRendererInterface[]|null
     */
    protected ?array $instances = null;

    /**
     * @param iterable<FileRendererInterface> $renderers
     */
    public function __construct(protected readonly iterable $renderers = []) {}

    /**
     * @deprecated since TYPO3 v15.0, this method is a no-op and will be removed in TYPO3 v16.0. Register the renderer as a tagged service using the #[AsFileRenderer] attribute instead.
     */
    public function registerRendererClass(string $className): void
    {
        trigger_error(
            'RendererRegistry->registerRendererClass() is a no-op since TYPO3 v15.0 and will be removed in TYPO3 v16.0.'
            . ' Register "' . $className . '" as a tagged service using the #[AsFileRenderer] attribute instead.',
            E_USER_DEPRECATED
        );
    }

    /**
     * Get all registered renderer instances
     *
     * @return FileRendererInterface[]
     */
    protected function getRendererInstances(): array
    {
        if ($this->instances === null) {
            $this->instances = [];
            foreach ($this->renderers as $renderer) {
                $this->instances[] = $renderer;
            }
        }
        return $this->instances;
    }

    /**
     * Get matching renderer with highest priority
     */
    public function getRenderer(FileInterface $file): ?FileRendererInterface
    {
        foreach ($this->getRendererInstances() as $fileRenderer) {
            if ($fileRenderer->canRender($file)) {
                return $fileRenderer;
            }
        }
        return null;
    }
}
