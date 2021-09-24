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
 * Interface of a view
 *
 * @deprecated since v11, will be removed with v12. Use TYPO3Fluid\Fluid\View\ViewInterface instead.
 */
interface ViewInterface
{
    /**
     * Sets the current controller context
     *
     * @param ControllerContext $controllerContext
     * @internal
     * @deprecated since v11, will be removed with v12.
     */
    public function setControllerContext(ControllerContext $controllerContext);

    /**
     * Add a variable to the view data collection.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
     *
     * @param string $key Key of variable
     * @param mixed $value Value of object
     * @return self an instance of $this, to enable chaining
     */
    public function assign($key, $value);

    /**
     * Add multiple variables to the view data collection
     *
     * @param array $values array in the format array(key1 => value1, key2 => value2)
     * @return self an instance of $this, to enable chaining
     */
    public function assignMultiple(array $values);

    /**
     * Renders the view
     *
     * @return string The rendered view
     */
    public function render();

    /**
     * Initializes this view.
     */
    public function initializeView();
}
