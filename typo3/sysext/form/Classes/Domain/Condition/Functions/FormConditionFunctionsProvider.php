<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Form\Domain\Condition\Functions;

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

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

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
            $this->getFormValueFunction()
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
            function () {
                // Not implemented, we only use the evaluator
            },
            function ($arguments, $field) {
                return $arguments['formValues'][$field] ?? null;
            }
        );
    }
}
