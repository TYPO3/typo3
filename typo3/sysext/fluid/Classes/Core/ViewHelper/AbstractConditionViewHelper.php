<?php
namespace TYPO3\CMS\Fluid\Core\ViewHelper;

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
 * Class AbstractConditionViewHelper
 */
abstract class AbstractConditionViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper
{
    /**
     * Controller Context to use
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     * @api
     */
    protected $controllerContext;

    /**
     * Renders <f:then> child if $condition is true, otherwise renders <f:else> child.
     *
     * @param bool $condition View helper condition
     * @return string the rendered string
     * @api
     */
    public function render()
    {
        if (static::evaluateCondition($this->arguments)) {
            return $this->renderThenChild();
        }
        return $this->renderElseChild();
    }

    /**
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     */
    public function setRenderingContext(\TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        parent::setRenderingContext($renderingContext);
        if ($renderingContext instanceof \TYPO3\CMS\Fluid\Core\Rendering\RenderingContext) {
            $this->controllerContext = $renderingContext->getControllerContext();
        }
    }
}
