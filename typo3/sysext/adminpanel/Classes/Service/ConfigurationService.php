<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Service;

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
use TYPO3\CMS\Adminpanel\ModuleApi\ConfigurableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\OnSubmitActorInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\SubmoduleProviderInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Admin Panel Service Class for Configuration Handling
 *
 * Scope: User TSConfig + Backend User UC
 */
class ConfigurationService implements SingletonInterface
{
    /**
     * @var array
     */
    protected $mainConfiguration;

    public function __construct()
    {
        $this->mainConfiguration = $this->getBackendUser()->getTSConfig()['admPanel.'] ?? [];
    }

    /**
     * Get MainConfiguration (User TSConfig admPanel)
     *
     * @return array
     */
    public function getMainConfiguration(): array
    {
        return $this->mainConfiguration;
    }

    /**
     * Helper method to return configuration options
     * Checks User TSConfig overrides and current backend user session
     *
     * @param string $identifier
     * @param string $option
     * @return string
     */
    public function getConfigurationOption(string $identifier, string $option): string
    {
        if ($identifier === '' || $option === '') {
            throw new \InvalidArgumentException('Identifier and option may not be empty', 1532861423);
        }

        if (isset($this->mainConfiguration['override.'][$identifier . '.'][$option])) {
            $returnValue = $this->mainConfiguration['override.'][$identifier . '.'][$option];
        } else {
            $returnValue = $this->getBackendUser()->uc['AdminPanel'][$identifier . '_' . $option] ?? '';
        }

        return (string)$returnValue;
    }

    /**
     * Save admin panel configuration to backend user UC
     * triggers onSubmit method of modules to enable each module
     * to enhance the save action
     *
     * @param \TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface[] $modules
     * @param ServerRequestInterface $request
     */
    public function saveConfiguration(array $modules, ServerRequestInterface $request): void
    {
        $configurationToSave = $request->getParsedBody()['TSFE_ADMIN_PANEL'] ?? [];
        $beUser = $this->getBackendUser();
        $this->triggerOnSubmitActors($modules, $request, $configurationToSave);

        $existingConfiguration = $beUser->uc['AdminPanel'] ?? [];
        $existingConfiguration = is_array($existingConfiguration) ? $existingConfiguration : [];

        // Settings
        $beUser->uc['AdminPanel'] = array_merge($existingConfiguration, $configurationToSave);
        unset($beUser->uc['AdminPanel']['action']);
        // Saving
        $beUser->writeUC();
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication|FrontendBackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @param array $modules
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param $configurationToSave
     */
    protected function triggerOnSubmitActors(
        array $modules,
        ServerRequestInterface $request,
        $configurationToSave
    ): void {
        foreach ($modules as $module) {
            if (
                $module instanceof OnSubmitActorInterface
                && (
                    ($module instanceof ConfigurableInterface && $module->isEnabled())
                    || !($module instanceof ConfigurableInterface)
                )
             ) {
                $module->onSubmit($configurationToSave, $request);
            }
            if ($module instanceof SubmoduleProviderInterface) {
                $this->triggerOnSubmitActors($module->getSubModules(), $request, $configurationToSave);
            }
        }
    }
}
