<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Renderer;

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

use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Abstract renderer which can be used as base class for custom renderers.
 *
 * Scope: frontend
 * **This class is meant to be sub classed by developers**.
 */
abstract class AbstractElementRenderer implements RendererInterface
{

    /**
     * The assigned controller context which might be needed by the renderer.
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var \TYPO3\CMS\Form\Domain\Runtime\FormRuntime
     */
    protected $formRuntime;

    /**
     * Set the controller context which should be used
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     */
    public function setControllerContext(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    /**
     * @param FormRuntime $formRuntime
     */
    public function setFormRuntime(FormRuntime $formRuntime)
    {
        $this->formRuntime = $formRuntime;
    }

    /**
     * @return FormRuntime
     */
    public function getFormRuntime(): FormRuntime
    {
        return $this->formRuntime;
    }
}
