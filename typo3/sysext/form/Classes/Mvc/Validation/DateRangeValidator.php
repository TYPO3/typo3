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

namespace TYPO3\CMS\Form\Mvc\Validation;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Form\Mvc\Validation\Exception\InvalidValidationOptionsException;

/**
 * Validator for date ranges
 *
 * Scope: frontend
 */
class DateRangeValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimum' => ['', 'The minimum date formatted as Y-m-d', 'string'],
        'maximum' => ['', 'The maximum date formatted as Y-m-d', 'string'],
        'format' => ['Y-m-d', 'The format of the minimum and maximum option', 'string'],
    ];

    /**
     * @param \DateTime $value The value that should be validated
     */
    public function isValid($value)
    {
        $this->validateOptions();

        if (!($value instanceof \DateTime)) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1521293685',
                    'form',
                    [gettype($value)]
                ),
                1521293685
            );

            return;
        }

        $minimum = $this->options['minimum'];
        $maximum = $this->options['maximum'];
        $format = $this->options['format'];
        $value->modify('midnight');

        if (
            $minimum instanceof \DateTime
            && $value < $minimum
        ) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1521293686',
                    'form',
                    [$minimum->format($format)]
                ),
                1521293686,
                [$minimum->format($format)]
            );
        }

        if (
            $maximum instanceof \DateTime
            && $value > $maximum
        ) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1521293687',
                    'form',
                    [$maximum->format($format)]
                ),
                1521293687,
                [$maximum->format($format)]
            );
        }
    }

    /**
     * Checks if this validator is correctly configured
     *
     * @throws InvalidValidationOptionsException if the configured validation options are incorrect
     */
    protected function validateOptions()
    {
        if (!empty($this->options['minimum'])) {
            $minimum = \DateTime::createFromFormat($this->options['format'], $this->options['minimum']);
            if (!($minimum instanceof \DateTime)) {
                $message = sprintf('The option "minimum" (%s) could not be converted to \DateTime from format "%s".', $this->options['minimum'], $this->options['format']);
                throw new InvalidValidationOptionsException($message, 1521293813);
            }

            $minimum->modify('midnight');
            $this->options['minimum'] = $minimum;
        }

        if (!empty($this->options['maximum'])) {
            $maximum = \DateTime::createFromFormat($this->options['format'], $this->options['maximum']);
            if (!($maximum instanceof \DateTime)) {
                $message = sprintf('The option "maximum" (%s) could not be converted to \DateTime from format "%s".', $this->options['maximum'], $this->options['format']);
                throw new InvalidValidationOptionsException($message, 1521293814);
            }

            $maximum->modify('midnight');
            $this->options['maximum'] = $maximum;
        }
    }
}
