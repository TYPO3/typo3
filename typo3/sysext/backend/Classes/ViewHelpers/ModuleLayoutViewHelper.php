<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\ViewHelpers;

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

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3Fluid\Fluid\View\Exception;

/**
 * A view helper for having properly styled backend modules.
 * It is recommended to use it in Fluid Layouts.
 * It will render the required HTML for the doc header.
 * All module specific output and further configuration of the doc header
 * must be rendered as children of this view helper.
 * = Examples =
 * <code>
 * <be:moduleLayout>
 *     <f:render section="content" />
 * </be:moduleLayout>
 * </code>
 * <output>
 * <!-- HTML of the backend module -->
 * </output>
 */
class ModuleLayoutViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer->exists(self::class, ModuleTemplate::class)) {
            throw new Exception('ModuleLayoutViewHelper can only be used once per module.', 1483292643);
        }

        $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $moduleTemplate->setFlashMessageQueue($renderingContext->getControllerContext()->getFlashMessageQueue());

        $viewHelperVariableContainer->add(self::class, ModuleTemplate::class, $moduleTemplate);
        $moduleTemplate->setContent($renderChildrenClosure());
        $viewHelperVariableContainer->remove(self::class, ModuleTemplate::class);

        return $moduleTemplate->renderContent();
    }
}
