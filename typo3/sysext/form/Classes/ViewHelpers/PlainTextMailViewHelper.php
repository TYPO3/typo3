<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\ViewHelpers;

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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * A viewhelper for the plain mail view
 *
 * Scope: frontend
 * @api
 * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
 */
class PlainTextMailViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize the arguments.
     *
     * @internal
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('formValue', 'array', 'The values from a form element', true);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @api
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $formValue = $arguments['formValue'];

        $label = TranslateElementPropertyViewHelper::renderStatic(
            ['element' => $formValue['element'], 'property' => 'label'],
            $renderChildrenClosure,
            $renderingContext
        );
        $processedValue = (!empty($formValue['processedValue'])) ? $formValue['processedValue'] : '-';
        $isMultiValue = $formValue['isMultiValue'];

        $label .= ': ';
        if ($isMultiValue && is_array($processedValue)) {
            $output = $label . array_shift($processedValue) . LF;
            $indent = str_repeat(chr(32), (strlen($label)));
            foreach ($processedValue as $multiValue) {
                $output .= $indent . $multiValue;
            }
        } else {
            $output = $label . $processedValue;
        }

        return $output . LF . LF;
    }
}
