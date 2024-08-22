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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Allows to modify variables for the view depending on a special login provider set in the controller.
 */
final readonly class ModifyPageLayoutOnLoginProviderSelectionEvent
{
    public function __construct(
        // @deprecated Remove in v14
        private LoginController $controller,
        // @deprecated Set to ViewInterface in v14 when StandaloneView is removed
        private StandaloneView|ViewInterface $view,
        // @deprecated Remove in v14
        private PageRenderer $pageRenderer,
        private ServerRequestInterface $request,
    ) {}

    /**
     * @deprecated Remove in v14.
     */
    public function getController(): LoginController
    {
        trigger_error(
            'ModifyPageLayoutOnLoginProviderSelectionEvent->getController() is deprecated, it has no useful public methods anymore.',
            E_USER_DEPRECATED
        );
        return $this->controller;
    }

    /**
     * @todo Set to ViewInterface in v14 when StandaloneView is removed
     */
    public function getView(): StandaloneView|ViewInterface
    {
        return $this->view;
    }

    /**
     * @deprecated Remove in v14.
     */
    public function getPageRenderer(): PageRenderer
    {
        trigger_error(
            'ModifyPageLayoutOnLoginProviderSelectionEvent->getPageRenderer() is deprecated, retrieve an instance using dependency injection instead.',
            E_USER_DEPRECATED
        );
        return $this->pageRenderer;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
