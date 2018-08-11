<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Controller;

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
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Adminpanel\Service\ModuleLoader;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Admin Panel Ajax Controller - Route endpoint for ajax actions
 *
 * @internal
 */
class AjaxController
{
    /**
     * @var array
     */
    protected $adminPanelModuleConfiguration;

    /**
     * @var ModuleLoader
     */
    protected $moduleLoader;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @param ConfigurationService $configurationService
     * @param ModuleLoader $moduleLoader
     */
    public function __construct(ConfigurationService $configurationService = null, ModuleLoader $moduleLoader = null)
    {
        $this->configurationService = $configurationService
                                      ??
                                      GeneralUtility::makeInstance(ConfigurationService::class);
        $this->adminPanelModuleConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules'] ?? [];
        $this->moduleLoader = $moduleLoader ?? GeneralUtility::makeInstance(ModuleLoader::class);
    }

    /**
     * Save adminPanel data
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function saveDataAction(ServerRequestInterface $request): JsonResponse
    {
        $this->configurationService->saveConfiguration(
            $this->moduleLoader->validateSortAndInitializeModules($this->adminPanelModuleConfiguration),
            $request
        );
        return new JsonResponse(['success' => true]);
    }

    /**
     * Toggle admin panel active state via UC
     *
     * @return JsonResponse
     */
    public function toggleActiveState(): JsonResponse
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->uc['AdminPanel']['display_top'] ?? false) {
            $backendUser->uc['AdminPanel']['display_top'] = false;
        } else {
            $backendUser->uc['AdminPanel']['display_top'] = true;
        }
        $backendUser->writeUC();
        return new JsonResponse(['success' => true]);
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
