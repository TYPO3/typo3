<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Form\Domain\Condition\Functions;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

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
