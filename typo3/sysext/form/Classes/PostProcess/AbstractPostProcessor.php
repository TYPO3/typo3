<?php
namespace TYPO3\CMS\Form\PostProcess;

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
 * The mail post processor
 */
abstract class AbstractPostProcessor
{
    /**
     * @var \TYPO3\CMS\Form\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * Set the current controller context
     *
     * @param \TYPO3\CMS\Form\Mvc\Controller\ControllerContext $controllerContext
     * @return void
     */
    public function setControllerContext(\TYPO3\CMS\Form\Mvc\Controller\ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }
}
