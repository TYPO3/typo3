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

namespace TYPO3\CMS\Core\ExpressionLanguage;

/**
 * Provide functions and variables to symfony expression language.
 *
 * Note 'variables' should only rely on things that can be injected.
 * Accessing for instance $GLOBALS['TYPO3_REQUEST'] and providing this
 * as variable is a misuse - runtime related variables must be provided
 * by the caller to the Resolver class directly.
 */
abstract class AbstractProvider implements ProviderInterface
{
    protected array $expressionLanguageProviders = [];
    protected array $expressionLanguageVariables = [];

    public function getExpressionLanguageProviders(): array
    {
        return $this->expressionLanguageProviders;
    }

    /**
     * An array with key/value pairs. The key will be available as variable name
     */
    public function getExpressionLanguageVariables(): array
    {
        return $this->expressionLanguageVariables;
    }
}
