<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Modules;

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
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract base class for Admin Panel Modules containing helper methods and default interface implementations
 *
 * Extend this class when writing own admin panel modules (or implement the Interface directly)
 */
abstract class AbstractModule implements AdminPanelModuleInterface
{
    /**
     * @var string
     */
    protected $extResources = 'EXT:adminpanel/Resources/Private';

    /**
     * @var \TYPO3\CMS\Adminpanel\Modules\AdminPanelSubModuleInterface[]
     */
    protected $subModules = [];

    /**
     * Main Configuration (from UserTSConfig, admPanel)
     *
     * @var array
     */
    protected $mainConfiguration;

    /**
     * @var \TYPO3\CMS\Adminpanel\Service\ConfigurationService
     */
    protected $configurationService;

    public function __construct()
    {
        $this->configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $this->mainConfiguration = $this->configurationService->getMainConfiguration();
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getIconIdentifier(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function initializeModule(ServerRequestInterface $request): void
    {
    }

    /**
     * Returns true if the module is
     * -> either enabled via TSConfig admPanel.enable
     * -> or any setting is overridden
     * override is a way to use functionality of the admin panel without displaying the admin panel to users
     * for example: hidden records or pages can be displayed by default
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        $identifier = $this->getIdentifier();
        $result = $this->isEnabledViaTsConfig();
        if ($this->mainConfiguration['override.'][$identifier] ?? false) {
            $result = (bool)$this->mainConfiguration['override.'][$identifier];
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function onSubmit(array $input, ServerRequestInterface $request): void
    {
    }

    /**
     * Translate given key
     *
     * @param string $key Key for a label in the $LOCAL_LANG array of "sysext/lang/Resources/Private/Language/locallang_tsfe.xlf
     * @param bool $convertWithHtmlspecialchars If TRUE the language-label will be sent through htmlspecialchars
     * @return string The value for the $key
     */
    protected function extGetLL($key, $convertWithHtmlspecialchars = true): string
    {
        $labelStr = $this->getLanguageService()->getLL($key);
        if ($convertWithHtmlspecialchars) {
            $labelStr = htmlspecialchars($labelStr, ENT_QUOTES | ENT_HTML5);
        }
        return $labelStr;
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
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns true if TSConfig admPanel.enable is set for this module (or all modules)
     *
     * @return bool
     */
    protected function isEnabledViaTsConfig(): bool
    {
        $result = false;
        $identifier = $this->getIdentifier();
        if (!empty($this->mainConfiguration['enable.']['all'])) {
            $result = true;
        } elseif (!empty($this->mainConfiguration['enable.'][$identifier])) {
            $result = true;
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getJavaScriptFiles(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getCssFiles(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getShortInfo(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function setSubModules(array $subModules): void
    {
        $this->subModules = $subModules;
    }

    /**
     * @inheritdoc
     */
    public function getSubModules(): array
    {
        return $this->subModules;
    }

    /**
     * @inheritdoc
     */
    public function getHasSubmoduleSettings(): bool
    {
        foreach ($this->subModules as $subModule) {
            if (!empty($subModule->getSettings())) {
                return true;
            }
        }
        return false;
    }
}
