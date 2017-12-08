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

use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Scope: frontend / backend
 * @api
 */
interface ExpressionLanguageVariableProviderInterface
{

    /**
     * @param FormRuntime $formRuntime
     */
    public function __construct(FormRuntime $formRuntime);

    /**
     * @return string
     */
    public function getVariableName(): string;

    /**
     * @return mixed
     */
    public function getVariableValue();
}
