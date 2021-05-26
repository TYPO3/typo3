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

namespace TYPO3\CMS\Backend\ViewHelpers;

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3Fluid\Fluid\View\Exception;

/**
 * A ViewHelper for having properly styled backend modules.
 * It is recommended to use it in Fluid Layouts.
 * It will render the required HTML for the doc header.
 * All module specific output and further configuration of the doc header
 * must be rendered as children of this ViewHelper.
 *
 * Examples
 * ========
 *
 * Default::
 *
 *    <be:moduleLayout>
 *       <f:render section="content" />
 *    </be:moduleLayout>
 *
 * Output::
 *
 *    <!-- HTML of the backend module -->
 *
 * @deprecated since TYPO3 v11.3, will be removed in TYPO3 v12.0.
 */
class ModuleLayoutViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('name', 'string', 'Name of the module, defaults to the current plugin name, if available', false);
        $this->registerArgument('title', 'string', 'Title of the module.', false);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        trigger_error(__CLASS__ . ' will be removed in TYPO3 v12.', E_USER_DEPRECATED);
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer->exists(self::class, ModuleTemplate::class)) {
            throw new Exception('ModuleLayoutViewHelper can only be used once per module.', 1483292643);
        }

        $extensionService = GeneralUtility::makeInstance(ExtensionService::class);
        $pluginNamespace = $extensionService->getPluginNamespace(
            $renderingContext->getRequest()->getControllerExtensionName(),
            $renderingContext->getRequest()->getPluginName()
        );

        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageService::class)
            ->getMessageQueueByIdentifier('extbase.flashmessages.' . $pluginNamespace);

        $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $moduleTemplate->setFlashMessageQueue($flashMessageQueue);
        if (($arguments['name'] ?? null) !== null) {
            $moduleTemplate->setModuleName($arguments['name']);
        }
        if (($arguments['title'] ?? null) !== null) {
            $moduleTemplate->setTitle($arguments['title']);
        }

        $viewHelperVariableContainer->add(self::class, ModuleTemplate::class, $moduleTemplate);
        $moduleTemplate->setContent($renderChildrenClosure());
        $viewHelperVariableContainer->remove(self::class, ModuleTemplate::class);

        return $moduleTemplate->renderContent();
    }
}
