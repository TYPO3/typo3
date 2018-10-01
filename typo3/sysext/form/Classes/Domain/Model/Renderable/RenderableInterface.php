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
 * Base interface which all Form Parts except the FormDefinition must adhere
 * to (i.e. all elements which are NOT the root of a Form).
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 */
interface RenderableInterface extends RootRenderableInterface
{

    /**
     * Return the parent renderable
     *
     * @return CompositeRenderableInterface|null the parent renderable
     * @internal
     */
    public function getParentRenderable();

    /**
     * Set the new parent renderable. You should not call this directly;
     * it is automatically called by addRenderable.
     *
     * This method should also register itself at the parent form, if possible.
     *
     * @param CompositeRenderableInterface $renderable
     * @internal
     */
    public function setParentRenderable(CompositeRenderableInterface $renderable);

    /**
     * Set the index of this renderable inside the parent renderable
     *
     * @param int $index
     * @internal
     */
    public function setIndex(int $index);

    /**
     * Get the index inside the parent renderable
     *
     * @return int
     */
    public function getIndex(): int;

    /**
     * This function is called after a renderable has been removed from its parent
     * renderable. The function should make sure to clean up the internal state,
     * like reseting $this->parentRenderable or deregistering the renderable
     * at the form.
     *
     * @internal
     */
    public function onRemoveFromParentRenderable();

    /**
     * Register this element at the parent form, if there is a connection to the parent form.
     *
     * @internal
     */
    public function registerInFormIfPossible();

    /**
     * Get the template name of the renderable
     *
     * @return string
     */
    public function getTemplateName(): string;

    /**
     * Returns whether this renderable is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;
}
