<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Condition;

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

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 *
 * @internal
 */
class ConditionResolver
{

    /**
     * @var \TYPO3\CMS\Form\Domain\Condition\ConditionContext
     */
    protected $conditionContext;

    /**
     * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var array
     */
    public $expressionLanguageVariables = [];

    /**
     * @param ConditionContext $conditionContext
     */
    public function __construct(ConditionContext $conditionContext)
    {
        $this->conditionContext = $conditionContext;
        $this->expressionLanguage = new ExpressionLanguage(null, $conditionContext->getExpressionLanguageProviders());
        $this->expressionLanguageVariables = $conditionContext->getExpressionLanguageVariables();
    }

    /**
     * @param string $condition
     * @return bool
     */
    public function resolveCondition(string $condition): bool
    {
        return (bool)$this->expressionLanguage->evaluate($condition, $this->expressionLanguageVariables);
    }
}
