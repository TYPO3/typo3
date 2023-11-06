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

namespace TYPO3\CMS\Extbase\Validation\Validator;

use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;

/**
 * Abstract validator. Mother af most validators.
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * Specifies whether this validator accepts empty values.
     *
     * If this is TRUE, the validators isValid() method is not called in case of an empty value
     * Note: A value is considered empty if it is NULL or an empty string!
     * By default, all validators except for NotEmpty and the Composite Validators accept empty values.
     *
     * @var bool
     */
    protected $acceptsEmptyValues = true;

    /**
     * This contains the supported options, their default values, types and descriptions.
     *
     * @var array
     */
    protected $supportedOptions = [];

    protected array $options = [];
    protected Result $result;

    public function setOptions(array $options): void
    {
        $this->initializeDefaultOptions($options);
    }

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the error messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     */
    public function validate(mixed $value): Result
    {
        $this->result = new Result();
        if ($this->acceptsEmptyValues === false || $this->isEmpty($value) === false) {
            $this->isValid($value);
        }
        return $this->result;
    }

    /**
     * Check if $value is valid. If it is not valid, needs to add an error to result.
     */
    abstract protected function isValid(mixed $value): void;

    /**
     * Creates a new validation error object and adds it to $this->result
     *
     * @param string $message The error message
     * @param int $code The error code (a unix timestamp)
     * @param array $arguments Arguments to be replaced in message
     * @param string $title title of the error
     */
    protected function addError(string $message, int $code, array $arguments = [], string $title = ''): void
    {
        $this->result->addError(new Error($message, $code, $arguments, $title));
    }

    /**
     * Creates a new validation error object for a property and adds it to the proper sub result of $this->result
     *
     * @param string|array $propertyPath The property path (string or array)
     * @param string $message The error message
     * @param int $code The error code (a unix timestamp)
     * @param array $arguments Arguments to be replaced in message
     * @param string $title Title of the error
     */
    protected function addErrorForProperty(string|array $propertyPath, string $message, int $code, array $arguments = [], string $title = ''): void
    {
        $propertyPath = is_array($propertyPath) ? implode('.', $propertyPath) : $propertyPath;
        $error = new Error($message, $code, $arguments, $title);
        $this->result->forProperty($propertyPath)->addError($error);
    }

    /**
     * Returns the options of this validator
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * TRUE if the given $value is NULL or an empty string ('')
     */
    final protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * Wrap static call to LocalizationUtility to simplify unit testing.
     */
    protected function translateErrorMessage(string $translateKey, string $extensionName, array $arguments = []): string
    {
        return LocalizationUtility::translate(
            $translateKey,
            $extensionName,
            $arguments
        ) ?? '';
    }

    /**
     * Initialize default options.
     * @throws InvalidValidationOptionsException
     */
    protected function initializeDefaultOptions(array $options): void
    {
        // check for options given but not supported
        if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== []) {
            throw new InvalidValidationOptionsException('Unsupported validation option(s) found: ' . implode(', ', array_keys($unsupportedOptions)), 1379981890);
        }
        // check for required options being set
        array_walk(
            $this->supportedOptions,
            static function (array $supportedOptionData, string $supportedOptionName, array $options): void {
                if (isset($supportedOptionData[3]) && $supportedOptionData[3] === true && !array_key_exists($supportedOptionName, $options)) {
                    throw new InvalidValidationOptionsException('Required validation option not set: ' . $supportedOptionName, 1379981891);
                }
            },
            $options
        );
        // merge with default values
        $this->options = array_merge(
            array_map(
                static fn(array $value): mixed => $value[0],
                $this->supportedOptions
            ),
            $options
        );
    }
}
