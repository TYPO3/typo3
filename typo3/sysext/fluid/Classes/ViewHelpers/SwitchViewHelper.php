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

use TYPO3Fluid\Fluid\Core\Compiler\StopCompilingException;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\ViewHelpers\CaseViewHelper as OriginalCaseViewHelper;
use TYPO3Fluid\Fluid\ViewHelpers\DefaultCaseViewHelper;

/**
 * Switch view helper which can be used to render content depending on a value or expression.
 * Implements what a basic switch()-PHP-method does.
 *
 * = Examples =
 *
 * <code title="Simple Switch statement">
 * <f:switch expression="{person.gender}">
 *   <f:case value="male">Mr.</f:case>
 *   <f:case value="female">Mrs.</f:case>
 *   <f:defaultCase>Mrs. or Mr.</f:defaultCase>
 * </f:switch>
 * </code>
 * <output>
 * Mr. / Mrs. (depending on the value of {person.gender}) or if no value evaluates to TRUE, defaultCase
 * </output>
 *
 * Note: Using this view helper can be a sign of weak architecture. If you end up using it extensively
 * you might want to consider restructuring your controllers/actions and/or use partials and sections.
 * E.g. the above example could be achieved with <f:render partial="title.{person.gender}" /> and the partials
 * "title.male.html", "title.female.html", ...
 * Depending on the scenario this can be easier to extend and possibly contains less duplication.
 *
 * @api
 */
class SwitchViewHelper extends \TYPO3Fluid\Fluid\ViewHelpers\SwitchViewHelper
{
    /**
     * @param NodeInterface $node
     * @return bool
     */
    protected function isDefaultCaseNode(NodeInterface $node)
    {
        if ($node instanceof ViewHelperNode) {
            $viewHelperClassName = $node->getViewHelperClassName();
            $arguments = $node->getArguments();
            return
                $viewHelperClassName === DefaultCaseViewHelper::class ||
                (
                    $viewHelperClassName === CaseViewHelper::class && isset($arguments['default']) && $arguments['default']
                )
            ;
        }
        return false;
    }

    /**
     * @param NodeInterface $node
     * @return bool
     */
    protected function isCaseNode(NodeInterface $node)
    {
        if ($node instanceof ViewHelperNode) {
            $viewHelperClassName = $node->getViewHelperClassName();
            return $viewHelperClassName === CaseViewHelper::class || $viewHelperClassName === OriginalCaseViewHelper::class;
        }
        return false;
    }

    /**
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     * @param ViewHelperNode $node
     * @param TemplateCompiler $compiler
     */
    public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler)
    {
        if (count($node->getChildNodes())) {
            throw new StopCompilingException('switch view helper', 1476122366);
        }
    }
}
