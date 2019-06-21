<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\ViewHelpers\ModuleLayout\Button;

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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\AbstractButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\ViewHelpers\ModuleLayoutViewHelper;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use TYPO3Fluid\Fluid\View\Exception;

abstract class AbstractButtonViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('icon', 'string', 'Icon identifier for the button');
        $this->registerArgument('title', 'string', 'Title of the button');
        $this->registerArgument('disabled', 'bool', 'Whether the button is disabled', false, false);
        $this->registerArgument('showLabel', 'bool', 'Defines whether to show the title as a label within the button', false, false);
        $this->registerArgument('position', 'string', 'Position of the button (left or right)');
        $this->registerArgument('group', 'integer', 'Button group of the button');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @throws \InvalidArgumentException
     * @throws \TYPO3Fluid\Fluid\View\Exception
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): void {
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        self::ensureProperNesting($viewHelperVariableContainer);

        /** @var ModuleTemplate $moduleTemplate */
        $moduleTemplate = $viewHelperVariableContainer->get(ModuleLayoutViewHelper::class, ModuleTemplate::class);
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $position = $arguments['position'] ?? ButtonBar::BUTTON_POSITION_LEFT;
        $group = $arguments['group'] ?? 1;
        $button = static::createButton($buttonBar, $arguments, $renderingContext);
        if ($button instanceof AbstractButton) {
            self::addDefaultAttributes($button, $arguments, $renderingContext);
        }
        $buttonBar->addButton($button, $position, $group);
    }

    abstract protected static function createButton(ButtonBar $buttonBar, array $arguments, RenderingContextInterface $renderingContext): ButtonInterface;

    /**
     * @param ViewHelperVariableContainer $viewHelperVariableContainer
     * @throws Exception
     */
    private static function ensureProperNesting(ViewHelperVariableContainer $viewHelperVariableContainer): void
    {
        if (!$viewHelperVariableContainer->exists(ModuleLayoutViewHelper::class, ModuleTemplate::class)) {
            throw new Exception(sprintf('%s must be nested in <f.be.moduleLayout> ViewHelper', self::class), 1531216505);
        }
    }

    private static function addDefaultAttributes(AbstractButton $button, array $arguments, RenderingContextInterface $renderingContext): void
    {
        $button->setShowLabelText($arguments['showLabel']);
        if (isset($arguments['title'])) {
            $button->setTitle($arguments['title']);
        }
        /** @var ModuleTemplate $moduleTemplate */
        $moduleTemplate = $renderingContext->getViewHelperVariableContainer()->get(ModuleLayoutViewHelper::class, ModuleTemplate::class);
        $button->setIcon($moduleTemplate->getIconFactory()->getIcon($arguments['icon'], Icon::SIZE_SMALL));
    }
}
