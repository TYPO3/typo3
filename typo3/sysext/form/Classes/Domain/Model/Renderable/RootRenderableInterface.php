<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Model\Renderable;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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
     */
    public function getType(): string;

    /**
     * The identifier of this renderable
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Get the label which shall be displayed next to the form element
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get the renderer class name to be used to display this form;
     * must implement RendererInterface
     *
     * @return string the renderer class name
     */
    public function getRendererClassName(): string;

    /**
     * Get all rendering options
     *
     * @return array associative array of rendering options
     */
    public function getRenderingOptions(): array;
}
