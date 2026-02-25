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
use TYPO3\CMS\Form\Utility\DateRangeValidatorPatterns;

/**
 * Validator for date ranges
 *
 * Scope: frontend
 */
final class DateRangeValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimum' => ['', 'The minimum date formatted as Y-m-d or a relative date expression (e.g. "today", "-18 years")', 'string'],
        'maximum' => ['', 'The maximum date formatted as Y-m-d or a relative date expression (e.g. "today", "-18 years")', 'string'],
        'format' => ['Y-m-d', 'The format of the minimum and maximum option', 'string'],
    ];

    /**
     * @param mixed $value The value that should be validated
     */
    public function isValid(mixed $value): void
    {
        $options = $this->validateOptions();

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

        $minimum = $options['minimum'];
        $maximum = $options['maximum'];
        $format = $options['format'];
        $value->modify('midnight');

        if ($minimum instanceof \DateTime && $value < $minimum) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1521293687',
                    'form',
                    [$minimum->format($format)]
                ),
                1521293687,
                [$minimum->format($format)]
            );
        }

        if ($maximum instanceof \DateTime && $value > $maximum) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1521293686',
                    'form',
                    [$maximum->format($format)]
                ),
                1521293686,
                [$maximum->format($format)]
            );
        }
    }

    /**
     * Checks if this validator is correctly configured
     *
     * @throws InvalidValidationOptionsException if the configured validation options are incorrect
     */
    private function validateOptions(): array
    {
        $options = $this->options;
        if (!empty($this->options['minimum'])) {
            $minimum = $this->parseDate($this->options['minimum']);
            if ($minimum === null) {
                $message = sprintf(
                    'The option "minimum" (%s) could not be converted to DateTime. Use format "%s" or a relative expression (e.g. "today", "-18 years").',
                    $this->options['minimum'],
                    $this->options['format']
                );
                throw new InvalidValidationOptionsException($message, 1521293813);
            }
            $minimum->modify('midnight');
            $options['minimum'] = $minimum;
        }

        if (!empty($this->options['maximum'])) {
            $maximum = $this->parseDate($this->options['maximum']);
            if ($maximum === null) {
                $message = sprintf(
                    'The option "maximum" (%s) could not be converted to DateTime. Use format "%s" or a relative expression (e.g. "today", "-18 years").',
                    $this->options['maximum'],
                    $this->options['format']
                );
                throw new InvalidValidationOptionsException($message, 1521293814);
            }
            $maximum->modify('midnight');
            $options['maximum'] = $maximum;
        }
        return $options;
    }

    /**
     * Parse a date string as absolute format first, then fall back to relative expressions.
     *
     * Supports:
     * - Absolute dates matching the configured format (e.g. "2025-03-17")
     * - Relative date expressions supported by PHP's DateTime parser (e.g. "today", "-18 years", "+1 month")
     */
    private function parseDate(string $value): ?\DateTime
    {
        $date = \DateTime::createFromFormat($this->options['format'], $value);
        if ($date instanceof \DateTime) {
            return $date;
        }

        if (!self::isRelativeDateExpression($value)) {
            return null;
        }

        try {
            return new \DateTime($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Check if the value is a recognized relative date expression.
     *
     * Only explicit relative expressions are accepted to prevent
     * ambiguous strings (e.g. "1972-01") from being silently interpreted.
     */
    private static function isRelativeDateExpression(string $value): bool
    {
        return (bool)preg_match(DateRangeValidatorPatterns::RELATIVE_DATE_PCRE, trim($value));
    }
}
