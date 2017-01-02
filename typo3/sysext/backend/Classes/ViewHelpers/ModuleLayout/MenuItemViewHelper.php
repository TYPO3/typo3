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
use TYPO3\CMS\Backend\ViewHelpers\ModuleLayoutViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use TYPO3Fluid\Fluid\View\Exception;

/**
 * A view helper for adding a menu item to a doc header menu.
 * It must be a child of <be:moduleLayout.menu>
 * = Examples =
 * <code>
 * <be:moduleLayout>
 *     <be:moduleLayout.menu identifier="MenuIdentifier">
 *          <be:moduleLayout.menuItem label="Menu item 1" uri="{f:uri.action(action: 'index')}"/>
 *     </be:moduleLayout.menu>
 * </be:moduleLayout>
 * </code>
 */
class MenuItemViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('label', 'string', 'Label of the menu item', true);
        $this->registerArgument('uri', 'string', 'Action uri', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @throws Exception
     * @throws \InvalidArgumentException
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        self::ensureProperNesting($viewHelperVariableContainer);

        /** @var Menu $menu */
        $menu = $viewHelperVariableContainer->get(ModuleLayoutViewHelper::class, Menu::class);
        $menuItem = $menu->makeMenuItem();
        $menuItem->setTitle($arguments['label']);
        $menuItem->setHref($arguments['uri']);
        $menuItem->setActive(self::isCurrentUri($arguments['uri']));
        $menu->addMenuItem($menuItem);
    }

    /**
     * @param ViewHelperVariableContainer $viewHelperVariableContainer
     * @throws Exception
     */
    private static function ensureProperNesting(ViewHelperVariableContainer $viewHelperVariableContainer): void
    {
        if (!$viewHelperVariableContainer->exists(ModuleLayoutViewHelper::class, Menu::class)) {
            throw new Exception(sprintf('%s must be nested in <f.be.moduleLayout.menu> view helper', self::class), 1531235592);
        }
    }

    /**
     * @param string $uri
     * @return bool
     */
    protected static function isCurrentUri(string $uri): bool
    {
        return GeneralUtility::getIndpEnv('REQUEST_URI') === $uri;
    }
}
