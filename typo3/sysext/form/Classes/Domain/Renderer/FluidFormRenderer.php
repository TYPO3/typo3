<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Renderer;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Mvc\View\FormView;

/**
 * A renderer which render all renderables within the $formRuntime.
 * All the work is done within FormView::class.
 * This is just a proxy class to make the rendering process more clear.
 * See the documentation within FormView::class for additional information.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @internal
 */
class FluidFormRenderer extends AbstractElementRenderer implements RendererInterface
{

    /**
     * Initialize the FormView::class and render the this->formRuntime.
     * This method is expected to invoke the beforeRendering() callback
     * on each $renderable. This is done within FormView::class.
     *
     * @param RootRenderableInterface $renderable
     * @return string the rendered $formRuntime
     * @internal
     */
    public function render(RootRenderableInterface $renderable): string
    {
        $formView = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(FormView::class);

        $formView->setFormRuntime($this->formRuntime);
        $formView->setControllerContext($this->controllerContext);
        return $formView->renderRenderable($renderable);
    }
}
