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
 * Interface of a view
 *
 * @api
 */
interface ViewInterface
{
    /**
     * Sets the current controller context
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     * @return void
     */
    public function setControllerContext(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext);

    /**
     * Add a variable to the view data collection.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
     *
     * @param string $key Key of variable
     * @param mixed $value Value of object
     * @return \TYPO3\CMS\Extbase\Mvc\View\ViewInterface an instance of $this, to enable chaining
     * @api
     */
    public function assign($key, $value);

    /**
     * Add multiple variables to the view data collection
     *
     * @param array $values array in the format array(key1 => value1, key2 => value2)
     * @return \TYPO3\CMS\Extbase\Mvc\View\ViewInterface an instance of $this, to enable chaining
     * @api
     */
    public function assignMultiple(array $values);

    /**
     * Tells if the view implementation can render the view for the given context.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     * @return bool TRUE if the view has something useful to display, otherwise FALSE
     * @api
     */
    public function canRender(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext);

    /**
     * Renders the view
     *
     * @return string The rendered view
     * @api
     */
    public function render();

    /**
     * Initializes this view.
     *
     * @return void
     * @api
     */
    public function initializeView();
}
