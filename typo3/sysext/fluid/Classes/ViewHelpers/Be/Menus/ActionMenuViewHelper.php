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
 * ViewHelper which returns a select box, that can be used to switch between
 * multiple actions and controllers and looks similar to TYPO3s funcMenu.
 *
 * .. note::
 *    This ViewHelper is experimental!
 *
 * Examples
 * ========
 *
 * Simple::
 *
 *    <f:be.menus.actionMenu>
 *       <f:be.menus.actionMenuItem label="Overview" controller="Blog" action="index" />
 *       <f:be.menus.actionMenuItem label="Create new Blog" controller="Blog" action="new" />
 *       <f:be.menus.actionMenuItem label="List Posts" controller="Post" action="index" arguments="{blog: blog}" />
 *    </f:be.menus.actionMenu>
 *
 * Select box with the options "Overview", "Create new Blog" and "List Posts".
 *
 * Localized::
 *
 *    <f:be.menus.actionMenu>
 *       <f:be.menus.actionMenuItem label="{f:translate(key:'overview')}" controller="Blog" action="index" />
 *       <f:be.menus.actionMenuItem label="{f:translate(key:'create_blog')}" controller="Blog" action="new" />
 *    </f:be.menus.actionMenu>
 *
 * Localized select box.
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
        foreach ($this->childNodes as $childNode) {
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
    public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler)
    {
        // @todo: replace with a true compiling method to make compilable!
        $compiler->disable();
        return null;
    }

    protected static function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
