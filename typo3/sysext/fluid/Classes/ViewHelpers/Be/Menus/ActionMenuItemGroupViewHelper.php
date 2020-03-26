<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Menus;

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

use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;

/**
 * ViewHelper which groups options of an option tag.
 *
 * Example
 * =======
 *
 * ::
 *
 *  <f:be.menus.actionMenu>
 *      <f:be.menus.actionMenuItem label="Default: Welcome" controller="Default" action="index" />
 *      <f:be.menus.actionMenuItem label="Community: get in touch" controller="Community" action="index" />
 *
 *      <f:be.menus.actionMenuItemGroup label="Information">
 *          <f:be.menus.actionMenuItem label="PHP Information" controller="Information" action="listPhpInfo" />
 *          <f:be.menus.actionMenuItem label="Documentation" controller="Information" action="documentation" />
 *          <f:be.menus.actionMenuItem label="Hooks" controller="Information" action="hooks" />
 *          <f:be.menus.actionMenuItem label="Signals" controller="Information" action="signals" />
 *          <f:be.menus.actionMenuItem label="XClasses" controller="Information" action="xclass" />
 *      </f:be.menus.actionMenuItemGroup>
 *  </f:be.menus.actionMenu>
 */
class ActionMenuItemGroupViewHelper extends ActionMenuViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'optgroup';

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('label', 'string', 'label', false, '');
    }

    /**
     * @return string
     */
    public function render()
    {
        $label = $this->arguments['label'];

        $this->tag->addAttribute('label', $label);
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
