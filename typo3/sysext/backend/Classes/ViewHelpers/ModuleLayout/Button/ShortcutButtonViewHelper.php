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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
 *        <be:moduleLayout.button.shortcutButton displayName="Shortcut label" arguments="{route: '{route}'"/>
 *    </be:moduleLayout>
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
        $this->registerArgument('displayName', 'string', 'Name for the shortcut');
        $this->registerArgument('arguments', 'array', 'List of relevant GET variables as key/values list to store', false, []);
        // @deprecated since v11, will be removed in v12. Use 'arguments' instead. Deprecation logged by ModuleTemplate->makeShortcutIcon()
        $this->registerArgument('getVars', 'array', 'List of additional GET variables to store. The current id, module and all module arguments will always be stored', false, []);
    }

    protected static function createButton(ButtonBar $buttonBar, array $arguments, RenderingContextInterface $renderingContext): ButtonInterface
    {
        $currentRequest = $renderingContext->getRequest();
        $moduleName = $currentRequest->getPluginName();
        $displayName = $arguments['displayName'];

        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setDisplayName($displayName)
            ->setModuleName($moduleName);

        if (!empty($arguments['arguments'])) {
            $shortcutButton->setArguments($arguments['arguments']);
        } else {
            // @deprecated since v11, will be removed in v12. Use 'variables' instead. Deprecation logged by ModuleTemplate->makeShortcutIcon()
            $extensionName = $currentRequest->getControllerExtensionName();
            $argumentPrefix = GeneralUtility::makeInstance(ObjectManager::class)
                ->get(ExtensionService::class)
                ->getPluginNamespace($extensionName, $moduleName);
            $getVars = $arguments['getVars'];
            $getVars[] = $argumentPrefix;
            $shortcutButton->setGetVariables($getVars);
        }

        return $shortcutButton;
    }
}
