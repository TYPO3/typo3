<?php

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

namespace TYPO3\CMS\Extbase\Mvc\View;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

/**
 * An empty view - a special case.
 *
 * @deprecated since v11, will be removed with 12.
 */
class EmptyView implements ViewInterface
{
    public function __construct()
    {
        trigger_error('Class ' . __CLASS__ . ' has been deprecated in v11 and will be removed with v12.');
    }

    /**
     * Dummy method to satisfy the ViewInterface
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     * @internal
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
    }

    /**
     * Dummy method to satisfy the ViewInterface
     *
     * @param string $key
     * @param mixed $value
     * @return \TYPO3\CMS\Extbase\Mvc\View\EmptyView instance of $this to allow chaining
     */
    public function assign($key, $value)
    {
        return $this;
    }

    /**
     * Dummy method to satisfy the ViewInterface
     *
     * @param array $values
     * @return \TYPO3\CMS\Extbase\Mvc\View\EmptyView instance of $this to allow chaining
     */
    public function assignMultiple(array $values)
    {
        return $this;
    }

    /**
     * This view can be used in any case.
     *
     * @return bool TRUE
     * @deprecated since TYPO3 v11, will be removed in v12. Legacy method, not part of ViewInterface anymore.
     */
    public function canRender()
    {
        trigger_error('Method ' . __METHOD__ . ' has been deprecated in v11 and will be removed with v12.', E_USER_DEPRECATED);
        return true;
    }

    /**
     * Renders the empty view
     *
     * @return string An empty string
     */
    public function render()
    {
        return '<!-- This is the output of the Empty View. An appropriate View was not found. -->';
    }

    /**
     * A magic call method.
     *
     * Because this empty view is used as a Special Case in situations when no matching
     * view is available, it must be able to handle method calls which originally were
     * directed to another type of view. This magic method should prevent PHP from issuing
     * a fatal error.
     *
     * @param string $methodName
     * @param array $arguments
     */
    public function __call($methodName, array $arguments)
    {
    }

    /**
     * Initializes this view.
     *
     * Override this method for initializing your concrete view implementation.
     */
    public function initializeView()
    {
    }
}
