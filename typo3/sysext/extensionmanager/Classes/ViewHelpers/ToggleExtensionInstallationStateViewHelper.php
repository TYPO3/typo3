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
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper;

/**
 * Display a deactivate / activate link
 * @internal
 */
class ToggleExtensionInstallationStateViewHelper extends ActionViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Renders an install link
     *
     * @param array $extension
     * @param array $arguments Arguments
     * @param string $controller Target controller. If NULL current controllerName is used
     * @param string $extensionName Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used
     * @param string $pluginName Target plugin. If empty, the current plugin name is used
     * @param int $pageUid target page. See TypoLink destination
     * @param int $pageType type of the target page. See typolink.parameter
     * @param bool $noCache set this to disable caching for the target page. You should not need this.
     * @param bool $noCacheHash set this to suppress the cHash query parameter created by TypoLink. You should not need this.
     * @param string $section the anchor to be added to the URI
     * @param string $format The requested format, e.g. ".html
     * @param bool $linkAccessRestrictedPages If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.
     * @param array $additionalParams additional query parameters that won't be prefixed like $arguments (overrule $arguments)
     * @param bool $absolute If set, the URI of the rendered link is absolute
     * @param bool $addQueryString If set, the current query parameters will be kept in the URI
     * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
     * @param string $addQueryStringMethod Set which parameters will be kept. Only active if $addQueryString = TRUE
     * @return string the rendered a tag
     */
    public function render($extension = null, array $arguments = array(), $controller = null, $extensionName = null, $pluginName = null, $pageUid = null, $pageType = 0, $noCache = false, $noCacheHash = false, $section = '', $format = '', $linkAccessRestrictedPages = false, array $additionalParams = array(), $absolute = false, $addQueryString = false, array $argumentsToBeExcludedFromQueryString = array(), $addQueryStringMethod = null)
    {
        // Early return if package is protected or is a runtime actived package and can not be unloaded
        /** @var $packageManager \TYPO3\CMS\Core\Package\PackageManager */
        $packageManager = $this->objectManager->get(PackageManager::class);
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
        $this->tag->addAttribute('title', LocalizationUtility::translate('extensionList.' . $label, 'extensionmanager'));
        $icon = $extension['installed'] ? 'uninstall' : 'install';
        $this->tag->addAttribute('class', 'onClickMaskExtensionManager btn btn-default');

        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->tag->setContent($iconFactory->getIcon('actions-system-extension-' . $icon, Icon::SIZE_SMALL)->render());
        return $this->tag->render();
    }
}
