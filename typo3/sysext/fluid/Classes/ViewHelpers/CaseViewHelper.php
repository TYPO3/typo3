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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\ViewHelpers\SwitchViewHelper as OriginalSwitchViewHelper;

/**
 * Case view helper that is only usable within the SwitchViewHelper.
 * @see \TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper
 *
 * @api
 */
class CaseViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize ViewHelper arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'mixed', 'The switch value. If it matches, the child will be rendered.', false, null);
        $this->registerArgument('default', 'bool', 'If this is set, this child will be rendered, if none else matches.', false, false);
    }

    /***
     * @return string the contents of this view helper if $value equals the expression of the surrounding switch view helper, or $default is TRUE. otherwise an empty string
     * @throws Exception
     *
     * @api
     */
    public function render()
    {
        return static::renderStatic(
            [
                'value' => $this->arguments['value'],
                'default' => $this->arguments['default']
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return mixed|string
     * @throws Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $arguments['value'];
        $default = (bool)$arguments['default'];
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        if ($default !== false) {
            GeneralUtility::logDeprecatedViewHelperAttribute(
                'default',
                $renderingContext,
                'Argument "default" on f:case is deprecated - use f:defaultCase instead'
            );
        }
        if ($value === null && $default === false) {
            throw new Exception('The case View helper must have either value or default argument', 1382867521);
        }
        $expression = $viewHelperVariableContainer->get(OriginalSwitchViewHelper::class, 'switchExpression');

        // non-type-safe comparison by intention
        if ($default === true || $expression == $value) {
            $viewHelperVariableContainer->addOrUpdate(OriginalSwitchViewHelper::class, 'break', true);
            return $renderChildrenClosure();
        }

        return '';
    }
}
