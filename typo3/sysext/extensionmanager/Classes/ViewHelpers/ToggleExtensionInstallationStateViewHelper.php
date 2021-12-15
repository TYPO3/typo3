<?php

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

namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Display a deactivate / activate link
 * @internal
 */
class ToggleExtensionInstallationStateViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('extension', 'array', '', true);
    }

    /**
     * Renders an install link
     *
     * @return string the rendered a tag
     */
    public function render()
    {
        if (Environment::isComposerMode()) {
            return '';
        }

        $extension = $this->arguments['extension'];
        $extension += [
            'installed' => false,
        ];
        // Early return if package is protected and can not be unloaded
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $package = $packageManager->getPackage($extension['key']);
        if ($package->isProtected()) {
            return '';
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($this->renderingContext->getRequest());
        $uri = $uriBuilder->reset()->uriFor(
            'toggleExtensionInstallationState',
            ['extensionKey' => $extension['key']],
            'Action'
        );
        $this->tag->addAttribute('href', $uri);
        $label = $extension['installed'] ? 'deactivate' : 'activate';
        $this->tag->addAttribute('title', LocalizationUtility::translate('extensionList.' . $label, 'extensionmanager'));
        $icon = $extension['installed'] ? 'uninstall' : 'install';
        $this->tag->addAttribute('class', 'onClickMaskExtensionManager btn btn-default');
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->tag->setContent($iconFactory->getIcon('actions-system-extension-' . $icon, Icon::SIZE_SMALL)->render());
        return $this->tag->render();
    }
}
