<?php
namespace TYPO3\CMS\Styleguide\ViewHelpers\Be\Menus;

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

/**
 * Wrapper for f:be.menus.actionMenu
 * Adapts HTML for 7.6 ModuleTemplate API
 */
class ActionMenuViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\Menus\ActionMenuViewHelper
{

    /**
     * @param string $defaultController
     * @return string
     */
    public function render($defaultController = null)
    {
        $this->tag->addAttribute('class', 'form-control input-sm');
        $this->tag->addAttribute('onchange', 'jumpToUrl(this.options[this.selectedIndex].value, this);');
        $options = '';
        foreach ($this->childNodes as $childNode) {
            if ($childNode instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode) {
                $options .= $childNode->evaluate($this->renderingContext);
            }
        }
        $this->tag->setContent($options);
        return $this->tag->render();
    }
}
