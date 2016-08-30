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
use TYPO3\CMS\Fluid\Core\ViewHelper\Exception;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Case view helper that is only usable within the SwitchViewHelper.
 * @see \TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper
 *
 * @api
 */
class CaseViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * @param mixed $value The switch value. If it matches, the child will be rendered
     * @param bool $default If this is set, this child will be rendered, if none else matches
     *
     * @return string the contents of this view helper if $value equals the expression of the surrounding switch view helper, or $default is TRUE. otherwise an empty string
     * @throws Exception
     *
     * @api
     */
    public function render($value = null, $default = false)
    {
        return static::renderStatic(
            [
                'value' => $value,
                'default' => $default
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return mixed|string
     * @throws Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $arguments['value'];
        $default = $arguments['default'];
        $viewHelperVariableContainer = $renderingContext->getViewHelperVariableContainer();
        if (!$viewHelperVariableContainer->exists(SwitchViewHelper::class, 'stateStack')) {
            throw new Exception('The case View helper can only be used within a switch View helper', 1368112037);
        }
        if (is_null($value) && $default === false) {
            throw new Exception('The case View helper must have either value or default argument', 1382867521);
        }
        $stateStack = $viewHelperVariableContainer->get(SwitchViewHelper::class, 'stateStack');
        $currentState = array_pop($stateStack);

        if ($currentState['break'] === true) {
            return '';
        }

        // non-type-safe comparison by intention
        if ($default === true || $currentState['expression'] == $value) {
            $currentState['break'] = true;
            $stateStack[] = $currentState;
            $viewHelperVariableContainer->addOrUpdate(SwitchViewHelper::class, 'stateStack', $stateStack);
            return $renderChildrenClosure();
        }

        return '';
    }
}
