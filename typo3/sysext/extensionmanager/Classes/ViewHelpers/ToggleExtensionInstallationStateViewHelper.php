<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Display a deactivate / activate link
 * @internal
 */
class ToggleExtensionInstallationStateViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Renders an install link
     *
     * @param string $extension
     * @return string the rendered a tag
     */
    public function render($extension)
    {
        // Early return if package is protected or is a runtime actived package and can not be unloaded
        /** @var $packageManager \TYPO3\CMS\Core\Package\PackageManager */
        $packageManager = $this->objectManager->get(\TYPO3\CMS\Core\Package\PackageManager::class);
        $package = $packageManager->getPackage($extension['key']);
        if ($package->isProtected() || in_array($extension['key'], $GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages'])) {
            return '';
        }

        $uriBuilder = $this->controllerContext->getUriBuilder();
        $action = 'toggleExtensionInstallationState';
        $uri = $uriBuilder->reset()->uriFor($action, array(
            'extensionKey' => $extension['key']
        ), 'Action');
        $this->tag->addAttribute('href', $uri);
        $label = $extension['installed'] ? 'deactivate' : 'activate';
        $this->tag->addAttribute('title', \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extensionList.' . $label, 'extensionmanager'));
        $icon = $extension['installed'] ? 'uninstall' : 'install';
        $this->tag->addAttribute('class', 'onClickMaskExtensionManager btn btn-default');

        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->tag->setContent($iconFactory->getIcon('actions-system-extension-' . $icon, Icon::SIZE_SMALL)->render());
        return $this->tag->render();
    }
}
