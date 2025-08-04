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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Menus;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper which returns a select box that can be used to switch between
 * multiple actions and controllers and looks similar to TYPO3s "funcMenu".
 *
 * ```
 *  <f:be.menus.actionMenu>
 *      <f:be.menus.actionMenuItem label="First Menu" controller="Default" action="index" />
 *      <f:be.menus.actionMenuItemGroup label="Information">
 *          <f:be.menus.actionMenuItem label="PHP Information" controller="Information" action="listPhpInfo" />
 *          <f:be.menus.actionMenuItem label="{f:translate(key:'documentation')}" controller="Information" action="documentation" />
 *          ...
 *      </f:be.menus.actionMenuItemGroup>
 *  </f:be.menus.actionMenu>
 * ```
 *
 * **Note:** This ViewHelper is experimental and tailored to be used only in extbase context.
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-be-menus-actionmenu
 */
final class ActionMenuViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'select';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('defaultController', 'string', 'The default controller to be used');
    }

    public function render(): string
    {
        $options = '';
        foreach ($this->viewHelperNode->getChildNodes() as $childNode) {
            if ($childNode instanceof ViewHelperNode) {
                $options .= $childNode->evaluate($this->renderingContext);
            }
        }
        $this->tag->addAttributes([
            'data-global-event' => 'change',
            'data-action-navigate' => '$value',
        ]);
        $this->tag->setContent($options);
        $this->getPageRenderer()->loadJavaScriptModule('@typo3/backend/global-event-handler.js');
        return '<div class="docheader-funcmenu">' . $this->tag->render() . '</div>';
    }

    /**
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     */
    public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler): string
    {
        // @todo: replace with a true compiling method to make compilable!
        $compiler->disable();
        return '';
    }

    protected static function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
