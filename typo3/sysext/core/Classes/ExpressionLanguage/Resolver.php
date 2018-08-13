<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\ExpressionLanguage;

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
 * Class Resolver
 * @internal
 */
class Resolver
{
    /**
     * @var ProviderInterface
     */
    protected $context;

    /**
     * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var array
     */
    public $expressionLanguageVariables = [];

    /**
     * @param ProviderInterface $context
     */
    public function __construct(ProviderInterface $context)
    {
        $this->context = $context;
        $this->expressionLanguage = new ExpressionLanguage(null, $context->getExpressionLanguageProviders());
        $this->expressionLanguageVariables = $context->getExpressionLanguageVariables();
    }

    /**
     * Evaluate an expression.
     *
     * @param string $condition The expression to parse
     * @return bool
     */
    public function evaluate(string $condition): bool
    {
        return (bool)$this->expressionLanguage->evaluate($condition, $this->expressionLanguageVariables);
    }

    /**
     * Compiles an expression source code.
     *
     * @param string $condition The expression to compile
     * @return string
     */
    public function compile(string $condition): string
    {
        return (string)$this->expressionLanguage->compile($condition, array_keys($this->expressionLanguageVariables));
    }
}
