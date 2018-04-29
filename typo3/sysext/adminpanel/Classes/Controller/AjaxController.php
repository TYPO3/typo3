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

use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Adminpanel\Service\ModuleLoader;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Admin Panel Ajax Controller - Route endpoint for ajax actions
 */
class AjaxController
{

    /**
     * Save adminPanel data
     *
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function saveDataAction(ServerRequest $request): JsonResponse
    {
        $moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);

        $modules = $moduleLoader->getModulesFromConfiguration();

        $input = $request->getParsedBody()['TSFE_ADMIN_PANEL'] ?? null;
        $beUser = $this->getBackendUser();
        if (is_array($input)) {
            // Setting
            $beUser->uc['TSFE_adminConfig'] = array_merge(
                !is_array($beUser->uc['TSFE_adminConfig']) ? [] : $beUser->uc['TSFE_adminConfig'],
                $input
            );
            unset($beUser->uc['TSFE_adminConfig']['action']);

            /** @var \TYPO3\CMS\Adminpanel\Modules\AdminPanelModuleInterface $module */
            foreach ($modules as $module) {
                if ($module->isEnabled()) {
                    $module->onSubmit($input);
                }
            }
            // Saving
            $beUser->writeUC();
            // Flush fluid template cache
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
            $cacheManager->getCache('fluid_template')->flush();
        }
        return new JsonResponse(['success' => true]);
    }

    /**
     * Toggle admin panel active state via UC
     *
     * @param \Psr\Http\Message\RequestInterface $request
     * @return \TYPO3\CMS\Core\Http\JsonResponse
     */
    public function toggleActiveState(RequestInterface $request): JsonResponse
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->uc['TSFE_adminConfig']['display_top'] ?? false) {
            $backendUser->uc['TSFE_adminConfig']['display_top'] = false;
        } else {
            $backendUser->uc['TSFE_adminConfig']['display_top'] = true;
        }
        $backendUser->writeUC();
        return new JsonResponse(['success' => true]);
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
