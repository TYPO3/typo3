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

namespace TYPO3\CMS\Backend\ViewHelpers\ModuleLayout\Button;

use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * A ViewHelper for adding a shortcut button to the doc header area.
 * It must be a child of :ref:`<be:moduleLayout> <typo3-backend-modulelayout>`.
 *
 * The 'arguments' argument should contain key/value pairs of all arguments
 * relevant for the specific view.
 *
 * Examples
 * --------
 *
 * Default::
 *
 *    <be:moduleLayout>
 *        <be:moduleLayout.button.shortcutButton displayName="Shortcut label" arguments="{parameter: '{someValue}'}"/>
 *    </be:moduleLayout>
 *
 * @deprecated since TYPO3 v11.3, will be removed in TYPO3 v12.0. Deprecation logged AbstractButtonViewHelper.
 */
class ShortcutButtonViewHelper extends AbstractButtonViewHelper
{
    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('displayName', 'string', 'Name for the shortcut', false, '');
        $this->registerArgument('arguments', 'array', 'List of relevant GET variables as key/values list to store', false, []);
        $this->registerArgument('getVars', 'array', 'List of additional GET variables to store. The current id, module and all module arguments will always be stored', false, []);
    }

    protected static function createButton(ButtonBar $buttonBar, array $arguments, RenderingContextInterface $renderingContext): ButtonInterface
    {
        $currentRequest = $renderingContext->getRequest();
        $moduleName = $currentRequest->getPluginName();
        $displayName = $arguments['displayName'];

        // Initialize the shortcut button
        $shortcutButton = $buttonBar
            ->makeShortcutButton()
            ->setDisplayName($displayName)
            ->setRouteIdentifier(self::getRouteIdentifierForModuleName($moduleName));

        if (!empty($arguments['arguments'])) {
            $shortcutButton->setArguments($arguments['arguments']);
        } else {
            $extensionName = $currentRequest->getControllerExtensionName();
            $argumentPrefix = GeneralUtility::makeInstance(ExtensionService::class)
                ->getPluginNamespace($extensionName, $moduleName);
            $getVars = $arguments['getVars'];
            $getVars[] = $argumentPrefix;
            $shortcutButton->setGetVariables($getVars);
        }

        return $shortcutButton;
    }

    /**
     * Tries to fetch the route identifier for a given module name
     *
     * @param string $moduleName
     * @return string
     */
    protected static function getRouteIdentifierForModuleName(string $moduleName): string
    {
        foreach (GeneralUtility::makeInstance(Router::class)->getRoutes() as $identifier => $route) {
            if ($route->hasOption('moduleName') && $route->getOption('moduleName') === $moduleName) {
                return $identifier;
            }
        }

        return '';
    }
}
