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

namespace TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing;

/**
 * Helper for array processing
 *
 * Scope: frontend / backend
 * @internal
 */
class ArrayProcessing
{

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $expression;

    /**
     * @var callable
     */
    protected $processor;

    /**
     * @param string $identifier
     * @param string $expression
     * @param callable $processor
     */
    public function __construct(string $identifier, string $expression, callable $processor)
    {
        $this->identifier = $identifier;
        $this->expression = $expression;
        $this->processor = $processor;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @return callable
     */
    public function getProcessor(): callable
    {
        return $this->processor;
    }
}
