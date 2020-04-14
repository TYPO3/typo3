<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Adminpanel\ModuleApi;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Adminpanel interface to denote that a module has tasks to perform on initialization of the request and may enrich said request
 *
 * Modules that need to set data / options early in the rendering process to be able to collect data, should implement
 * this interface - for example the log module uses the initialization to register the admin panel log collection early
 * in the rendering process.
 *
 * Modules that manipulate the request based on their configuration should also implement this interface.
 *
 * Initialize is called in the PSR-15 middleware stack through admin panel initialisation via the AdminPanel MainController.
 *
 * @see \TYPO3\CMS\Adminpanel\Middleware\AdminPanelInitiator::process()
 * @see \TYPO3\CMS\Adminpanel\Controller\MainController::initialize()
 */
interface RequestEnricherInterface
{
    /**
     * Initialize the module - runs in the TYPO3 middleware stack at an early point
     * may manipulate the current request
     *
     * @param ServerRequestInterface $request
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function enrich(ServerRequestInterface $request): ServerRequestInterface;
}
