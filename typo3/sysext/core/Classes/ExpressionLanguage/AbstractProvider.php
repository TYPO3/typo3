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

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Class AbstractProvider
 */
abstract class AbstractProvider implements ProviderInterface
{
    /**
     * @var ExpressionFunctionProviderInterface[]
     */
    protected $expressionLanguageProviders = [];

    /**
     * @var array
     */
    protected $expressionLanguageVariables = [];

    /**
     * An array of objects which implements the ExpressionFunctionProviderInterface
     *
     * @return ExpressionFunctionProviderInterface[]
     */
    public function getExpressionLanguageProviders(): array
    {
        return $this->expressionLanguageProviders;
    }

    /**
     * An array with key/value pairs. The key will be available as variable name
     *
     * @return array
     */
    public function getExpressionLanguageVariables(): array
    {
        return $this->expressionLanguageVariables;
    }
}
