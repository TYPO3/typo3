<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;

/**
 * Layout controller
 *
 * Renders a first "load the Javascript in <head>" view, and the
 * main layout of the install tool in second action.
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class LayoutController extends AbstractController
{
    /**
     * The init action renders an HTML response with HTML view having <head> section
     * containing resources to main .js routing.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function initAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Layout/Init.html');
        $view->assignMultiple([
            // time is used as cache bust for js and css resources
            'time' => time(),
            'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
        ]);
        return new HtmlResponse(
            $view->render(),
            200,
            [
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'no-cache'
            ]
        );
    }

    /**
     * Return a json response with the main HTML layout body: Toolbar, main menu and
     * doc header in standalone, doc header only in backend context. Silent updaters
     * are executed before this main view is loaded.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mainLayoutAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Layout/MainLayout.html');
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Execute silent configuration update. May be called multiple times until success = true is returned.
     *
     * @return ResponseInterface success = true if no change has been done
     */
    public function executeSilentConfigurationUpdateAction(): ResponseInterface
    {
        $silentUpdate = new SilentConfigurationUpgradeService();
        $success = true;
        try {
            $silentUpdate->execute();
        } catch (ConfigurationChangedException $e) {
            $success = false;
        }
        return new JsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * Legacy ajax call. This silent updater takes care that all extensions configured in LocalConfiguration
     * EXT/extConf serialized array are "upmerged" to arrays within EXTENSIONS if this extension does not
     * exist in EXTENSIONS yet.
     *
     * @return ResponseInterface
     * @deprecated since TYPO3 v9, will be removed with TYPO3 v10.0
     */
    public function executeSilentLegacyExtConfExtensionConfigurationUpdateAction(): ResponseInterface
    {
        $configurationManager = new ConfigurationManager();
        try {
            $oldExtConfSettings = $configurationManager->getConfigurationValueByPath('EXT/extConf');
        } catch (MissingArrayPathException $e) {
            // The old 'extConf' array may not exist anymore, set to empty array if so.
            $oldExtConfSettings = [];
        }
        try {
            $newExtensionSettings = $configurationManager->getConfigurationValueByPath('EXTENSIONS');
        } catch (MissingArrayPathException $e) {
            // New 'EXTENSIONS' array may not exist yet, for instance if just upgrading to v9
            $newExtensionSettings = [];
        }
        foreach ($oldExtConfSettings as $extensionName => $extensionSettings) {
            if (!array_key_exists($extensionName, $newExtensionSettings)) {
                $unserializedConfiguration = unserialize($extensionSettings, ['allowed_classes' => false]);
                if (is_array($unserializedConfiguration)) {
                    $newExtensionSettings = $this->removeDotsFromArrayKeysRecursive($unserializedConfiguration);
                    $configurationManager->setLocalConfigurationValueByPath('EXTENSIONS/' . $extensionName, $newExtensionSettings);
                }
            }
        }
        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Synchronize TYPO3_CONF_VARS['EXTENSIONS'] with possibly new defaults from extensions
     * ext_conf_template.txt files. This make LocalConfiguration the only source of truth for
     * extension configuration and it is always up to date, also if an extension has been
     * updated.
     *
     * @return ResponseInterface
     */
    public function executeSilentExtensionConfigurationSynchronizationAction(): ResponseInterface
    {
        $extensionConfiguration = new ExtensionConfiguration();
        $extensionConfiguration->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions();
        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Helper method for executeSilentLegacyExtConfExtensionConfigurationUpdateAction(). Old EXT/extConf
     * settings have dots at the end of array keys if nested arrays were used. The new configuration does
     * not use this funny nested representation anymore. The method removes all dots at the end of given
     * array keys recursive to do this transition.
     *
     * @param array $settings
     * @return array New settings
     * @deprecated since TYPO3 v9, will be removed with TYPO3 v10.0 along with executeSilentLegacyExtConfExtensionConfigurationUpdateAction()
     */
    private function removeDotsFromArrayKeysRecursive(array $settings): array
    {
        $settingsWithoutDots = [];
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $settingsWithoutDots[rtrim($key, '.')] = $this->removeDotsFromArrayKeysRecursive($value);
            } else {
                $settingsWithoutDots[$key] = $value;
            }
        }
        return $settingsWithoutDots;
    }
}
