<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * With this tag, you can select a layout to be used for the current template.
 *
 * = Examples =
 *
 * <code>
 * <f:layout name="main" />
 * </code>
 * <output>
 * (no output)
 * </output>
 *
 * @api
 */
class LayoutViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\PostParseInterface
{
    /**
     * Initialize arguments
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('name', 'string', 'Name of layout to use. If none given, "Default" is used.', true);
    }

    /**
     * On the post parse event, add the "layoutName" variable to the variable container so it can be used by the TemplateView.
     *
     * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $syntaxTreeNode
     * @param array $viewHelperArguments
     * @param \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer
     * @return void
     */
    public static function postParseEvent(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode $syntaxTreeNode, array $viewHelperArguments, \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer $variableContainer)
    {
        if (isset($viewHelperArguments['name'])) {
            $layoutNameNode = $viewHelperArguments['name'];
        } else {
            $layoutNameNode = new \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\TextNode('Default');
        }

        $variableContainer->add('layoutName', $layoutNameNode);
    }

    /**
     * This tag will not be rendered at all.
     *
     * @return void
     * @api
     */
    public function render()
    {
    }
}
