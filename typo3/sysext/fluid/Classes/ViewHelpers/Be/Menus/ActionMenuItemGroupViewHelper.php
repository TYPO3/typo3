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

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper which groups options within a `<f:be.menus.actionMenu>` group.
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
 * **NOTE**: This ViewHelper is experimental and tailored to be used only in extbase context.
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-be-menus-actionmenuitemgroup
 */
final class ActionMenuItemGroupViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'optgroup';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        // @todo: deprecate
        $this->registerArgument('defaultController', 'string', 'Unused');
        $this->registerArgument('label', 'string', 'The label of the option group', false, '');
    }

    public function render(): string
    {
        $this->tag->addAttribute('label', $this->arguments['label']);
        $options = '';
        foreach ($this->childNodes as $childNode) {
            if ($childNode instanceof ViewHelperNode) {
                $options .= $childNode->evaluate($this->renderingContext);
            }
        }
        $this->tag->setContent($options);
        return $this->tag->render();
    }
}
