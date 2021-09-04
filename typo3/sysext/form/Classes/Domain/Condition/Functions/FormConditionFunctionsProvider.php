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

namespace TYPO3\CMS\Form\Domain\Condition\Functions;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * @internal
 */
class FormConditionFunctionsProvider implements ExpressionFunctionProviderInterface
{

    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        return [
            $this->getFormValueFunction(),
            $this->getRootFormPropertyFunction(),
        ];
    }

    /**
     * Shortcut function to access field values
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    protected function getFormValueFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'getFormValue',
            static function () {
                // Not implemented, we only use the evaluator
            },
            static function ($arguments, $field) {
                return $arguments['formValues'][$field] ?? null;
            }
        );
    }

    /**
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    protected function getRootFormPropertyFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'getRootFormProperty',
            static function () {
                // Not implemented, we only use the evaluator
            },
            static function ($arguments, $property) {
                $formDefinition = $arguments['formRuntime']->getFormDefinition();
                try {
                    $value = ObjectAccess::getPropertyPath($formDefinition, $property);
                } catch (\Exception $exception) {
                    $value = null;
                }
                return $value;
            }
        );
    }
}
