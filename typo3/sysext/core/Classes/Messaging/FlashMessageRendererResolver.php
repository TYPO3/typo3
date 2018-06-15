<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Messaging;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\Renderer\FlashMessageRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class for rendering flash messages.
 */
class FlashMessageRendererResolver
{
    /**
     * @var array
     */
    protected $renderer = [
        'BE' => Renderer\BootstrapRenderer::class,
        'FE' => Renderer\ListRenderer::class,
        'CLI' => Renderer\PlaintextRenderer::class,
        '_default' => Renderer\PlaintextRenderer::class,
    ];

    /**
     * This method resolves a FlashMessageRendererInterface for the given $context.
     *
     * In case $context is null, the context will be detected automatic.
     *
     * @return FlashMessageRendererInterface
     */
    public function resolve(): FlashMessageRendererInterface
    {
        $rendererClass = $this->resolveFlashMessageRenderClass();
        $renderer = GeneralUtility::makeInstance($rendererClass);
        if (!$renderer instanceof FlashMessageRendererInterface) {
            throw new \RuntimeException('Renderer ' . get_class($renderer)
                . ' does not implement FlashMessageRendererInterface', 1476958086);
        }
        return $renderer;
    }

    /**
     * This method resolves the renderer class by given context.
     *
     * @return string
     */
    protected function resolveFlashMessageRenderClass(): string
    {
        $context = $this->resolveContext();
        $renderClass = $this->renderer['_default'];

        if (!empty($this->renderer[$context])) {
            $renderClass = $this->renderer[$context];
        }

        return $renderClass;
    }

    /**
     * This method detect the current context and return one of the
     * following strings:
     * - FE
     * - BE
     * - CLI
     *
     * @return string
     */
    protected function resolveContext(): string
    {
        $context = '';
        if (Environment::isCli()) {
            $context = 'CLI';
        } elseif (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE) {
            $context = 'BE';
        } elseif (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_FE) {
            $context = 'FE';
        }
        return $context;
    }
}
