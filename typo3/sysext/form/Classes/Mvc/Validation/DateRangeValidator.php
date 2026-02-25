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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Form\Utility\DateRangeValidatorPatterns;

/**
 * Validator for date ranges
 *
 * Scope: frontend
 */
final class DateRangeValidator extends AbstractValidator implements LoggerAwareInterface
{
    use LoggerAwareTrait;
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
        if ($options === null) {
            return;
        }

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
     * Checks if this validator is correctly configured.
     *
     * Returns the resolved options array on success, or null if a date
     * option is misconfigured. In the latter case a generic validation
     * error is added for the end user and the technical details are logged.
     */
    private function validateOptions(): ?array
    {
        $options = $this->options;
        if (!empty($this->options['minimum'])) {
            $minimum = $this->parseDate($this->options['minimum']);
            if ($minimum === null) {
                $this->logger->error('DateRangeValidator: The option "minimum" ({value}) could not be converted to DateTime. Use format "{format}" or a relative expression (e.g. "today", "-18 years").', [
                    'value' => $this->options['minimum'],
                    'format' => $this->options['format'],
                ]);
                $this->addError(
                    $this->translateErrorMessage(
                        'validation.error.1748345955',
                        'form'
                    ),
                    1748345955
                );
                return null;
            }
            $minimum->modify('midnight');
            $options['minimum'] = $minimum;
        }

        if (!empty($this->options['maximum'])) {
            $maximum = $this->parseDate($this->options['maximum']);
            if ($maximum === null) {
                $this->logger->error('DateRangeValidator: The option "maximum" ({value}) could not be converted to DateTime. Use format "{format}" or a relative expression (e.g. "today", "-18 years").', [
                    'value' => $this->options['maximum'],
                    'format' => $this->options['format'],
                ]);
                $this->addError(
                    $this->translateErrorMessage(
                        'validation.error.1748345955',
                        'form'
                    ),
                    1748345955
                );
                return null;
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
     * - Any relative date expression accepted by PHP's DateTime parser
     *   (e.g. "today", "-18 years", "last sunday", "first day of next month")
     */
    private function parseDate(string $value): ?\DateTime
    {
        $date = \DateTime::createFromFormat($this->options['format'], $value);
        if ($date instanceof \DateTime) {
            return $date;
        }

        return DateRangeValidatorPatterns::parseRelativeDateExpression($value);
    }
}
