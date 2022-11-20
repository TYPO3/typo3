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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Domain\Renderer;

use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Base interface for Renderers. A Renderer is used to render a form.
 *
 * Scope: frontend
 * **This interface is meant to be implemented by developers, although often you
 * will subclass AbstractElementRenderer** ({@link AbstractElementRenderer}).
 */
interface RendererInterface
{
    /**
     * Note: This method is expected to call the 'beforeRendering' hook
     * on each $renderable
     *
     * @return string the rendered $formRuntime
     */
    public function render(): string;

    public function setFormRuntime(FormRuntime $formRuntime);

    public function getFormRuntime(): FormRuntime;
}
