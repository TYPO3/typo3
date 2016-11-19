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
     * @return null|CompositeRenderableInterface the parent renderable
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
     * @return void
     * @internal
     */
    public function setParentRenderable(CompositeRenderableInterface $renderable);

    /**
     * Set the index of this renderable inside the parent renderable
     *
     * @param int $index
     * @return void
     * @internal
     */
    public function setIndex(int $index);

    /**
     * Get the index inside the parent renderable
     *
     * @return int
     * @api
     */
    public function getIndex(): int;

    /**
     * This function is called after a renderable has been removed from its parent
     * renderable. The function should make sure to clean up the internal state,
     * like reseting $this->parentRenderable or deregistering the renderable
     * at the form.
     *
     * @return void
     * @internal
     */
    public function onRemoveFromParentRenderable();

    /**
     * This is a callback that is invoked by the Form Factory after the whole form has been built.
     * It can be used to add new form elements as children for complex form elements.
     *
     * @return void
     * @api
     */
    public function onBuildingFinished();

    /**
     * Register this element at the parent form, if there is a connection to the parent form.
     *
     * @return void
     * @internal
     */
    public function registerInFormIfPossible();
}
