<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Model\Renderable;

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

use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Base interface which all parts of a form must adhere to.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 */
interface RootRenderableInterface
{

    /**
     * Abstract "type" of this Renderable. Is used during the rendering process
     * to determine the template file or the View PHP class being used to render
     * the particular element.
     *
     * @return string
     * @api
     */
    public function getType(): string;

    /**
     * The identifier of this renderable
     *
     * @return string
     * @api
     */
    public function getIdentifier(): string;

    /**
     * Get the label which shall be displayed next to the form element
     *
     * @return string
     * @api
     */
    public function getLabel(): string;

    /**
     * This is a callback that is invoked by the Renderer before the corresponding element is rendered.
     * Use this to access previously submitted values and/or modify the $formRuntime before an element
     * is outputted to the browser.
     *
     * @param FormRuntime $formRuntime
     * @return void
     * @api
     */
    public function beforeRendering(FormRuntime $formRuntime);

    /**
     * Get the renderer class name to be used to display this renderable;
     * must implement RendererInterface
     *
     * Is only set if a specific renderer should be used for this renderable,
     * if it is NULL the caller needs to determine the renderer or take care
     * of the renderer itself.
     *
     * @return null|string the renderer class name
     * @api
     */
    public function getRendererClassName();

    /**
     * Get all rendering options
     *
     * @return array associative array of rendering options
     * @api
     */
    public function getRenderingOptions(): array;
}
