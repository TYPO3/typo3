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

namespace TYPO3\CMS\Backend\LoginProvider\Event;

use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Allows to modify variables for the view depending on a special login provider set in the controller.
 */
final class ModifyPageLayoutOnLoginProviderSelectionEvent
{
    /**
     * @var LoginController
     */
    private $controller;

    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var PageRenderer
     */
    private $pageRenderer;

    public function __construct(LoginController $controller, StandaloneView $view, PageRenderer $pageRenderer)
    {
        $this->controller = $controller;
        $this->view = $view;
        $this->pageRenderer = $pageRenderer;
    }

    public function getController(): LoginController
    {
        return $this->controller;
    }

    public function getView(): StandaloneView
    {
        return $this->view;
    }

    public function getPageRenderer(): PageRenderer
    {
        return $this->pageRenderer;
    }
}
