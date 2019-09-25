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

use TYPO3\CMS\Form\Domain\Model\Exception\FormDefinitionConsistencyException;

/**
 * Convenience base class which implements common functionality for most
 * classes which implement CompositeRenderableInterface, i.e. have **child renderable elements**.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 */
abstract class AbstractCompositeRenderable extends AbstractRenderable implements CompositeRenderableInterface
{

    /**
     * array of child renderables
     *
     * @var \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface[]
     */
    protected $renderables = [];

    /**
     * Add a renderable to the list of child renderables.
     *
     * This function will be wrapped by the subclasses, f.e. with an "addPage"
     * or "addElement" method with the correct type hint.
     *
     * @param RenderableInterface $renderable
     * @throws FormDefinitionConsistencyException
     * @internal
     */
    protected function addRenderable(RenderableInterface $renderable)
    {
        if ($renderable->getParentRenderable() !== null) {
            throw new FormDefinitionConsistencyException(sprintf('The renderable with identifier "%s" is already added to another element (element identifier: "%s").', $renderable->getIdentifier(), $renderable->getParentRenderable()->getIdentifier()), 1325665144);
        }
        $renderable->setIndex(count($this->renderables));
        $renderable->setParentRenderable($this);
        $this->renderables[] = $renderable;
    }

    /**
     * Move $renderableToMove before $referenceRenderable
     *
     * This function will be wrapped by the subclasses, f.e. with an "movePageBefore"
     * or "moveElementBefore" method with the correct type hint.
     *
     * @param RenderableInterface $renderableToMove
     * @param RenderableInterface $referenceRenderable
     * @throws FormDefinitionConsistencyException
     * @internal
     */
    protected function moveRenderableBefore(RenderableInterface $renderableToMove, RenderableInterface $referenceRenderable)
    {
        if ($renderableToMove->getParentRenderable() !== $referenceRenderable->getParentRenderable() || $renderableToMove->getParentRenderable() !== $this) {
            throw new FormDefinitionConsistencyException('Moved renderables need to be part of the same parent element.', 1326089744);
        }

        $reorderedRenderables = [];
        $i = 0;
        foreach ($this->renderables as $renderable) {
            if ($renderable === $renderableToMove) {
                continue;
            }

            if ($renderable === $referenceRenderable) {
                $reorderedRenderables[] = $renderableToMove;
                $renderableToMove->setIndex($i);
                $i++;
            }
            $reorderedRenderables[] = $renderable;
            $renderable->setIndex($i);
            $i++;
        }
        $this->renderables = $reorderedRenderables;
    }

    /**
     * Move $renderableToMove after $referenceRenderable
     *
     * This function will be wrapped by the subclasses, f.e. with an "movePageAfter"
     * or "moveElementAfter" method with the correct type hint.
     *
     * @param RenderableInterface $renderableToMove
     * @param RenderableInterface $referenceRenderable
     * @throws FormDefinitionConsistencyException
     * @internal
     */
    protected function moveRenderableAfter(RenderableInterface $renderableToMove, RenderableInterface $referenceRenderable)
    {
        if ($renderableToMove->getParentRenderable() !== $referenceRenderable->getParentRenderable() || $renderableToMove->getParentRenderable() !== $this) {
            throw new FormDefinitionConsistencyException('Moved renderables need to be part of the same parent element.', 1477083145);
        }

        $reorderedRenderables = [];
        $i = 0;
        foreach ($this->renderables as $renderable) {
            if ($renderable === $renderableToMove) {
                continue;
            }

            $reorderedRenderables[] = $renderable;
            $renderable->setIndex($i);
            $i++;

            if ($renderable === $referenceRenderable) {
                $reorderedRenderables[] = $renderableToMove;
                $renderableToMove->setIndex($i);
                $i++;
            }
        }
        $this->renderables = $reorderedRenderables;
    }

    /**
     * Returns all RenderableInterface instances of this composite renderable recursively
     *
     * @return RenderableInterface[]
     * @internal
     */
    public function getRenderablesRecursively(): array
    {
        $renderables = [];
        foreach ($this->renderables as $renderable) {
            $renderables[] = $renderable;
            if ($renderable instanceof CompositeRenderableInterface) {
                $renderables = array_merge($renderables, $renderable->getRenderablesRecursively());
            }
        }
        return $renderables;
    }

    /**
     * Remove a renderable from this renderable.
     *
     * This function will be wrapped by the subclasses, f.e. with an "removePage"
     * or "removeElement" method with the correct type hint.
     *
     * @param RenderableInterface $renderableToRemove
     * @throws FormDefinitionConsistencyException
     * @internal
     */
    protected function removeRenderable(RenderableInterface $renderableToRemove)
    {
        if ($renderableToRemove->getParentRenderable() !== $this) {
            throw new FormDefinitionConsistencyException('The renderable to be removed must be part of the calling parent renderable.', 1326090127);
        }

        $updatedRenderables = [];
        foreach ($this->renderables as $renderable) {
            if ($renderable === $renderableToRemove) {
                continue;
            }

            $updatedRenderables[] = $renderable;
        }
        $this->renderables = $updatedRenderables;

        $renderableToRemove->onRemoveFromParentRenderable();
    }

    /**
     * Register this element at the parent form, if there is a connection to the parent form.
     *
     * @internal
     */
    public function registerInFormIfPossible()
    {
        parent::registerInFormIfPossible();
        foreach ($this->renderables as $renderable) {
            $renderable->registerInFormIfPossible();
        }
    }

    /**
     * This function is called after a renderable has been removed from its parent
     * renderable.
     * This just passes the event down to all child renderables of this composite renderable.
     *
     * @internal
     */
    public function onRemoveFromParentRenderable()
    {
        foreach ($this->renderables as $renderable) {
            $renderable->onRemoveFromParentRenderable();
        }
        parent::onRemoveFromParentRenderable();
    }
}
