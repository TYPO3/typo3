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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Mvc\Validation;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validator for countable types
 *
 * Scope: frontend
 * @internal
 */
class CountValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimum' => [0, 'The minimum count to accept', 'integer'],
        'maximum' => [PHP_INT_MAX, 'The maximum count to accept', 'integer'],
    ];

    /**
     * The given value is valid if it is an array or \Countable that contains the specified amount of elements.
     *
     * @param mixed $value
     */
    public function isValid($value)
    {
        if (!is_array($value) && !($value instanceof \Countable)) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1475002976',
                    'form'
                ),
                1475002976
            );
            return;
        }

        $minimum = (int)$this->options['minimum'];
        $maximum = (int)$this->options['maximum'];
        if (count($value) < $minimum || count($value) > $maximum) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1475002994',
                    'form',
                    [$minimum, $maximum]
                ),
                1475002994,
                [$this->options['minimum'], $this->options['maximum']]
            );
        }
    }
}
