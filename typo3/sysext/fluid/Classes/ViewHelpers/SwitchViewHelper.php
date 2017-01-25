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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

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
 *   <f:case default="TRUE">Mrs. or Mr.</f:case>
 * </f:switch>
 * </code>
 * <output>
 * Mr. / Mrs. (depending on the value of {person.gender}) or if no value evaluates to TRUE, default case
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
class SwitchViewHelper extends AbstractViewHelper implements ChildNodeAccessInterface, CompilableInterface
{
    /**
     * An array of \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
     * @var array
     */
    private $childNodes = [];

    /**
     * @var mixed
     */
    protected $backupSwitchExpression = null;

    /**
     * @var bool
     */
    protected $backupBreakState = false;

    /**
     * Setter for ChildNodes - as defined in ChildNodeAccessInterface
     *
     * @param array $childNodes Child nodes of this syntax tree node
     * @return void
     */
    public function setChildNodes(array $childNodes)
    {
        $this->childNodes = $childNodes;
    }

    /**
     * @param mixed $expression
     * @return string the rendered string
     * @api
     */
    public function render($expression)
    {
        return static::renderStatic(
            [
                'expression' => $expression
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Default implementation for CompilableInterface. See CompilableInterface
     * for a detailed description of this method.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     * @see \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();

        $stackValue = [
            'expression' => $arguments['expression'],
            'break' => false
        ];

        if ($viewHelperVariableContainer->exists(self::class, 'stateStack')) {
            $stateStack = $viewHelperVariableContainer->get(self::class, 'stateStack');
        } else {
            $stateStack = [];
        }
        $stateStack[] = $stackValue;
        $viewHelperVariableContainer->addOrUpdate(self::class, 'stateStack', $stateStack);

        $result = $renderChildrenClosure();

        $stateStack = $viewHelperVariableContainer->get(self::class, 'stateStack');
        array_pop($stateStack);
        $viewHelperVariableContainer->addOrUpdate(self::class, 'stateStack', $stateStack);

        return $result;
    }
}
