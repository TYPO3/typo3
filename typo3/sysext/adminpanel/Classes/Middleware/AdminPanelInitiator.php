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

namespace TYPO3\CMS\Adminpanel\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ConfigurableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\SubmoduleProviderInterface;
use TYPO3\CMS\Adminpanel\Service\ModuleLoader;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * PSR-15 Middleware to initialize the admin panel
 *
 * @internal
 */
readonly class AdminPanelInitiator implements MiddlewareInterface
{
    public function __construct(
        private ModuleLoader $moduleLoader,
    ) {}

    /**
     * Initialize the adminPanel if
     * - backend user is logged in
     * - at least one adminpanel functionality is enabled
     * - admin panel is open
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (StateUtility::isActivatedForUser() && StateUtility::isOpen()) {
            $request = $request->withAttribute('adminPanelRequestId', substr(md5(StringUtility::getUniqueId()), 0, 13));
            $adminPanelModuleConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules'] ?? [];
            $modules = $this->moduleLoader->validateSortAndInitializeModules($adminPanelModuleConfiguration);
            $request = $this->initializeModules($request, $modules);
        }
        return $handler->handle($request);
    }

    /**
     * @param array<string, ModuleInterface> $modules
     */
    private function initializeModules(ServerRequestInterface $request, array $modules): ServerRequestInterface
    {
        foreach ($modules as $module) {
            if (
                ($module instanceof RequestEnricherInterface)
                && (
                    (($module instanceof ConfigurableInterface) && $module->isEnabled())
                    || (!($module instanceof ConfigurableInterface))
                )
            ) {
                $request = $module->enrich($request);
            }
            if ($module instanceof SubmoduleProviderInterface) {
                $request = $this->initializeModules($request, $module->getSubModules());
            }
        }
        return $request;
    }
}
