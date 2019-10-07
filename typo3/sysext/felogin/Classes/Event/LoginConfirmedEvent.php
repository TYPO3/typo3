<?php
declare(strict_types = 1);
namespace TYPO3\CMS\FrontendLogin\Event;

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

use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\FrontendLogin\Controller\LoginController;

/**
 * A notification when a log in has successfully arrived at the plugin, via the view and the controller, multiple
 * information can be overridden in Event Listeners.
 */
final class LoginConfirmedEvent
{
    /**
     * @var LoginController
     */
    private $controller;

    /**
     * @var ViewInterface
     */
    private $view;

    public function __construct(LoginController $controller, ViewInterface $view)
    {
        $this->controller = $controller;
        $this->view = $view;
    }

    public function getController(): LoginController
    {
        return $this->controller;
    }

    public function getView(): ViewInterface
    {
        return $this->view;
    }
}
