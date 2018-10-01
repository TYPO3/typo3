<?php
namespace TYPO3\CMS\Extbase\Mvc\View;

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
 * An abstract View
 */
abstract class AbstractView implements \TYPO3\CMS\Extbase\Mvc\View\ViewInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * View variables and their values
     *
     * @var array
     * @see assign()
     */
    protected $variables = [];

    /**
     * Sets the current controller context
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     */
    public function setControllerContext(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    /**
     * Add a variable to $this->viewData.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
     *
     * @param string $key Key of variable
     * @param mixed $value Value of object
     * @return \TYPO3\CMS\Extbase\Mvc\View\AbstractView an instance of $this, to enable chaining
     */
    public function assign($key, $value)
    {
        $this->variables[$key] = $value;
        return $this;
    }

    /**
     * Add multiple variables to $this->viewData.
     *
     * @param array $values array in the format array(key1 => value1, key2 => value2).
     * @return \TYPO3\CMS\Extbase\Mvc\View\AbstractView an instance of $this, to enable chaining
     */
    public function assignMultiple(array $values)
    {
        foreach ($values as $key => $value) {
            $this->assign($key, $value);
        }
        return $this;
    }

    /**
     * Tells if the view implementation can render the view for the given context.
     *
     * By default we assume that the view implementation can handle all kinds of
     * contexts. Override this method if that is not the case.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     * @return bool TRUE if the view has something useful to display, otherwise FALSE
     */
    public function canRender(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext)
    {
        return true;
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
