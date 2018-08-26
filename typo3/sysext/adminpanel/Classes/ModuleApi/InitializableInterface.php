<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\ModuleApi;

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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Adminpanel interface to denote that a module has tasks to perform on initialization of the request
 *
 * Modules that need to set data / options early in the rendering process to be able to collect data, should implement
 * this interface - for example the log module uses the initialization to register the admin panel log collection early
 * in the rendering process.
 *
 * Initialize is called in the PSR-15 middleware stack through admin panel initialisation via the AdminPanel MainController.
 *
 * @see \TYPO3\CMS\Adminpanel\Middleware\AdminPanelInitiator::process()
 * @see \TYPO3\CMS\Adminpanel\Controller\MainController::initialize()
 */
interface InitializableInterface
{
    /**
     * Initialize the module - runs early in a TYPO3 request
     *
     * @param ServerRequestInterface $request
     */
    public function initializeModule(ServerRequestInterface $request): void;
}
