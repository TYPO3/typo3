<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\ViewHelpers\ModuleLayout;

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

use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\ViewHelpers\ModuleLayoutViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use TYPO3Fluid\Fluid\View\Exception;

/**
 * A ViewHelper for adding a menu to the doc header area
 * of :ref:`<be:moduleLayout> <typo3-backend-modulelayout>`. It accepts only
 * :ref:`<be:moduleLayout.menuItem> <typo3-backend-modulelayout-menuitem>` view
 * helpers as children.
 *
 * Examples
 * ========
 *
 * Default::
 *
 *    <be:moduleLayout>
 *        <be:moduleLayout.menu identifier="MenuIdentifier">
 *            <be:moduleLayout.menuItem label="Menu item 1" uri="{f:uri.action(action: 'index')}"/>
 *        </be:moduleLayout.menu>
 *    </be:moduleLayout>
 */
class MenuViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('identifier', 'string', 'Identifier of the menu', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @throws Exception
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        self::ensureProperNesting($viewHelperVariableContainer);

        /** @var ModuleTemplate $moduleTemplate */
        $moduleTemplate = $viewHelperVariableContainer->get(ModuleLayoutViewHelper::class, ModuleTemplate::class);
        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier($arguments['identifier']);

        $viewHelperVariableContainer->add(ModuleLayoutViewHelper::class, Menu::class, $menu);
        $renderChildrenClosure();
        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $viewHelperVariableContainer->remove(ModuleLayoutViewHelper::class, Menu::class);
    }

    /**
     * @param ViewHelperVariableContainer $viewHelperVariableContainer
     * @throws Exception
     */
    private static function ensureProperNesting(ViewHelperVariableContainer $viewHelperVariableContainer): void
    {
        if (!$viewHelperVariableContainer->exists(ModuleLayoutViewHelper::class, ModuleTemplate::class)) {
            throw new Exception(sprintf('%s must be nested in <f.be.moduleLayout> ViewHelper', self::class), 1531235715);
        }
        if ($viewHelperVariableContainer->exists(ModuleLayoutViewHelper::class, Menu::class)) {
            throw new Exception(sprintf('%s can not be nested in <f.be.moduleLayout.menu> ViewHelper', self::class), 1531235777);
        }
    }
}
