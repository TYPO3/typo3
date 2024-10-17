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

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Exception\ModuleAccessDeniedException;
use TYPO3\CMS\Backend\Exception\NoAccessibleModuleException;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Validates module access and extends the PSR-7 Request with the
 * resolved module object for the use in further components.
 *
 * @internal
 */
class BackendModuleValidator implements MiddlewareInterface
{
    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly FlashMessageService $flashMessageService,
    ) {}

    /**
     * In case the current route targets a TYPO3 backend module and the user
     * has necessary access permissions, add the module to the request.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Route $route */
        $route = $request->getAttribute('route');
        $selectedSubModule = null;
        $inaccessibleSubModule = null;
        $ensureToPersistUserSettings = false;
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!$backendUser
            || !$route->hasOption('module')
            || !(($module = $route->getOption('module')) instanceof ModuleInterface)
        ) {
            return $handler->handle($request);
        }

        // If on a second level module with further sub modules, jump to the third-level modules
        // (either the last used or the first in the list) and store this selection for the user.
        /** @var $module ModuleInterface */
        if ($module->getParentModule() && $module->hasSubModules()) {
            // Note: "action" is a special setting, which is evaluated here individually
            $subModuleIdentifier = (string)($backendUser->getModuleData($module->getIdentifier())['action'] ?? '');
            if ($module->hasSubModule($subModuleIdentifier)) {
                if ($this->moduleProvider->accessGranted($subModuleIdentifier, $backendUser)) {
                    // Use the selected sub module if user has access to it. By checking access here,
                    // we prevent that the user can no longer access the parent module, since it would
                    // always run into the ModuleAccessDeniedException.
                    $selectedSubModule = $module->getSubModule($subModuleIdentifier);
                } else {
                    // Stored sub module exists but is currently not accessible. Store the
                    // requested module to later inform the user about the forced redirect.
                    $inaccessibleSubModule = $module->getSubModule($subModuleIdentifier);
                }
            }
            if ($selectedSubModule === null) {
                // Try to fetch the first accessible sub module. We check access here to prevent
                // that the user can no longer access the parent module, since it would always run
                // into the ModuleAccessDeniedException.
                foreach ($module->getSubModules() as $subModule) {
                    if ($this->moduleProvider->accessGranted($subModule->getIdentifier(), $backendUser)) {
                        $selectedSubModule = $subModule;
                        break;
                    }
                }
            }
            if ($selectedSubModule !== null) {
                // Overwrite the requested module and the route target if an accessible sub module has been found
                $module = $selectedSubModule;
                $route->setOptions(array_replace_recursive($route->getOptions(), $module->getDefaultRouteOptions()['_default']));
            }
        } elseif (($routeIdentifier = $route->getOption('_identifier')) !== null
            && $routeIdentifier === $module->getParentModule()?->getIdentifier()
        ) {
            // In case the actually requested module is the parent of the actually resolved module,
            // the parent module does not define a route itself and uses the current third-level module
            // as fallback. Therefore, we have to check the special "action" key on the "inaccessible"
            // parent module to still allow rerouting to another (last used) third-level module.
            $inaccessibleParentModule = $module->getParentModule();
            $subModuleIdentifier = (string)($backendUser->getModuleData($inaccessibleParentModule->getIdentifier())['action'] ?? '');
            if ($inaccessibleParentModule->hasSubModule($subModuleIdentifier)) {
                $module = $inaccessibleParentModule->getSubModule($subModuleIdentifier);
                $route->setOptions(array_replace_recursive($route->getOptions(), $module->getDefaultRouteOptions()['_default']));
            }
        }

        // Validate the requested module
        try {
            $this->validateModuleAccess($request, $module);
            if ($selectedSubModule !== null && $inaccessibleSubModule !== null) {
                $this->enqueueRedirectMessage($inaccessibleSubModule, $selectedSubModule);
            }
        } catch (ModuleAccessDeniedException $e) {
            // Since the user might request a module which is just temporarily blocked, e.g. due to workspace
            // restrictions, do not throw an exception but redirect to the first accessible module - if any.
            if (($firstAccessibleModule = $this->moduleProvider->getFirstAccessibleModule($backendUser)) !== null) {
                $this->enqueueRedirectMessage($module, $firstAccessibleModule);
                return new RedirectResponse($this->uriBuilder->buildUriFromRoute($firstAccessibleModule->getIdentifier()));
            }
            // User does not have access to any module.. ¯\_(ツ)_/¯
            throw new NoAccessibleModuleException('You don\'t have access to any module.', 1702480600);
        }

        // This module request (which is usually opened inside the list_frame)
        // has been issued from a toplevel browser window (e.g. a link was opened in a new tab).
        // Redirect to open the module as frame inside the TYPO3 backend layout.
        // HEADS UP: This header will only be available in secure connections (https:// or .localhost TLD)
        if ($request->getHeaderLine('Sec-Fetch-Dest') === 'document') {
            return new RedirectResponse(
                $this->uriBuilder->buildUriWithRedirect(
                    'main',
                    [],
                    RouteRedirect::createFromRoute($route, $request->getQueryParams())
                )
            );
        }

        // Third-level module, make sure to remember the previously selected module in the parent module
        if ($module->getParentModule()?->getParentModule()) {
            $parentModuleData = $backendUser->getModuleData($module->getParentIdentifier());
            if (($parentModuleData['action'] ?? '') !== $module->getIdentifier()) {
                $parentModuleData['action'] = $module->getIdentifier();
                $backendUser->pushModuleData($module->getParentIdentifier(), $parentModuleData, true);
                $ensureToPersistUserSettings = true;
            }
        }

        // Check for module data, send via GET/POST parameters.
        // Only consider the configured keys from the module configuration.
        $requestModuleData = [];
        foreach (array_keys($module->getDefaultModuleData()) as $name) {
            $newValue = $request->getParsedBody()[$name] ?? $request->getQueryParams()[$name] ?? null;
            if ($newValue !== null) {
                $requestModuleData[$name] = $newValue;
            }
        }

        // Get stored module data
        if (!is_array(($persistedModuleData = $backendUser->getModuleData($module->getIdentifier())))) {
            $persistedModuleData = [];
        }

        // Settings were changed from the request, so they need to get persisted
        if ($requestModuleData !== []) {
            $moduleData = ModuleData::createFromModule($module, array_replace_recursive($persistedModuleData, $requestModuleData));
            $backendUser->pushModuleData($module->getIdentifier(), $moduleData->toArray(), true);
            $ensureToPersistUserSettings = true;
        } else {
            $moduleData = ModuleData::createFromModule($module, $persistedModuleData);
        }

        // Add validated module and its data to the current request
        $request = $request
            ->withAttribute('module', $module)
            ->withAttribute('moduleData', $moduleData);

        $response = $handler->handle($request);

        if ($ensureToPersistUserSettings) {
            $backendUser->writeUC();
        }

        return $response;
    }

    /**
     * Checks whether the current user is allowed to access the requested module. Does
     * also evaluate page access permissions, in case an "id" is given in the request.
     *
     * @throws ModuleAccessDeniedException
     * @throws \RuntimeException
     */
    protected function validateModuleAccess(ServerRequestInterface $request, ModuleInterface $module): void
    {
        $backendUserAuthentication = $GLOBALS['BE_USER'];
        if (!$this->moduleProvider->accessGranted($module->getIdentifier(), $backendUserAuthentication)) {
            throw new ModuleAccessDeniedException('You don\'t have access to this module.', 1642450334);
        }
    }

    protected function enqueueRedirectMessage(ModuleInterface $requestedModule, ModuleInterface $redirectedModule): void
    {
        $languageService = $this->getLanguageService();
        $this->flashMessageService
            ->getMessageQueueByIdentifier(FlashMessageQueue::NOTIFICATION_QUEUE)
            ->enqueue(
                new FlashMessage(
                    sprintf(
                        $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:module.noAccess.message'),
                        $languageService->sL($redirectedModule->getTitle()),
                        $languageService->sL($requestedModule->getTitle())
                    ),
                    $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:module.noAccess.title'),
                    ContextualFeedbackSeverity::INFO,
                    true
                )
            );
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
